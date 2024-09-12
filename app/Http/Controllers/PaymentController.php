<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\PaymentMethod;
use Stripe\Customer;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function showPaymentPage()
    {
        return view('payment');
    }

    public function checkPaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        $paymentMethodId = $request->input('payment_method_id');

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $isAttached = !empty($paymentMethod->customer);

            return response()->json([
                'exists' => $isAttached,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
