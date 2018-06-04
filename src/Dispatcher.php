<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2018/3/31
 * Time: 22:50
 */

namespace pms;

use Phalcon\Config;
use Phalcon\FilterInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Cli\Dispatcher\Exception;
use Phalcon\Cli\Dispatcher as CliDispatcher;
use pms\bear\Counnect;

/**
 * Class Dispatcher
 * @property \pms\bear\Counnect $connect
 * @property  \pms\Session $session
 * @package pms
 */
class Dispatcher extends CliDispatcher
{

    public $connect;
    public $session;


    /**
     * 设置连接对象
     * @param Counnect $connect
     */
    public function setConnect(bear\Counnect $connect)
    {
        $this->connect = $connect;
    }

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
        $dConfig = $dependencyInjector->getShared('dConfig');
        if (!($dConfig instanceof Config)) {
            $this->_throwDispatchException("dConfig is not instanceof Config ", self::EXCEPTION_NO_DI);
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
        $sid = $this->connect->sid;
        $session_init = !empty($sid) && $dConfig->session;
        if ($session_init) {
            $session = $this->init_session($this->connect);
            $this->session = $session;
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
            # 处理程序从服务容器中检索为共享实例。
            $hasService = (bool)$dependencyInjector->has($handlerClass);
            if (!$hasService) {
                # DI没有使用这个名称的服务，尝试使用自动加载器加载它。
                // DI doesn't have a service with that name, try to load it using an autoloader
                $hasService = (bool)class_exists($handlerClass);
            }

            // If the service can be loaded we throw an exception
            # 如果服务不可以加载，我们抛出一个异常。

            if (!class_exists($handlerClass) || !$hasService) {
                if ($hasEventsManager) {
                    if ($eventsManager->fire("dispatch:beforeNotFoundHandler", $this) === false) {
                        continue;
                    }
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                // Try to throw an exception when an action isn't defined on the object
                $status = $this->{"_throwDispatchException"}("was not found on handler '" . $handlerClass . "'", self::EXCEPTION_ACTION_NOT_FOUND);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }


            $handler = new $handlerClass();
            if ($session_init) {
                $handler->session = $session;
            }

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

            // 检查参数是否是一个数组

            if (!is_array($params)) {
                // 传递了一个无效的参数变量会抛出异常
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
            # 设置连接对象
            $handler->connect = $this->connect;
            // 为了确保initialize（）被调用，我们将销毁当前的handlerClass
            // 如果发生错误，并且我们继续从该容器中取出，则从DI容器中取出。 这个
            // 是必要的，因为在实例的检索和执行之间是不相容的
            // initialize（）事件。 从编码的角度来看，它可能会更有意义
            // 在beforeExecuteRoute之前放置initialize（），这将解决这个问题。 但是，为了保持后代，并保持一致性，我们将确保默认和记录的行为正常工作。

            if ($hasEventsManager) {
                // Calling "dispatch:beforeExecuteRoute" event
                if ($eventsManager->fire("dispatch:beforeExecuteRoute", $handler) === false || $this->_finished === false) {

                    continue;
                }

            }

            if (method_exists($handler, "beforeExecuteRoute")) {
                // 直接调用“beforeExecuteRoute”方法
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

                // 调用“dispatch：afterInitialize”事件
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

            // 调用afterBinding作为回调和事件
            if (method_exists($handler, "afterBinding")) {
                if ($handler->afterBinding($this) === false) {
                    continue;
                }

                // 检查用户是否在侦听器中前进
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

            // 调用“dispatch：afterDispatch”事件
            if ($hasEventsManager) {
                try {
                    $eventsManager->fire("dispatch:afterDispatch", $this, $value);
                } catch (Exception $e) {
                    // 仍然检查完成在这里，因为我们想优先考虑forwarding（）调用
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

    /**
     * 初始化session
     */
    protected function init_session(Counnect $connect)
    {
        # 进行模拟session
        # 读取session_id
        $sid = $connect->sid;
        $this->session_id = $sid;
        output($sid, 'sid');
        return new Session($sid);
    }

    public function __destruct()
    {
        if ($this->session) {
            $this->session->reserve();
        }
        output('销毁调度器', '__destruct');
    }

}