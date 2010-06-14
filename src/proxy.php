<?php
/**
 * IceCube (ice_cube4p)
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://hugeinc.com/license/********
 *
 * FILE DOCUMENTATION
 *
 * This file contains a class and script to execute a proxy requests from AJAX
 *  scripts. If this file was live on a server at example.com/proxy.php, and we
 *  wanted to make AJAX requests to subdomain.example.com/other/resource, we'd:
 *      1. Add lines at the bottom of this file to say
 *          $proxy = new Proxy('http://subdomain.example.com');
 *          $proxy->execute();
 *      2. From our javascript, make requests to
 *          http://example.com/proxy.php?route=/other/resource
 * The heart of the functionality of this script is self-contained, reusable
 *  proxy class. This class could very easily be incorporated into an MVC
 *  framework or set of libraries.
 * 
 * @todo Finalize licensing above
 * @package IceCube
 * @copyright Copyright (c) 2010 HUGE LLC (http://hugeinc.com)
 * @license New BSD License
 * @author Kenny Katzgrau <kkatzgrau@hugeinc.com>
 */

/**
 * This class handles all of the functionality of the proxy server. The only
 *  public method is Proxy::execute(), so once the class is constructed, the
 *  only option is to execute the proxy request. It will throw exceptions when
 *  something isn't right, the message of which will be dumped to the output
 *  stream.
 *
 * There is an option to restrict requests so that they can only be made from
 *  certain hostnames or ips in the constructor
 * @author Kenny Katzgrau <kkatzgrau@hugeinc.com>
 */
class Proxy
{
    const REQUEST_METHOD_POST    = 1;
    const REQUEST_METHOD_GET     = 2;
    const REQUEST_METHOD_PUT     = 3;
    const REQUEST_METHOD_DELETE  = 4;

    /**
     * Will hold the hostname or IP address of the machin allowed to access this
     *  proxy
     * @var string
     */
    private $_allowedHostname   = NULL;

    /**
     * Will hold the host where proxy requests will be forwarded to
     * @var string
     */
    private $_forwardHost       = NULL;

    /**
     * Will hold the HTTP request method of the proxy request
     * @var string
     */
    private $_requestMethod     = NULL;

    /**
     * Will hold the cookies submitted by the client for the proxy request
     * @var string
     */
    private $_requestCookies    = NULL;

    /**
     * Will hold the body of the request submitted by the client
     * @var string
     */
    private $_requestBody       = NULL;

    /**
     * Will hold the content type of the request submitted by the client
     * @var string
     */
    private $_requestContentType= NULL;

    /**
     * Will hold the user-agent string submitted by the client
     * @var string
     */
    private $_requestUserAgent  = NULL;

    /**
     * Will hold the raw HTTP response (headers and all) sent back by the server
     *  that the proxy request was made to
     * @var string
     */
    private $_rawResponse       = NULL;

    /**
     * Will hold the response body sent back by the server that the proxy
     *  request was made to
     * @var string
     */
    private $_responseBody      = NULL;

    /**
     * Will hold parsed HTTP headers sent back by the server that the proxy
     *  request was made to in key-value form
     * @var array
     */
    private $_responseHeaders   = NULL;

    /**
     * Will hold headers in key-value array form that were sent by the client
     * @var array
     */
    private $_rawHeaders        = NULL;

    /**
     * Will hold the route for the proxy request submitted by the client in
     *  the query string's 'route' parameter
     * @var string
     */
    private $_route             = NULL;

    /**
     * Initialies the Proxy object
     * @param string $forward_host The base address that all requests will be
     *  forwarded to. Must not end in a trailing slash.
     * @param string $allowed_hostname If you want to restrict proxy requests
     *  to only come from a certain hostname or IP, set that here.
     */
    public function  __construct($forward_host, $allowed_hostname = NULL)
    {
        $this->_forwardHost     = $forward_host;
        $this->_allowedHostname = $allowed_hostname;
    }

