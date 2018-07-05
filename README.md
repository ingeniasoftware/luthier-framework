<p align="center">
    <img src="https://ingenia.me/images/luthier-framework/logo.png" width="100" />
</p>

<p align="center"><strong>WARNING: Under development!</strong></p>

**Luthier Framework** is a versatile PHP micro framework designed to make APIs and websites quickly. When we says "micro" we mean REALLY micro: in fact, only Composer and a single .php file is needed for start building your app. 

### Features

* Built on the well-known Symfony components
* PSR-7 compliant
* Middleware support 
* A very lightweight dependency injection container based on Pimple
* Simple yet powerful router, great for making REST APIs
* A lot of useful shortcuts for basic operations such returning JSON responses and redirections
* CLI support

### Requirements

* PHP >= 7.1.8
* Composer

### Installation

Get Luthier Framework with composer:

```
composer require luthier/framework
```

### Usage

Basic app:

```php
<?php
# your_app/index.php

require 'vendor/autoload.php';

$app = new Luthier\Framework();

$app->group('api', function(){

    $this->get('/', function(){
        $this->response->json(['message' => 'Welcome to Luthier Framework!']);
    });
    $this->get('about', function(){
        $this->response->json(['luthier_version' => Luthier\Framework::VERSION]);
    });

});

$app->run();
```

Defining routes:

```php
$app->get('path', function(){
    // GET route
});

$app->post('path', function(){
    // POST route
});

$app->match(['get','post'], 'path', function(){
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
