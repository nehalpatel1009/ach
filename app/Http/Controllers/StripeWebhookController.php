<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\PaymentIntent;
use App\Models\Payment;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        \Log::info('Webhook');
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Webhook Error: '.$e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook Error: '.$e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            
            $payment = Payment::where('payment_method_id', $paymentIntent->payment_method)->first();
            if ($payment) {
                $payment->status = 'succeeded';
                $payment->save();
            }
        } elseif ($event->type === 'payment_intent.payment_failed') {
            $paymentIntent = $event->data->object;
            $payment = Payment::where('payment_method_id', $paymentIntent->payment_method)->first();
            if ($payment) {
                $payment->status = 'failed';
                $payment->save();
            }
        }

        return response()->json(['status' => 'success']);
    }
}
