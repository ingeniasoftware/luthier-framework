<?php

use Luthier\Route;

Route::namespace('App\Controllers', function(){

    //
    // (This is the main route namespace of your application, start adding routes here)
    //

    Route::get('/', ['uses' => 'Foo@externo']);

});



