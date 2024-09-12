<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>ACH Payment</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" class="form-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h1 class="h4">ACH Payment</h1>
                    </div>
                    <div class="card-body">
                        <form id="payment-method-form" class="form">
                            <div class="form-group">
                                <label for="account-holder-name-field">Account Holder Name</label>
                                <input id="account-holder-name-field" type="text" class="form-control" name="account_holder_name" placeholder="Account Holder Name" value="{{ Auth::user()->name }}" required>
                            </div>
                            <div class="form-group">
                                <label for="email-field">Email</label>
                                <input id="email-field" type="email" class="form-control" name="email" placeholder="Email" value="{{ Auth::user()->email }}" required>
                            </div>
                            <div class="form-group">
                                <label for="amount-field">Amount</label>
                                <input id="amount-field" type="number" class="form-control" name="amount" placeholder="Amount" required>
                            </div>
                            <button id="payment-button" type="submit" class="btn btn-primary btn-block">Submit Payment</button>
                        </form>
                        <p id="payment-status" class="mt-3"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.getElementById('payment-method-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    let accountHolderName = document.getElementById('account-holder-name-field').value;
    let email = document.getElementById('email-field').value;
    let amount = document.getElementById('amount-field').value;

    // Fetch setup intent client secret from the backend
    let setupIntentResponse = await fetch('/create-setup-intent', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ payment_method_id: '' })
    });

    let setupIntentData = await setupIntentResponse.json();
    if (setupIntentResponse.status !== 200 || setupIntentData.error) {
        console.error(setupIntentData.error);
        alert('Failed to create Setup Intent');
        return;
    }

    let clientSecret = setupIntentData.client_secret;
    let stripe = Stripe('{{ env('STRIPE_KEY') }}');

    let result = await stripe.collectBankAccountForSetup({
        clientSecret: clientSecret,
        params: {
            payment_method_type: 'us_bank_account',
            payment_method_data: {
                billing_details: {
                    name: accountHolderName,
                    email: email,
                },
            },
        },
    });

    if (result.error) {
        alert(result.error.message);
        return;
    }

    // Create Payment Intent
    let paymentIntentResponse = await fetch('/create-payment-intent', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            payment_method_id: result.setupIntent.payment_method,
            amount: amount,
        })
    });

    let paymentIntentData = await paymentIntentResponse.json();
    if (paymentIntentResponse.status !== 200 || paymentIntentData.error) {
        console.error(paymentIntentData.error);
        alert('Payment failed');
        return;
    }

    if (paymentIntentData.status === 'succeeded') {
        alert('Payment succeeded');
    } else {
        alert('Payment status: ' + paymentIntentData.status);
    }
});

    </script>
</body>
</html>
