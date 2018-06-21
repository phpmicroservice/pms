<?php

namespace pms\Http;

use Phalcon\DiInterface;
use Phalcon\FilterInterface;
use Phalcon\Http\Request\File;
use Phalcon\Http\Request\Exception;
use Phalcon\Di\InjectionAwareInterface;

/**
 * Created by PhpStorm.
 * User: toplink_php1
 * Date: 2018/6/12
 * Time: 10:40
 */
class Request implements \Phalcon\Http\RequestInterface, InjectionAwareInterface
{
    protected $_dependencyInjector;
    protected $_rawBody;
    protected $_REQUEST;
    protected $_REQUESTData;

    public function __construct(\swoole_http_request $request, \swoole_http_response $response)
    {
        $this->_REQUEST = $request;
        $this->_REQUESTData = array_merge($this->_REQUEST->get, $this->_REQUEST->post, $this->_REQUEST->cookie);
    }

    /**
     * Sets the dependency injector
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     */
    public function getDI(): DiInterface
    {
        return $this->_dependencyInjector;
    }

    /**
     * Gets a variable from the $_REQUEST superglobal applying filters if needed
     *
     * @param string $name
     * @param string|array $filters
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($name = null, $filters = null, $defaultValue = null, boolean $notAllowEmpty = false, boolean $noRecursive = false)
    {
        return $this->getHelper($this->_REQUESTData, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
    }

    /**
     * Gets a variable from the $_POST superglobal applying filters if needed
     *
     * @param string $name
     * @param string|array $filters
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getPost($name = null, $filters = null, $defaultValue = null, boolean $notAllowEmpty = false, boolean $noRecursive = false)
    {
        return $this->getHelper($this->_REQUEST->post, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
    }

    /**
     * Gets variable from $_GET superglobal applying filters if needed
     *
     * @param string $name
     * @param string|array $filters
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getQuery($name = null, $filters = null, $defaultValue = null, boolean $notAllowEmpty = false, boolean $noRecursive = false)
    {
        return $this->getHelper($this->_REQUEST->get, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
    }

    /**
     * Checks whether $_REQUEST superglobal has certain index
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_REQUESTData[$name]);
    }

    /**
     * Checks whether $_POST superglobal has certain index
     *
     * @param string $name
     * @return bool
     */
    public function hasPost($name)
    {
        return isset($this->_REQUEST->post[$name]);
    }

    /**
     * Checks whether the PUT data has certain index
     *
     * @param string $name
     * @return bool
     */
    public function hasPut($name)
    {
        return false;
    }

    /**
     * Checks whether $_GET superglobal has certain index
     *
     * @param string $name
     * @return bool
     */
    public function hasQuery($name)
    {
        return isset($this->_REQUEST->get[$name]);
    }

    /**
     * Checks whether $_SERVER superglobal has certain index
     *
     * @param string $name
     * @return bool
     */
    public function hasServer($name)
    {
        return isset($this->_REQUEST->server[$name]);
    }

    /**
     * Gets HTTP header from request data
     *
     * @param string $header
     * @return string
     */
    public function getHeader($header)
    {
        return isset($this->_REQUEST->header);
    }

    /**
     * Checks whether request has been made using ajax. Checks if $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest"
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->getServer('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Checks whether request has been made using SOAP
     *
     * @return bool
     */
    public function isSoapRequested()
    {
        return $this->isSoap();
    }

    /**
     * Checks whether request has been made using SOAP
     * @return bool
     */
    public function isSoap()
    {
        return false;
    }

    /**
     * Checks whether request has been made using any secure layer
     *
     * @return bool
     */
    public function isSecureRequest()
    {
        return $this->isSecure();
    }

    /**
     * Checks whether request has been made using any secure layer
     */
    public function isSecure(): boolean
    {
        return $this->getScheme() === "https";
    }

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
     */
    public function getScheme()
    {
        $https = $this->getServer("HTTPS");
        if ($https) {
            if ($https == "off") {
                $scheme = "http";
            } else {
                $scheme = "https";
            }
        } else {
            $scheme = "http";
        }
        return $scheme;
    }

    /**
     * Gets variable from $_SERVER superglobal
     *
     * @param string $name
     * @return mixed
     */
    public function getServer($name)
    {
        if (isset($this->_REQUEST->server[$name])) {
            return $this->_REQUEST->server[$name];
        }
        return null;
    }