    /**
     * Execute the proxy request. This method sets HTTP headers and write to the
     *  output stream. Make sure that no whitespace or headers have already been
     *  sent.
     */
    public function execute()
    {
        try
        {
            $this->_checkPermissions();
            $this->_gatherRequestInfo();
            $this->_makeRequest();
            $this->_parseResponse();
            $this->_buildAndExecuteProxyResponse();
        }
        catch(Exception $ex)
        {
            $this->_output("There was an error processing your request: " 
                            . $ex->getMessage()
                            . " | ". basename(__FILE__) .", Line: " . $ex->getLine());
        }
    }

    /**
     * Gather any information we need about the request and
     *  store them in the class properties
     */
    private function _gatherRequestInfo()
    {
        $this->_loadRequestMethod();
        $this->_loadRequestCookies();
        $this->_loadRequestUserAgent();
        $this->_loadRawHeaders();
        $this->_loadContentType();
        $this->_loadRoute();

        if($this->_requestMethod === self::REQUEST_METHOD_POST
            || $this->_requestMethod === self::REQUEST_METHOD_PUT)
        {
            $this->_loadRequestBody();
        }
    }

    /**
     * Get the path to where the request will be made. This will be prepended
     *  by PROXY_HOST
     * @throws Exception When there is no 'route' parameter
     */
    private function _loadRoute()
    {
        if(!key_exists('route', $_GET))
            throw new Exception("You must supply a 'route' parameter in the request");

        $this->_route = $_GET['route'];
    }

    /**
     * Get the request body raw from the PHP input stream and store it in the
     *  _requestBody property
     */
    private function _loadRequestBody()
    {
        $this->_requestBody = @file_get_contents('php://input');
    }

    /**
     * Examine the request and load the HTTP request method
     *  into the _requestMethod property
     * @throws Exception When there is no request method
     */
    private function _loadRequestMethod()
    {
        if($this->_requestMethod !== NULL) return;

        if(! key_exists('REQUEST_METHOD', $_SERVER))
            throw new Exception("Request method unknown");

        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if($method == "get")
            $this->_requestMethod = self::REQUEST_METHOD_GET;
        elseif($method == "post")
            $this->_requestMethod = self::REQUEST_METHOD_POST;
        elseif($method == "put")
            $this->_requestMethod = self::REQUEST_METHOD_PUT;
        elseif($method == "delete")
            $this->_requestMethod = self::REQUEST_METHOD_DELETE;
        else
            throw new Exception("Request method ($method) invalid");
    }

