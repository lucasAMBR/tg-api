<?php

use App\Http\Controllers\Address\AddressController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::post('/', [AddressController::class, 'store'])->middleware('can:address.create');
    Route::get('/', [AddressController::class, 'showAuthUserAddress']);
    Route::patch('/{id}', [AddressController::class, 'update'])->middleware('can:address.update');
    Route::delete('/{id}', [AddressController::class, 'delete'])->middleware('can:address.delete');
});