    /**
     * Gets HTTP raw request body
     *
     * @return string
     */
    public function getRawBody()
    {


        $rawBody = $this->_rawBody;
        if (empty ($rawBody)) {

            $contents = file_get_contents("php://input");

            /**
             * We need store the read raw body because it can't be read again
             */
            $this->_rawBody = $contents;
            return $contents;
        }
        return $rawBody;
    }

    /**
     * Gets active server address IP
     *
     * @return string
     */
    public function getServerAddress()
    {

        if (isset($this->_REQUEST->server['SERVER_ADDR'])) {
            return $this->_REQUEST->server['SERVER_ADDR'];
        }
        return gethostbyname("localhost");
    }

    /**
     * Gets active server name
     *
     * @return string
     */
    public function getServerName()
    {
        if (isset($this->_REQUEST->server['SERVER_NAME'])) {
            return $this->_REQUEST->server['SERVER_NAME'];
        }
        return "localhost";
    }

    /**
     * Gets host name used by the request
     *
     * @return string
     */
    public function getHttpHost()
    {

        /**
         * Get the server name from $_SERVER["HTTP_HOST"]
         */
        $host = $this->getServer("HTTP_HOST");
        if (!$host) {

            /**
             * Get the server name from $_SERVER["SERVER_NAME"]
             */
            $host = $this->getServer("SERVER_NAME");
            if (!$host) {
                /**
                 * Get the server address from $_SERVER["SERVER_ADDR"]
                 */
                $host = $this->getServer("SERVER_ADDR");
            }
        }
        $host = strtolower(trim(host));

        return (string)$host;
    }

    /**
     * Gets information about the port on which the request is made
     *
     * @return int
     */
    public function getPort()
    {
        return (int)$this->getServer("SERVER_PORT");
    }

    /**
     * Gets most possibly client IPv4 Address. This methods searches in
     * $_SERVER["REMOTE_ADDR"] and optionally in $_SERVER["HTTP_X_FORWARDED_FOR"]
     *
     * @param bool $trustForwardedHeader
     * @return string
     */
    public function getClientAddress($trustForwardedHeader = false)
    {

        $address = null;
        /**
         * Proxies uses this IP
         */
        if ($trustForwardedHeader) {
            if (isset($this->_REQUEST->server['HTTP_X_FORWARDED_FOR'])) {
                $address = $this->_REQUEST->server['HTTP_X_FORWARDED_FOR'];
            }

            if ($address === nul) {
                $address = $this->_REQUEST->server['HTTP_CLIENT_IP'];
            }
        }

        if ($address === null) {
            $address = $this->_REQUEST->server['REMOTE_ADDR'];

        }

        if (is_string($address)) {
            if (strpos(address, ",")) {
                /**
                 * The client address has multiples parts, only return the first part
                 */
                return explode(",", address)[0];
            }
            return $address;
        }

        return '';
    }

    /**
     * Gets HTTP user agent used to made the request
     *
     * @return string
     */
    public function getUserAgent()
    {
        if (isset($this->_REQUEST->server['HTTP_USER_AGENT'])) {
            return $this->_REQUEST->server['HTTP_USER_AGENT'];
        }
        return "";
    }

    /**
     * Check if HTTP method match any of the passed methods
     *
     * @param string|array $methods
     * @param bool $strict
     * @return bool
     */
    public function isMethod($methods, $strict = false)
    {

    }

    /**
     * Checks whether HTTP method is POST. if $_SERVER["REQUEST_METHOD"] === "POST"
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->getMethod() === "POST";
    }

    /**
     * Gets HTTP method which request has been made
     *
     * @return string
     */
    public function getMethod()
    {

        $returnMethod = "";

        if (isset($this->_REQUEST->server['REQUEST_METHOD'])) {
            $requestMethod = $this->_REQUEST->server['REQUEST_METHOD'];
            $returnMethod = strtoupper($requestMethod);
        } else {
            return "GET";
        }


        return $returnMethod;
    }

    /**
     * Checks whether HTTP method is GET. if $_SERVER["REQUEST_METHOD"] === "GET"
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->getMethod() === "GET";
    }

    /**
     * Checks whether HTTP method is PUT. if $_SERVER["REQUEST_METHOD"] === "PUT"
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->getMethod() === "PUT";
    }

    /**
     * Checks whether HTTP method is HEAD. if $_SERVER["REQUEST_METHOD"] === "HEAD"
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->getMethod() === "PATCH";
    }

    /**
     * Checks whether HTTP method is DELETE. if $_SERVER["REQUEST_METHOD"] === "DELETE"
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->getMethod() === "DELETE";
    }

    /**
     * Checks whether HTTP method is OPTIONS. if $_SERVER["REQUEST_METHOD"] === "OPTIONS"
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->getMethod() === "OPTIONS";
    }

    /**
     * Checks whether HTTP method is PURGE (Squid and Varnish support). if $_SERVER["REQUEST_METHOD"] === "PURGE"
     *
     * @return bool
     */
    public function isPurge()
    {
        return $this->getMethod() === "PURGE";
    }