    /**
     * Loads the user-agent string into the _requestUserAgent property
     * @throws Exception When the user agent is not sent by the client
     * @todo Is the above really needed?
     */
    private function _loadRequestUserAgent()
    {
        if($this->_requestUserAgent !== NULL) return;

        if(! key_exists('HTTP_USER_AGENT', $_SERVER))
            throw new Exception("No HTTP User Agent was found");

        $this->_requestUserAgent = $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Store the cookie array into the _requestCookies
     *  property
     */
    private function _loadRequestCookies()
    {
        if($this->_requestCookies !== NULL) return;

        $this->_requestCookies = $_COOKIE;
    }

    /**
     * Load the content type into the _requestContentType property
     */
    private function _loadContentType()
    {
        $this->_loadRawHeaders();

        if(key_exists('Content-Type', $this->_rawHeaders))
            $this->_requestContentType = $this->_rawHeaders['Content-Type'];
    }

    /**
     * Load raw headers into the _rawHeaders property.
     *  This method REQUIRES APACHE
     * @throws Exception When we can't load request headers (perhaps when Apache
     *  isn't being used)
     */
    private function _loadRawHeaders()
    {
        if($this->_rawHeaders !== NULL) return;
        
        $this->_rawHeaders = getallheaders();

        if($this->_rawHeaders === FALSE)
            throw new Exception("Could not get request headers");
    }

    /**
     * Check that the proxy request is coming from the appropriate host
     *  that was set in the second argument of the constructor
     * @return void
     * @throws Exception when a client hostname is not permitted on a request
     */
    private function _checkPermissions()
    {
        if($this->_allowedHostname === NULL)
            return;

        if(key_exists('REMOTE_HOST', $_SERVER))
            $host = $_SERVER['REMOTE_HOST'];
        else
            $host = $_SERVER['REMOTE_ADDR'];

        if($this->_allowedHostname != $host)
            throw new Exception("Requests from hostname ($host) are not allowed");
    }

    /**
     * Make the proxy request using the supplied route and the base host we got
     *  in the constructor. Store the response in _rawResponse
     */
    private function _makeRequest()
    {
        $url = $this->_forwardHost . $this->_route;

        $curl_handle = curl_init($url);

        /**
         * Check to see if this is a POST request
         * @todo What should we do for PUTs? Others?
         */
        if($this->_requestMethod === self::REQUEST_METHOD_POST)
        {
            curl_setopt($curl_handle, CURLOPT_POST, true);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $this->_requestBody);
        }

        curl_setopt($curl_handle, CURLOPT_HEADER, true);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $this->_requestUserAgent);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_COOKIE, $this->_buildProxyRequestCookieString());
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $this->_generateProxyRequestHeaders());

        $this->_rawResponse = curl_exec($curl_handle);
    }

    /**
     * Parse the headers and the body out of the raw response sent back by the
     *  server. Store them in _responseHeaders and _responseBody.
     * @throws Exception When the server does not give us a valid response
     */
    private function _parseResponse()
    {
        $break   = strpos($this->_rawResponse, "\r\n\r\n");

        # Let's check to see if we recieved a header but no body
        if($break === FALSE)
        {
            $look_for = 'HTTP/';

            if(substr($this->_rawResponse, 0, strlen($look_for)))
                $break = strlen($this->_rawResponse);
            else
                throw new Exception("A valid response was not received from the host");
        }

        $header = substr($this->_rawResponse, 0, $break);
        $this->_responseHeaders = $this->_parseResponseHeaders($header);
        $this->_responseBody    = substr($this->_rawResponse, $break + 3);
    }

    /**
     * Parse out the headers from the response and store them in a key-value
     *  array and return it
     * @param string $headers A big chunk of text representing the HTTP headers
     * @return array A key-value array containing heder names and values
     */
    private function _parseResponseHeaders($headers)
    {
        $headers = str_replace("\r", "", $headers);
        $headers = explode("\n", $headers);
        $parsed  = array();

        foreach($headers as $header)
        {
            $field_end = strpos($header, ':');

            if($field_end === FALSE)
            {
                /* Cover the case where we're at the first header, the HTTP
                 *  status header
                 */
                $field = 'status';
                $value = $header;
            }
            else
            {
                $field = substr($header, 0, $field_end);
                $value = substr($header, $field_end + 1);
            }
            
            $parsed[$field] = $value;
        }

        return $parsed;
    }

    /**
     * Generate and return any headers needed to make the proxy request
     * @return array
     */
    private function _generateProxyRequestHeaders()
    {
        $headers                 = array();
        $headers['Content-Type'] = $this->_requestContentType;
        return $headers;
    }

    /**
     * From the global $_COOKIE array, rebuild the cookie string for the proxy
     *  request
     * @return string
     */
    private function _buildProxyRequestCookieString()
    {
        $cookie_string  = '';

        foreach($this->_requestCookies as $name => $value)
        {
            $value          = urlencode($value);
            $cookie_string .= "$name=$value; ";
        }

        return $cookie_string;
    }

    /**
     * Generate headers to send back to the broswer/client based on what the
     *  server sent back
     */
    private function _generateProxyResponseHeaders()
    {
        foreach($this->_responseHeaders as $name => $value)
        {
            if($name != 'status')
                header("$name: $value");
        }
    }

    /**
     * Generate the headers and send the final response to the output stream
     */
    private function _buildAndExecuteProxyResponse()
    {
        $this->_generateProxyResponseHeaders();
        $this->_output($this->_responseBody);
    }

    /**
     * A wrapper method for something like 'echo', simply to void having
     *  echo's in different parts of the code
     * @param mixed $data Data to dump to the output stream
     */
    private function _output($data)
    {
        echo $data;
    }
}

/**
 * Here's the actual script part. Comment it out or remove it if you simple want
 *  the class' functionality
 */
$proxy = new Proxy('http://sso.dev.ivillage.com/');
$proxy->execute();