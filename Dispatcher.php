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
use Phalcon\Dispatcher\Dispatcher as CliDispatcher;

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


        $dependencyInjector = <DiInterface > this->_dependencyInjector;
		if typeof dependencyInjector != "object" {
        this->{
            "_throwDispatchException"}("A dependency injection container is required to access related dispatching services", self::EXCEPTION_NO_DI);
			return false;
		}

		let eventsManager = <ManagerInterface > this->_eventsManager;
		let hasEventsManager = typeof eventsManager == "object";
		let this->_finished = true;

		if hasEventsManager {
            try {
                // Calling beforeDispatchLoop event
                // Note: Allow user to forward in the beforeDispatchLoop.
                if eventsManager->fire("dispatch:beforeDispatchLoop", this) === false && this->_finished !== false {
                    return false;
                }
			} catch Exception, e {
                // Exception occurred in beforeDispatchLoop.

                // The user can optionally forward now in the `dispatch:beforeException` event or
                // return <tt>false</tt> to handle the exception and prevent it from bubbling. In
                // the event the user does forward but does or does not return false, we assume the forward
                // takes precedence. The returning false intuitively makes more sense when inside the
                // dispatch loop and technically we are not here. Therefore, returning false only impacts
                // whether non-forwarded exceptions are silently handled or bubbled up the stack. Note that
                // this behavior is slightly different than other subsequent events handled inside the
                // dispatch loop.

                let status = this->{
                    "_handleException"}(e);
				if this->_finished !== false {
                    // No forwarding
                    if status === false {
                        return false;
                    }

                    // Otherwise, bubble Exception
                    throw e;
                }

				// Otherwise, user forwarded, continue
			}
		}

		let value = null,
			handler = null,
			numberDispatches = 0,
			actionSuffix = this->_actionSuffix,
			this->_finished = false;

		while !this->_finished {
        let numberDispatches++;

			// Throw an exception after 256 consecutive forwards
			if numberDispatches == 256 {
                this->{
                    "_throwDispatchException"}("Dispatcher has detected a cyclic routing causing stability problems", self::EXCEPTION_CYCLIC_ROUTING);
				break;
			}

			let this->_finished = true;
			this->_resolveEmptyProperties();

			if hasEventsManager {
                try {
                    // Calling "dispatch:beforeDispatch" event
                    if eventsManager->fire("dispatch:beforeDispatch", this) === false || this->_finished === false {
                        continue;
                    }
				} catch Exception, e {
                    if this->{
                        "_handleException"}(e) === false || this->_finished === false {
                        continue;
                    }

					throw e;
				}
			}

			let handlerClass = this->getHandlerClass();

			// Handlers are retrieved as shared instances from the Service Container
			let hasService = (bool)dependencyInjector->has(handlerClass);
			if !hasService {
                // DI doesn't have a service with that name, try to load it using an autoloader
                let hasService = (bool)class_exists(handlerClass);
			}

			// If the service can be loaded we throw an exception
			if !hasService {
                let status = this->{
                    "_throwDispatchException"}(handlerClass . " handler class cannot be loaded", self::EXCEPTION_HANDLER_NOT_FOUND);
				if status === false && this->_finished === false {
                    continue;
                }
				break;
			}

			let handler = dependencyInjector->getShared(handlerClass);
			let wasFresh = dependencyInjector->wasFreshInstance();

			// Handlers must be only objects
			if typeof handler !== "object" {
            let status = this->{
                "_throwDispatchException"}("Invalid handler returned from the services container", self::EXCEPTION_INVALID_HANDLER);
				if status === false && this->_finished === false {
                continue;
            }
				break;
			}

			let this->_activeHandler = handler;

			let namespaceName = this->_namespaceName;
			let handlerName = this->_handlerName;
			let actionName = this->_actionName;
			let params = this->_params;

			// Check if the params is an array
			if typeof params != "array" {
            // An invalid parameter variable was passed throw an exception
            let status = this->{
                "_throwDispatchException"}("Action parameters must be an Array", self::EXCEPTION_INVALID_PARAMS);
				if status === false && this->_finished === false {
                continue;
            }
				break;
			}

			// Check if the method exists in the handler
			let actionMethod = this->getActiveMethod();

			if !is_callable([handler, actionMethod]){
				if hasEventsManager {
                    if eventsManager->fire("dispatch:beforeNotFoundAction", this) === false {
                        continue;
                    }

					if this->_finished === false {
                        continue;
                    }
				}

				// Try to throw an exception when an action isn't defined on the object
				let status = this->{
            "_throwDispatchException"}("Action '" . actionName . "' was not found on handler '" . handlerName . "'", self::EXCEPTION_ACTION_NOT_FOUND);
				if status === false && this->_finished === false {
            continue;
        }

				break;
			}

			// In order to ensure that the initialize() gets called we'll destroy the current handlerClass
			// from the DI container in the event that an error occurs and we continue out of this block. This
			// is necessary because there is a disjoin between retrieval of the instance and the execution
			// of the initialize() event. From a coding perspective, it would have made more sense to probably
			// put the initialize() prior to the beforeExecuteRoute which would have solved this. However, for
			// posterity, and to remain consistency, we'll ensure the default and documented behavior works correctly.

			if hasEventsManager {
                try {
                    // Calling "dispatch:beforeExecuteRoute" event
                    if eventsManager->fire("dispatch:beforeExecuteRoute", this) === false || this->_finished === false {
                        dependencyInjector->remove(handlerClass);
						continue;
					}
				} catch Exception, e {
                    if this->{
                        "_handleException"}(e) === false || this->_finished === false {
                        dependencyInjector->remove(handlerClass);
						continue;
					}

					throw e;
				}
			}

			if method_exists(handler, "beforeExecuteRoute"){
				try {
                    // Calling "beforeExecuteRoute" as direct method
                    if handler->beforeExecuteRoute(this) === false || this->_finished === false {
                        dependencyInjector->remove(handlerClass);
						continue;
					}
				} catch Exception, e {
            if this->{
                "_handleException"}(e) === false || this->_finished === false {
                dependencyInjector->remove(handlerClass);
						continue;
					}

					throw e;
				}
			}

			// Call the "initialize" method just once per request
			//
			// Note: The `dispatch:afterInitialize` event is called regardless of the presence of an `initialize`
			//       method. The naming is poor; however, the intent is for a more global "constructor is ready
			//       to go" or similarly "__onConstruct()" methodology.
			//
			// Note: In Phalcon 4.0, the initialize() and `dispatch:afterInitialize` event will be handled
			// prior to the `beforeExecuteRoute` event/method blocks. This was a bug in the original design
			// that was not able to change due to widespread implementation. With proper documentation change
			// and blog posts for 4.0, this change will happen.
			//
			// @see https://github.com/phalcon/cphalcon/pull/13112
			if wasFresh === true {
                if method_exists(handler, "initialize"){
					try {
                        let this->_isControllerInitialize = true;
						handler->initialize();

					} catch Exception, e {
                    let this->_isControllerInitialize = false;

						// If this is a dispatch exception (e.g. From forwarding) ensure we don't handle this twice. In
						// order to ensure this doesn't happen all other exceptions thrown outside this method
						// in this class should not call "_throwDispatchException" but instead throw a normal Exception.

						if this->{
                        "_handleException"}(e) === false || this->_finished === false {
                        continue;
                    }

						throw e;
					}
				}

				let this->_isControllerInitialize = false;

			    // Calling "dispatch:afterInitialize" event
				if eventsManager {
                    try {
                        if eventsManager->fire("dispatch:afterInitialize", this) === false || this->_finished === false {
                            continue;
                        }
					} catch Exception, e {
                        if this->{
                            "_handleException"}(e) === false || this->_finished === false {
                            continue;
                        }

						throw e;
					}
				}
			}

			if this->_modelBinding {
            let modelBinder = this->_modelBinder;
				let bindCacheKey = "_PHMB_" . handlerClass . "_" . actionMethod;
				let params = modelBinder->bindToHandler(handler, params, bindCacheKey, actionMethod);
			}

			// Calling afterBinding
			if hasEventsManager {
                if eventsManager->fire("dispatch:afterBinding", this) === false {
                    continue;
                }

				// Check if the user made a forward in the listener
				if this->_finished === false {
                    continue;
                }
			}

			// Calling afterBinding as callback and event
			if method_exists(handler, "afterBinding"){
				if handler->afterBinding(this) === false {
            continue;
        }

				// Check if the user made a forward in the listener
				if this->_finished === false {
            continue;
        }
			}

			// Save the current handler
			let this->_lastHandler = handler;

			try {
                // We update the latest value produced by the latest handler
                let this->_returnedValue = this->callActionMethod(handler, actionMethod, params);

				if this->_finished === false {
                    continue;
                }
			} catch Exception, e {
            if this->{
                "_handleException"}(e) === false || this->_finished === false {
                continue;
            }

				throw e;
			}

			// Calling "dispatch:afterExecuteRoute" event
			if hasEventsManager {
                try {
                    if eventsManager->fire("dispatch:afterExecuteRoute", this, value) === false || this->_finished === false {
                        continue;
                    }
				} catch Exception, e {
                    if this->{
                        "_handleException"}(e) === false || this->_finished === false {
                        continue;
                    }

					throw e;
				}
			}

			// Calling "afterExecuteRoute" as direct method
			if method_exists(handler, "afterExecuteRoute"){
				try {
                    if handler->afterExecuteRoute(this, value) === false || this->_finished === false {
                        continue;
                    }
				} catch Exception, e {
            if this->{
                "_handleException"}(e) === false || this->_finished === false {
                continue;
            }

					throw e;
				}
			}

			// Calling "dispatch:afterDispatch" event
			if hasEventsManager {
                try {
                    eventsManager->fire("dispatch:afterDispatch", this, value);
				} catch Exception, e {
                    // Still check for finished here as we want to prioritize forwarding() calls
                    if this->{
                        "_handleException"}(e) === false || this->_finished === false {
                        continue;
                    }

					throw e;
				}
			}
		}

		if hasEventsManager {
            try {
                // Calling "dispatch:afterDispatchLoop" event
                // Note: We don't worry about forwarding in after dispatch loop.
                eventsManager->fire("dispatch:afterDispatchLoop", this);
			} catch Exception, e {
                // Exception occurred in afterDispatchLoop.
                if this->{
                    "_handleException"}(e) === false {
                    return false;
                }

				// Otherwise, bubble Exception
				throw e;
			}
		}

		return handler;
	}

}