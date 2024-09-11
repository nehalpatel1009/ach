<?php
use App\Http\Controllers\StripeController;

Route::prefix('stripe')->group(function () {
    Route::post('createCustomer', [StripeController::class, 'createCustomer']);
    Route::post('createPaymentIntent', [StripeController::class, 'createPaymentIntent']);
    Route::post('createSetupIntent', [StripeController::class, 'createSetupIntent']);
});
