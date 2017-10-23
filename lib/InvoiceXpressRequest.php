<?php


/**
 * A simple PHP API wrapper for the InvoiceXpress API 2.0 .
 * Full documentation about the new version 2.0
 * @see https://developers.invoicexpress.com/docs/versions/2.0.0
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
    protected $_response = NULL;
    protected $_debug = FALSE;
    
    protected $_raw_response = "";
    protected $_json_response = "";

    /**
     * holds the query string, url-encoded with the args
     * @var string 
     */
    protected $_query_str = '';

    /**
     * 
     */
    protected $_http_method = 'GET';

    /**
     * holds if there was an unexpected HTML response
     */
    protected $_was_html_response = false;

    
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

    public function __construct($method='') {
        if ( empty($method) ) {
            if ($this->_debug) {
               print "Warning: no method specified! Use setMethod to do that!"; 
            } 
        } else {
            $this->_method = $method;
        }
    }

    /** In case you created an instance with the no-args constructor
    * You should use this method to assign the API method later
    * or in case you want to reuse the same method
    * @param The new method
    */
    public function setMethod($method) {
        $this->_method = $method;
    }

    /**
    * Set in debugging mode
    */
    public function withDebug($yes=TRUE) {
        $this->_debug = $yes;
    }

    /*
     * Set the data/arguments we're about to request with
     *
     * @return null
     */

    public function set_http_default_args() {
        list($entity,$method) = explode(".", $this->_method);
        $required_args = $this->setRequiredArgs($entity, $method);
        if ($this->_http_method == 'GET') {
            $this->_args = array_merge($required_args,$this->_args);
            $this->_query_str = http_build_query($this->_args);
        }
    }
    
    /** @param set the arguments of the request
    */
    public function set_args($data) {
        $this->_args = $data;
    }


    /** @param get the current arguments of the request
    */
    public function get_args() {
        return $this->_args;
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

    public function getRawResponse() {
        return $this->_raw_response;
    }

    /**
     * Get the original Json answe11r from the InvoiceXpress API
     * 
     */
    public function getJsonResponse() {
        return $this->_json_response;
    }

    /**
     * not using XML anymore
     * Get the generated JSON generated from the args
     *. This is useful for debugging
     * to see what you're actually sending over the wire. Call this
     * after $ie->set_args() but before your make your $ie->request()
     *
     * @return string with JSON encoded
     */

    public function getGeneratedJson() {

        $set_args_data = json_encode($this->_args,JSON_PRETTY_PRINT);

        return $set_args_data;
    }
    
    /**
     * this function is called initally on set_args to properly initialize
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
       return $required_args;
    }

    /**
     * invoiceMethods
     *
     * Handle all Invoice & Simplified invoices requests
     *
     * 
     * @param bool      $ch         cURL Handle
     * @param array     $cmd_tokens      Array with the InvoiceXpress URL tokens for the respective method 
     * @param string    $url        Built URL so far      
     * @param int       $id         InvoiceXpress invoice ID
     * 
     * @return  string
     */
    public function invoiceMethods($ch, $cmd_tokens, $url, $id) {

        list($entity,$method) = $cmd_tokens;


        switch ($method) {
            case 'create':
                $this->_http_method = 'POST';
                $url = str_replace('{{ CLASS }}', $entity, $url);
                break;
            case 'list':
                $url = str_replace('{{ CLASS }}', $entity, $url);
                break;
            case 'change-state':
                $this->_http_method = 'PUT';
                $url =  str_replace('{{ CLASS }}', "$entity/$id/$method",$url);
                break;
            case 'email-document':
                $this->_http_method = 'PUT';
                $url = str_replace('{{ CLASS }}', "$entity/$id/$method", $url);
                break;
            case 'get':
                $url = str_replace('{{ CLASS }}', "$entity/$id", $url);
                break;
            case 'update':
                $this->_http_method = 'PUT';
                $url = str_replace('{{ CLASS }}', "$entity/$id", $url);
                break;
            case 'related_documents':
                $this->_http_method = 'GET';
                $url = str_replace('{{ CLASS }}', "$entity/$id/$method" , $url);
                break;
        }



        return $url;
    }

    public function itemMethods($ch, $cmd_tokens, $url, $id) {

        list($entity,$method) = $cmd_tokens;

        switch ($method) {
            case 'list':
                $this->_http_method = 'GET';
                $url = str_replace('{{ CLASS }}', $entity, $url);
            break;
            case 'create':
                $this->_http_method = 'POST';
                $url = str_replace('{{ CLASS }}', $entity, $url);
            break;
            case 'get':
                $this->_http_method = 'GET';
                $url = str_replace('{{ CLASS }}', "$entity/$id", $url);
            break;
            case 'update':
                $this->_http_method = 'PUT';
                $url = str_replace('{{ CLASS }}', "$entity/$id", $url);
            break;
            case 'delete':
                $this->_http_method = 'DELETE';
                $url = str_replace('{{ CLASS }}', "$entity/$id", $url);
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
     * @param array     $cmd_tokens      InvoiceXpress Method to run exploded
     * @param string    $url        Built URL so far      
     * @param int       $id         InvoiceXpress invoice ID
     * 
     * @return  string
     */
    public function clientMethods($ch, $cmd_tokens, $url, $id) {

        list($entity,$method) = $cmd_tokens;


        switch ($method) {
            case 'create':
                $this->_http_method = 'POST';
                $url = str_replace('{{ CLASS }}', $entity, $url);
                break;
            case 'list':
                $this->_http_method = 'GET';
                $url = str_replace('{{ CLASS }}', $entity, $url);
                break;
            case 'get':
                $this->_http_method = 'GET';
                $url = str_replace('{{ CLASS }}', "clients/$id" , $url);
                break;
            case 'update':
                $url = str_replace('{{ CLASS }}', "clients/$id", $url);
                $this->_http_method = 'PUT';
                break;
            case 'invoices':
                $this->_http_method = 'GET';
                $url = str_replace('{{ CLASS }}', "clients/$id/$method", $url);
                break;
            case 'find-by-name': 
            case 'find-by-code':
                $this->_http_method = 'GET';
                $url = str_replace('{{ CLASS }}', "clients/$method", $url);
                break;
        }


        return $url;
    }

    /**
     * 
     * request
     *
     * Send the request over the wire
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

        if (empty($this->_method)) {
            throw new InvoiceXpressRequestException('You need to set the proper API method through setMethod()!');
        }
        
        $set_args_data = NULL;

        $url = str_replace('{{ DOMAIN }}', self::$_domain, $this->_api_url);

        $url .= "?api_key=" . self::$_token;


        $cmd_tokens = explode(".", $this->_method);

        list($entity,$method) = $cmd_tokens;

        if ($this->_debug)
            echo ("=> METHOD " . print_r($cmd_tokens, TRUE));

        $ch = curl_init();    // initialize curl handle
        //Filter correct method to run and return $url
        switch ($entity) {
            case 'invoices':
            case 'invoice_receipts':
            case 'document':
            case 'simplified_invoices':
            case 'credit_notes':
            case 'debit_notes':
                $url = $this->invoiceMethods($ch, $cmd_tokens, $url, $id);
                break;
            case 'clients':
                $url = $this->clientMethods($ch, $cmd_tokens, $url, $id);
                break;
            case 'items':
                $url = $this->itemMethods($ch, $cmd_tokens, $url, $id);
                break;
            default:
                echo ("The methods for the $entity were not implemented yet!");
                return;
                break;
        }

        if ($this->_http_method != 'GET') {
            $set_args_data = $this->getGeneratedJson();
            
            if ($this->_debug) {
                $p = print_r($set_args_data, true);
                echo("args = " . $p . "\n");
            }
        }

        $this->set_http_default_args();
        
        if ($this->_http_method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$this->_http_method);
        } else if ($this->_http_method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if ($this->_http_method == 'GET') {
            $this->setRequiredArgs($entity,$method );
        }

        
        if (!empty($this->_query_str)) {
            $url .= "&" . $this->_query_str;
        }

        echo ($this->_http_method . ' ' . $url . "\n");
        if (!empty($this->getGeneratedJson())) {
            echo $this->getGeneratedJson();
            echo "\n";
        }

        curl_setopt($ch, CURLOPT_URL, $url); // set url to set_args to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s
        curl_setopt($ch, CURLOPT_VERBOSE, $this->_debug);
        
        if ($this->_http_method != 'GET')
            curl_setopt($ch, CURLOPT_POSTFIELDS, $set_args_data); // add set_args fields
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8",
        	"Accept: application/json"));

        $result = curl_exec($ch);
        $this->$result = $result;
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->_error = 'A cURL error occured: ' . curl_error($ch);
            return;
        } else {
            curl_close($ch);
        }

        if ($this->_debug) {
            echo("http status = " . $http_status . "\n");
        }


        // detects if there was HTML on the output, which means there was some
        // error
        if ($result != strip_tags($result)) {
        	$this->_was_html_response = TRUE;
        	$this->_error = "ERROR!!!\nThere was a HTML response!\n";
        	$this->_raw_response = $result;
        	return;
        }



        if ($result && $result != " ") {
            $res = print_r($result, true);
            if ($this->_debug) {
                echo("\n\nresult string = {" . $res . "}\n\n");
            }

            $this->_raw_response = $result;

            $json_decoded = json_decode($result, true);

            $r = print_r($json_decoded, true);

            if ($this->_debug) {
                echo("\nresponse = " . $r);
            }

            $this->_json_response = $json_decoded;
        }



        $this->_success = (($http_status == '201 Created') 
        	|| ($http_status == '200 OK'
        	|| ($http_status = '202 Accepted')));


        if (isset($response['error'])) {
            $this->_success = FALSE;
            $this->_error = $response['errors'];
        }
    }

}
