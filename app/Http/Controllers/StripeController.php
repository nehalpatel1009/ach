<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    private $secret;

    public function __construct()
    {
        $this->secret = env('STRIPE_SECRET');
        Stripe::setApiKey($this->secret);
    }

    // Create a Stripe Customer
    public function createCustomer(Request $request)
    {
        try {
            $customer = Customer::create([
                'email' => $request->email,
                'name' => $request->name,
                'phone' => $request->phone,
                'description' => 'ACH Payment Customer',
            ]);
            // Store customer ID in DB for future use
            return response()->json(['customer' => $customer]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Create Setup Intent for ACH payment
    public function createSetupIntent(Request $request)
    {
        try {
            // Retrieve customer by ID
            $customerId = $request->customer_id;
            $intent = SetupIntent::create([
                'payment_method_types' => ['us_bank_account'],
                'customer' => $customerId,
            ]);

            // Save the Setup Intent ID in your database if needed
            return response()->json(['token' => $intent->client_secret]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Create Payment Intent to deduct payment
    public function createPaymentIntent(Request $request)
    {
        try {
            $customer = Customer::retrieve($request->customer_id);
            $intent = PaymentIntent::create([
                'payment_method_types' => ['us_bank_account'],
                'payment_method' => $request->payment_method_id,
                'customer' => $customer->id,
                'confirm' => true,
                'amount' => ($request->amount) * 100,
                'currency' => 'usd',
            ]);
            return response()->json(['intent' => $intent]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
