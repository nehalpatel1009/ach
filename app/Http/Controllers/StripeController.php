<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\SetupIntent;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;

class StripeController extends Controller
{
    public function createSetupIntent(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = Auth::user();

        if (!$user->stripe_customer_id) {
            $customer = \Stripe\Customer::create([
                'email' => $user->email,
            ]);
            $user->stripe_customer_id = $customer->id;
            $user->save();
        }

        if ($request->payment_method_id) {
            $newPaymentMethod = PaymentMethod::retrieve($request->payment_method_id);

            $existingPaymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'us_bank_account'
            ]);

            foreach ($existingPaymentMethods->data as $paymentMethod) {
                if ($paymentMethod->us_bank_account->fingerprint === $newPaymentMethod->us_bank_account->fingerprint) {
                    return response()->json(['message' => 'Payment method already exists.'], 200);
                }
            }
        }
        $setupIntent = SetupIntent::create([
            'customer' => $user->stripe_customer_id,
            'payment_method_types' => ['us_bank_account'],
        ]);

        return response()->json(['client_secret' => $setupIntent->client_secret]);
    }

    public function createPaymentIntent(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = Auth::user();

        try {
            // Retrieve the new payment method from the request
            $newPaymentMethod = PaymentMethod::retrieve($request->payment_method_id);

            // Retrieve existing payment methods for the customer
            $existingPaymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'us_bank_account'
            ]);

            $isDuplicate = false;

            // Check if this payment method is already attached based on the fingerprint
            foreach ($existingPaymentMethods->data as $paymentMethod) {
                if (isset($paymentMethod->us_bank_account->fingerprint) &&
                    $paymentMethod->us_bank_account->fingerprint === $newPaymentMethod->us_bank_account->fingerprint) {
                    // If a match is found, set $newPaymentMethod to the existing one
                    $isDuplicate = true;
                    $newPaymentMethod = $paymentMethod; // Reuse the existing payment method
                    break;
                }
            }

            if (!$isDuplicate) {
                $newPaymentMethod->attach(['customer' => $user->stripe_customer_id]);
            }

            // Proceed to create the payment intent, whether it's a new or reused payment method
            $paymentIntent = PaymentIntent::create([
                'customer' => $user->stripe_customer_id,
                'amount' => $request->amount * 100, // Convert amount to cents
                'currency' => 'usd',
                'payment_method' => $newPaymentMethod->id,
                'payment_method_types' => ['us_bank_account'],
                'off_session' => true,
                'confirm' => true,
                'mandate_data' => [
                    'customer_acceptance' => [
                        'type' => 'online',
                        'online' => [
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->header('User-Agent')
                        ],
                    ],
                ],
            ]);

            // Optionally, log the payment in your database
            $user->payments()->create([
                'payment_method_id' => $newPaymentMethod->id,
                'amount' => $request->amount,
                'status' => $paymentIntent->status,
            ]);

            return response()->json(['status' => $paymentIntent->status]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
