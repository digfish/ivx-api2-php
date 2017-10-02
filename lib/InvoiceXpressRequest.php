<?php


/**
 * A simple PHP API wrapper for the InvoiceXpress API 2.0 .
 * Full documentation about the new version 2.0 https://developers.invoicexpress.com/docs/versions/2.0.0
 * Forked from nunomorgadinho/InvoiceXpressRequest-PHP-API
 * Slight modifications and corrections by Samuel Viana
 * More about: https://github.com/digfish/ivx-api2-php
 * PHP version 5
 *
 * @author     original by Nuno Morgadinho <nuno@widgilabs.com>
 * @author     modified and adapted by Samuel Viana <pescadordigital@gmail.com>
 * @license    MIT
 * @version    2.0
 */
class InvoiceXpressRequestException extends Exception {
    
}

class InvoiceXpressRequest {
    /*
     * The domain you need when making a request
     */

    protected static $_domain = '';

    /*
     * The API token you need when making a request
     */
    protected static $_token = '';

    /*
     * The API url we're hitting. {{ DOMAIN }} will get replaced with $domain
     * when you set InvoiceXpressRequest::init($domain, $token)
     */
    protected $_api_url = 'https://{{ DOMAIN }}.app.invoicexpress.com/{{ CLASS }}.json';
 
    /*
     * Stores the current method we're using. Example:
     * new InvoiceXpressRequest('client.create'), 'client.create' would be the method
     */
    protected $_method = '';

    /*
     * Any arguments to pass to the request
     */
    protected $_args = array();

    /*
     * Determines whether or not the request was successful
     */
    protected $_success = false;

    /*
     * Holds the error returned from our request
     */
    protected $_error = '';

    /*
     * Holds the response after our request
     */
    protected $_response = array();
    protected $_debug = FALSE;
    
    /**
     * holds the query string, url-encoded with the args
     * @var string 
     */
    protected $_query_str = '';

    /**
     * 
     */
    protected $_http_method = 'GET';
    
    /*
     * Initialize the and store the domain/token for making requests
     *
     * @param string $domain The subdomain like 'yoursite'.freshbooks.com
     * @param string $token The token found in your account settings area
     * @return null
     */

    public static function init($domain, $token) {
        self::$_domain = $domain;
        self::$_token = $token;
    }

    /*
     * Set up the request object and assign a method name
     *
     * @param array $method The method name from the API, like 'client.update' etc
     * @return null
     */

    public function __construct($method) {
        $this->_method = $method;
    }

    public function withDebug($yes) {
        $this->_debug = $yes;
    }

    /*
     * Set the data/arguments we're about to request with
     *
     * @return null
     */

    public function post($data) {
        list($entity,$method) = explode(".", $this->_method);
        $this->setRequiredArgs($entity, $method);
        $default_args = $this->_args;
        $this->_args = array_merge($default_args,$data);
        $this->_query_str = http_build_query($this->_args);
    }
    
    
    public function set_args($data) {
        $this->post($data);
    }
    

    /*
     * Determine whether or not it was successful
     *
     * @return bool
     */

    public function success() {
        return $this->_success;
    }

    /*
     * Get the error (if there was one returned from the request)
     *
     * @return string
     */

    public function getError() {
        return $this->_error;
    }

    /*
     * Get the response from the request we made
     *
     * @return array
     */

    public function getResponse() {
        return $this->_response;
    }

    /**
     * Get the original XML answer from the InvoiceXpress API
     * 
     */
    public function getResponseJson() {
        return $this->_serverAnswer;
    }

    /* @deprecated
     * not using XML anymore
     * Get the generated XML to view. This is useful for debugging
     * to see what you're actually sending over the wire. Call this
     * after $ie->post() but before your make your $ie->request()
     *
     * @return array
     */

    public function getGeneratedXML() {

        $dom = new XmlDomConstruct('1.0', 'utf-8');
        $dom->fromMixed($this->_args);
        $post_data = $dom->saveXML();

        return $post_data;
    }
    
    /**
     * this function is called initally on post to properly initialize
     * the required parameters with their default values
     * @param type $entity
     * @param type $method
     */
    private function setRequiredArgs($entity,$method) {
        $required_args = array();
       if ($entity === 'invoices') {
           if ($method === 'list') {
               $required_args = array(
                   'non_archived' => 'true',
                   'type' => array(
                       'Invoice',
                       'InvoiceReceipt',
                       'SimplifiedInvoice',
                       'CreditNotes',
                       'DebitNotes'
                   ),
                   'status' => array(
                       'draft',
                       'sent',
                       'settled',
                       'canceled',
                       'second_copy'
                   )
               );
           }
       } 
       $this->_args = $required_args;
    }

    /**
     * invoiceMethods
     *
     * Handle all Invoice & Simplified invoices requests
     *
     * 
     * @param bool      $ch         cURL Handle
     * @param array     $class      InvoiceXpress Method to run exploded
     * @param string    $url        Built URL so far      
     * @param int       $id         InvoiceXpress invoice ID
     * 
     * @return  string
     */
    public function invoiceMethods($ch, $class, $url, $id) {

        switch ($class[1]) {
            case 'create':
                curl_setopt($ch, CURLOPT_POST, 1);
                $this->_http_method = 'POST';
                $url = str_replace('{{ CLASS }}', $class[0], $url);
                break;
            case 'list':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', $class[0], $url);
                
                break;
            case 'change-state':
            case 'email-invoice':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->_http_method = 'PUT';
                $url = str_replace('{{ CLASS }}', $class[0] . "/" . $id . "/" . $class[1], $url);
                break;
            case 'get':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', $class[0] . "/" . $id, $url);
                break;
            case 'update':
                $this->_http_method = 'PUT';
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $url = str_replace('{{ CLASS }}', $class[0] . "/" . $id, $url);
                break;
        }



        return $url;
    }

