<p align="center">
    <img src="https://ingenia.me/images/luthier-framework/logo.png" width="100" />
</p>

<p align="center"><strong>WARNING: Under development!</strong></p>

**Luthier Framework** is a versatile PHP micro-framework for build APIs and small websites quickly. When we say "micro" we mean REALLY micro: in fact, only Composer and a single .php file is required to start.

### Features

* Based on the Symfony components
* Easy to learn and extend
* Powerful and flexible router with middleware support
* CSRF protection
* JSON and XML response helpers
* Validator with translated error messages
* Dependency Injection container 
* Command Line Interface command creation
* Built-in plain PHP template engine with Twig and Blade integration

### Requirements

* PHP >= 7.1.8
* Composer

### Installation

Get Luthier Framework with composer:

```
composer require luthier/framework
```

### Usage

Basic example:

```php
<?php
# your_app/index.php

require 'vendor/autoload.php';

$app = new Luthier\Framework();

$app->get('/', function(){
	$this->response->write("Hello world!");
});

$app->group('api', function(){

    $this->get('/', function(){
        json_response(['message' => 'Welcome to Luthier Framework!']);
    });
    $this->get('about', function(){
        json_response(['version' => Luthier\Framework::VERSION]);
    });

});

$app->run();
```

Defining routes:

```php
$app->get('foo/', function(){
    // GET route
});

$app->post('bar/', function(){
    // POST route
});

$app->match(['get','post'], 'baz/', function(){
    // This route accept both GET and POST requests
});
```

Router parameters:

```php
$app->get('hello/{name}', function($name){
    $this->response->write("Hello $name!");
});

// Optional parameters

$app->get('about/{category?}', function($category = 'animals'){
    $this->response->write("Category: category");
});

// Regex parameters 

$app->get('website/{((en|es|fr)):lang}', function($lang){
    $this->response->write($lang);
});
```

Route middleware:

```php
// Global middleware:

$app->middleware(function($request, $response, $next){
    $response->write('Global <br>');
    $next($request, $response);
});

// Global middleware (but not assigned to any route yet)

$app->middleware('test', function($request, $response, $next){
    $response->write('Before route<br>');
    $next($request, $response);
    $response->write('After route <br>');
});

$this->get('/', function(){
    $this->response->write('Route <br>')
})->middleware('test'); // <- assign the 'test' middleware to this route

```

### Documentation

Coming soon!

### Related projects

* [Luthier CI](https://github.com/ingeniasoftware/luthier-ci): Improved routing, middleware support, authentication tools and more for CodeIgniter 3 framework
* [SimpleDocs](https://github.com/ingeniasoftware/simpledocs): Dynamic documentation library for PHP which uses Markdown files

### Donate

If you love our work,  consider support us on [Patreon](https://patreon.com/ingenia)