    /**
     * Checks whether HTTP method is TRACE. if $_SERVER["REQUEST_METHOD"] === "TRACE"
     *
     * @return bool
     */
    public function isTrace()
    {
        return $this->getMethod() === "TRACE";
    }

    /**
     * Checks whether HTTP method is CONNECT. if $_SERVER["REQUEST_METHOD"] === "CONNECT"
     *
     * @return bool
     */
    public function isConnect()
    {
        return $this->getMethod() === "CONNECT";
    }

    /**
     * Checks whether request include attached files
     *
     * @param boolean $onlySuccessful
     * @return boolean
     */
    public function hasFiles($onlySuccessful = false)
    {
        return false;

    }

    /**
     * Gets attached files as Phalcon\Http\Request\FileInterface compatible instances
     *
     * @param bool $onlySuccessful
     * @return \Phalcon\Http\Request\FileInterface[]
     */
    public function getUploadedFiles($onlySuccessful = false)
    {
        return false;
    }

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public function getHTTPReferer()
    {

        if (isset($this->_REQUEST->server['HTTP_REFERER'])) {
            $this->_REQUEST->server['HTTP_REFERER'];
        }
        return "";
    }

    /**
     * Gets best mime/type accepted by the browser/client from $_SERVER["HTTP_ACCEPT"]
     *
     * @return string
     */
    public function getBestAccept()
    {
        return $this->_getBestQuality($this->getAcceptableContent(), "accept");
    }

    /**
     * Gets array with mime/types and their quality accepted by the browser/client from $_SERVER["HTTP_ACCEPT"]
     *
     * @return array
     */
    public function getAcceptableContent()
    {
        return $this->_getQualityHeader("HTTP_ACCEPT", "accept");
    }

    /**
     * Gets best charset accepted by the browser/client from $_SERVER["HTTP_ACCEPT_CHARSET"]
     *
     * @return string
     */
    public function getBestCharset()
    {
        return $this->_getBestQuality($this->getClientCharsets(), "charset");
    }

    /**
     * Gets charsets array and their quality accepted by the browser/client from $_SERVER["HTTP_ACCEPT_CHARSET"]
     *
     * @return array
     */
    public function getClientCharsets()
    {
        return $this->_getQualityHeader("HTTP_ACCEPT_CHARSET", "charset");
    }

    /**
     * Gets best language accepted by the browser/client from $_SERVER["HTTP_ACCEPT_LANGUAGE"]
     *
     * @return string
     */
    public function getBestLanguage()
    {
        return $this->_getBestQuality($this->getLanguages(), "language");
    }

    /**
     * Gets languages array and their quality accepted by the browser/client from _SERVER["HTTP_ACCEPT_LANGUAGE"]
     *
     * @return array
     */
    public function getLanguages()
    {
        return $this->_getQualityHeader("HTTP_ACCEPT_LANGUAGE", "language");
    }

    /**
     * Gets auth info accepted by the browser/client from $_SERVER["PHP_AUTH_USER"]
     *
     * @return array
     */
    public function getBasicAuth()
    {
        if (isset($this->_REQUEST->server['PHP_AUTH_USER']) && isset($this->_REQUEST->server['PHP_AUTH_PW'])) {
            $auth = [];
            $auth["username"] = $this->_REQUEST->server["PHP_AUTH_USER"];
            $auth["password"] = $this->_REQUEST->server["PHP_AUTH_PW"];
            return $auth;
        }

        return null;
    }

    /**
     * Gets auth info accepted by the browser/client from $_SERVER["PHP_AUTH_DIGEST"]
     *
     * @return array
     */
    public function getDigestAuth()
    {

        $auth = [];
        if (isset($this->_REQUEST->server['PHP_AUTH_DIGEST'])) {
            $digest = $this->_REQUEST->server['PHP_AUTH_DIGEST'];
            $matches = [];
            if (!preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $digest, $matches, 2)) {
                return $auth;
            }
            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $auth[$match[1]] = $match[3];
                }
            }
        }

        return $auth;
    }

}