    public function itemMethods($ch, $class, $url, $id, $extra = '') {
        $methodName = $class[1];
        switch ($methodName) {
            case 'list':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', $class[0], $url);
                break;
        }


        return $url;
    }

    /**
     * clientMethods
     *
     * Handle all client requests
     *
     * 
     * @param bool      $ch         cURL Handle
     * @param array     $class      InvoiceXpress Method to run exploded
     * @param string    $url        Built URL so far      
     * @param int       $id         InvoiceXpress invoice ID
     * @param string    $extra      Special case usage for adding Extra parameter GET before API_KEY
     * 
     * @return  string
     */
    public function clientMethods($ch, $class, $url, $id, $extra = '') {

        switch ($class[1]) {
            case 'create':
            case 'list':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', $class[0], $url);

                //curl_setopt($ch, CURLOPT_POST, 0);


                break;
            case 'get':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $id, $url);

                break;
            case 'update':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $url = str_replace('{{ CLASS }}', "clients/" . $id, $url);
                $this->_http_method = 'PUT';
                break;
            case 'invoices':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $id . "/" . $class[1], $url);

                break;
            case 'find-by-name':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $class[1], $url);
                $url .= "?client_name=" . $extra . "&api_key=" . self::$_token;
                break;
            case 'find-by-code':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $class[1], $url);
                $url .= "?client_code=" . $extra . "&api_key=" . self::$_token;
                break;
            case 'create-invoice':
            case 'create-cash-invoice':
            case 'create-credit-note':
            case 'create-debit-note':
                list($before, $after) = explode('-', $class[1], 2);
                $url = str_replace('{{ CLASS }}', "clients/" . $id . "/" . $before . "/" . $after, $url);

                break;
        }


        return $url;
    }

    /**
     * request
     *
     * Send the request over the wire
     *
     * 
     * @param int       $id         InvoiceXpress invoice ID
     * @param array     $extra      Special case usage for adding Extra parameter GET before API_KEY (ex: https://:screen-name.invoicexpress.net/clients/find-by-code.xml?client_code=Ni+Hao&API_KEY=XXX)
     * 
     * 
     * @return  array
     */
    public function request($id = '', $extra = '') {
        if (!self::$_domain || !self::$_token) {
            throw new InvoiceXpressRequestException('You need to call InvoiceXpressRequest::init($domain, $token) with your domain and token.');
        }
        
        $post_data = NULL;
        
        if ($this->_http_method != 'GET') {
            $post_data = $this->getGeneratedXML();
            
            if ($this->_debug) {
                $p = print_r($post_data, true);
                echo("post = " . $p . "\n");
            }
        }

        $url = str_replace('{{ DOMAIN }}', self::$_domain, $this->_api_url);

        $class = explode(".", $this->_method);

        if ($this->_debug)
            echo ("=> METHOD " . print_r($class, TRUE));

        $ch = curl_init();    // initialize curl handle
        //Filter correct method to run and return $url
        switch ($class[0]) {
            case 'invoices':
            case 'simplified_invoices':
                $url = $this->invoiceMethods($ch, $class, $url, $id);
                break;
            case 'clients':
                $url = $this->clientMethods($ch, $class, $url, $id, $extra);
                break;
            case 'items':
                $url = $this->itemMethods($ch, $class, $url, $id, $extra);
                break;
            default:
                echo ("The methods for the {$class[0]} were not implemented yet!");
                break;
        }
        
        if ($this->_http_method == 'GET' || $this->_http_method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$this->_http_method);
        } else if ($this->_http_method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }


        $this->setRequiredArgs($class[0],$class[1] );

        $url .= "?api_key=" . self::$_token;
        
        $url .= "&" . $this->_query_str;

        if ($this->_debug) {
            echo ("==> URL = " . $url . "\n");
        }
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s
        curl_setopt($ch, CURLOPT_VERBOSE, $this->_debug);
        
        if ($this->_http_method != 'GET')
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // add POST fields
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8"));

        $result = curl_exec($ch);
        $this->$result = $result;
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->_error = 'A cURL error occured: ' . curl_error($ch);
            return;
        } else {
            curl_close($ch);
        }

        // if weird simplexml error then you may have the a user with
        // a user_meta wc_ie_client_id defined that not exists in InvoiceXpress
        if ($this->_debug)
            var_dump($result);

        if ($result && $result != " ") {
            $res = print_r($result, true);
            if ($this->_debug) {
                echo("result string = {" . $res . "}");
            }

            $this->_serverAnswer = $result;

            $response = json_decode($result, true);

            $r = print_r($response, true);

            if ($this->_debug) {
                echo("response = " . $r);
            }

            $this->_response = $response;
        }

        $this->_success = (($http_status == '201 Created') || ($http_status == '200 OK'));
        if ($this->_debug) {
            echo("http status = " . $http_status . "\n");
        }

        if (isset($response['error'])) {
            $this->_error = $response['error'];
        }
    }

}
