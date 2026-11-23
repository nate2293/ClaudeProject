<?php

use Illuminate\Support\Facades\Route;

// The starter kit's dashboard is replaced by the header's account menu; old links go to the shop.
Route::redirect('dashboard', '/')->name('dashboard');

require __DIR__.'/shop.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
