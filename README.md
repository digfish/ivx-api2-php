# The InvoiceXpress API PHP client, now ready for the new 2.0 API.

Is no more than a simple PHP wrapper for making requests with the InvoiceXpress API. It's a fork of the original [WidgiLabs/InvoiceXpressRequest-PHP-API](https://github.com/WidgiLabs/InvoiceXpressRequest-PHP-API), coded by [nunomorgadinho](https://github.com/nunomorgadinho).

Made compatible with the new API version 2.0 that now natively responds with JSON format.

This code can be perhaps very low level to the impatient, if you feel that after taking a look at it, and you are a fan of [Laravel](http://laravel.com), as I am too, I would recommend to you as an alternative to take a look at the [invoicexpress-api](https://github.com/rpsimao/invoicexpress-api) by [rpsimao](https://github.com/rpsimao).

If you intend to use this code in a project of yours, I show below some examples on how can be used.

With the following class, which extends the class in InvoiceXpressRequest.php :

```php
<?php class MyInvoiceXpressApi extends InvoiceXpressRequest
{


    public static function init($domain, $token)
    {
        parent::init($domain, $token);
        self::$isInitialized = true;
    }

    public function __construct($method)
    {
        self::init(IVX_DOMAIN,IVX_TOKEN);
        parent::__construct($method);
    }


    public function invoke($args = array(), $debug = false)
    {
        $this->post($args);
        echo "** Invoking {$this->_method} **\n ";
        $response = $this->getResponse();

        if (!$this->success()) {
            echo "Something got wrong!\n";
            echo "ERRORS:\n";
            var_dump($this->getError());
        }
        return $response;
    }
}
```


You should already have assigned values to the IVX_DOMAIN and IVX_TOKEN . If, for example you are using myFirm as the name for your company you should use that name. The IVX_TOKEN is API Key you can obtain at the InvoiceXpress backoffice in Account >> Integrations >> API .

You can then assign to them:
```php
  define('IVX_DOMAIN','myFirm');
  define('IVX_TOKEN','--- the API key with 40 chars here ----');
```
You can put these two lines on a separate file with the adequate permissions to not be world-readable and include it in the header of the file of the example code above.

Then, to invoke your new class to get data, like listing invoices, you can do:
```php
  $api = new MyInvoiceXpressApi ( 'invoices.list' );
  $response = $api->invoke($args);
```

```$args``` is an associative PHP array with the parameters you pass to the API like for example the page number:
```php
 $args['page'] = 3;
```
The API response should be in the $response variable, which contains a PHP array with the invoice data.
.

