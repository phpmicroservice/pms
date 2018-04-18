<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2018/3/31
 * Time: 22:50
 */

namespace pms;

use Phalcon\FilterInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Cli\Dispatcher\Exception;
use Phalcon\Cli\Dispatcher as CliDispatcher;

class Dispatcher extends CliDispatcher
{

    /**
     * Process the results of the router by calling into the appropriate controller action(s)
     * including any routing data or injected parameters.
     *
     * @return object|false Returns the dispatched handler class (the Controller for Mvc dispatching or a Task
     *                      for CLI dispatching) or <tt>false</tt> if an exception occurred and the operation was
     *                      stopped by returning <tt>false</tt> in the exception handler.
     *
     * @throws \Exception if any uncaught or unhandled exception occurs during the dispatcher process.
     */
    public function dispatch()
    {

        $dependencyInjector = $this->_dependencyInjector;

        if (!is_object($dependencyInjector)) {


            $this->_throwDispatchException("A dependency injection container is required to access related dispatching services", self::EXCEPTION_NO_DI);
            return false;
        }

        $eventsManager = $this->_eventsManager;
        $hasEventsManager = is_object($eventsManager);
        $this->_finished = true;

        if ($hasEventsManager) {

            // Calling beforeDispatchLoop event
            // Note: Allow user to forward in the beforeDispatchLoop.
            if ($eventsManager->fire("dispatch:beforeDispatchLoop", $this) === false && $this->_finished !== false) {
                return false;
            }

        }

        $value = null;
        $handler = null;
        $numberDispatches = 0;
        $actionSuffix = $this->_actionSuffix;
        $this->_finished = false;

        while (!$this->_finished) {
            $numberDispatches++;

            // Throw an exception after 256 consecutive forwards
            if (numberDispatches == 256) {
                $this->{"_throwDispatchException"}("Dispatcher has detected a cyclic routing causing stability problems", self::$EXCEPTION_CYCLIC_ROUTING);
                break;
            }

            $this->_finished = true;
            $this->_resolveEmptyProperties();

            if ($hasEventsManager) {

                // Calling "dispatch:beforeDispatch" event
                if ($eventsManager->fire("dispatch:beforeDispatch", $this) === false || $this->_finished === false) {
                    continue;
                }

            }

            $handlerClass = $this->getHandlerClass();

            // Handlers are retrieved as shared instances from the Service Container
            # �������ӷ��������м���Ϊ����ʵ����
            $hasService = (bool)$dependencyInjector->has($handlerClass);
            if (!$hasService) {
                # DIû��ʹ��������Ƶķ��񣬳���ʹ���Զ���������������
                // DI doesn't have a service with that name, try to load it using an autoloader
                $hasService = (bool)class_exists($handlerClass);
            }

            // If the service can be loaded we throw an exception
            # ���������Լ��أ������׳�һ���쳣��
            if (!$hasService) {
                $status = $this->{"_throwDispatchException"}($handlerClass . " handler class cannot be loaded", self::EXCEPTION_HANDLER_NOT_FOUND);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }
            $handler = new $handlerClass();
            $wasFresh = true;

			// Handlers must be only objects

            if (!is_object($handler)) {
                $status = $this->{"_throwDispatchException"}("Invalid handler returned from the services container", self::EXCEPTION_INVALID_HANDLER);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }

            $this->_activeHandler = $handler;

            $namespaceName = $this->_namespaceName;
            $handlerName = $this->_handlerName;
            $actionName = $this->_actionName;
            $params = $this->_params;

            // �������Ƿ���һ������

            if (!is_array($params)) {
                // ������һ����Ч�Ĳ����������׳��쳣
                $status = $this->_throwDispatchException("Action parameters must be an Array", self::EXCEPTION_INVALID_PARAMS);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }

			// Check if the method exists in the handler
            $actionMethod = $this->getActiveMethod();

            if (!is_callable([$handler, $actionMethod])) {

                if ($hasEventsManager) {
                    if ($eventsManager->fire("dispatch:beforeNotFoundAction", $this) === false) {
                        continue;
                    }

                    if ($this->_finished === false) {
                        continue;
                    }
                }

                // Try to throw an exception when an action isn't defined on the object
                $status = $this->{"_throwDispatchException"}("Action '" . $actionName . "' was not found on handler '" . $handlerName . "'", self::EXCEPTION_ACTION_NOT_FOUND);
                if ($status === false && $this->_finished === false) {
                    continue;
                }

                break;
            }

            // Ϊ��ȷ��initialize���������ã����ǽ����ٵ�ǰ��handlerClass
            // ����������󣬲������Ǽ����Ӹ�������ȡ�������DI������ȡ���� ���
            // �Ǳ�Ҫ�ģ���Ϊ��ʵ���ļ�����ִ��֮���ǲ����ݵ�
            // initialize�����¼��� �ӱ���ĽǶ������������ܻ��������
            // ��beforeExecuteRoute֮ǰ����initialize�������⽫���������⡣ ���ǣ�Ϊ�˱��ֺ����������һ���ԣ����ǽ�ȷ��Ĭ�Ϻͼ�¼����Ϊ����������

            if ($hasEventsManager) {

                    // Calling "dispatch:beforeExecuteRoute" event
                if ($eventsManager->fire("dispatch:beforeExecuteRoute", $handler) === false || $this->_finished === false) {

                    continue;
                }

            }

            if (method_exists($handler, "beforeExecuteRoute")) {
                // ֱ�ӵ��á�beforeExecuteRoute������
                if ($handler->beforeExecuteRoute($this) === false || $this->_finished === false) {
                    continue;
                }

            }

            if ($wasFresh === true) {
                if (method_exists($handler, "initialize")) {
                    $this->_isControllerInitialize = true;
                    $handler->initialize();
                }

                $this->_isControllerInitialize = false;

                // ���á�dispatch��afterInitialize���¼�
                if ($eventsManager) {
                    if ($eventsManager->fire("dispatch:afterInitialize", $this) === false || $this->_finished === false) {
                        continue;
                    }
                }
			}

            if ($this->_modelBinding) {
                $modelBinder = $this->_modelBinder;
                $bindCacheKey = "_PHMB_" . $handlerClass . "_" . $actionMethod;
                $params = $modelBinder->bindToHandler($handler, $params, $bindCacheKey, $actionMethod);
			}

			// Calling afterBinding
            if ($hasEventsManager) {
                if ($eventsManager->fire("dispatch:afterBinding", this) === false) {
                    continue;
                }

				// Check if the user made a forward in the listener
                if ($this->_finished === false) {
                    continue;
                }
			}

            // ����afterBinding��Ϊ�ص����¼�
            if (method_exists($handler, "afterBinding")) {
                if ($handler->afterBinding($this) === false) {
                    continue;
                }

                // ����û��Ƿ�����������ǰ��
                if ($this->_finished === false) {
                    continue;
                }
            }

			// Save the current handler
            $this->_lastHandler = $handler;


                // We update the latest value produced by the latest handler
            $this->_returnedValue = $this->callActionMethod($handler, $actionMethod, $params);

            if ($this->_finished === false) {
                    continue;
                }


			// Calling "dispatch:afterExecuteRoute" event
            if ($hasEventsManager) {

                if ($eventsManager->fire("dispatch:afterExecuteRoute", $this, $value) === false || $this->_finished === false) {
                        continue;
                    }

			}

			// Calling "afterExecuteRoute" as direct method
            if (method_exists($handler, "afterExecuteRoute")) {
				try {
                    if ($handler->afterExecuteRoute(this, value) === false || $this->_finished === false) {
                        continue;
                    }
                } catch (Exception $e) {
                    if ($this->{"_handleException"}($e) === false || $this->_finished === false) {
                        continue;
                    }

                    throw e;
                }
			}

            // ���á�dispatch��afterDispatch���¼�
            if ($hasEventsManager) {
                try {
                    $eventsManager->fire("dispatch:afterDispatch", $this, $value);
                } catch (Exception $e) {
                    // ��Ȼ�������������Ϊ���������ȿ���forwarding��������
                    if ($this->{"_handleException"}($e) === false || $this->_finished === false) {
                        continue;
                    }

                    throw e;
                }
			}
		}

        if ($hasEventsManager) {
            try {
                // Calling "dispatch:afterDispatchLoop" event
                // Note: We don't worry about forwarding in after dispatch loop.
                $eventsManager->fire("dispatch:afterDispatchLoop", $this);
            } catch (Exception $e) {
                // Exception occurred in afterDispatchLoop.
                if ($this->{"_handleException"}($e) === false) {
                    return false;
                }


            }
		}

        return $handler;
    }

}