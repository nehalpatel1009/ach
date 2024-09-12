<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payment', [PaymentController::class, 'showPaymentPage'])->name('payment');


Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::get('register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/dashboard', [AuthController::class, 'dashboard'])->middleware('auth')->name('dashboard');

Route::post('/store-payment-method', [PaymentController::class, 'storePaymentMethod'])->name('store.payment.method');
Route::post('/check-payment-method', [PaymentController::class, 'checkPaymentMethod']);

Route::post('/create-setup-intent', [StripeController::class, 'createSetupIntent'])->middleware('auth');
Route::post('/create-payment-intent', [StripeController::class, 'createPaymentIntent'])->middleware('auth');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
