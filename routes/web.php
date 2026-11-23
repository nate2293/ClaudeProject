<?php

use Illuminate\Support\Facades\Route;

// The starter kit's dashboard is replaced by the customer account area.
Route::redirect('dashboard', '/account/orders')->name('dashboard');

require __DIR__.'/shop.php';
require __DIR__.'/account.php';
require __DIR__.'/auth.php';
