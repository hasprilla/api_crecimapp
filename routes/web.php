<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    echo 'PHP Version: '.phpversion();

});
