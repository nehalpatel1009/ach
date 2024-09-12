<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACH Payment</title>
</head>
<body>
    <h1>ACH Payment</h1>
    <form id="payment-method-form">
        <input id="account-holder-name-field" type="text" name="account_holder_name" placeholder="Account Holder Name" required>
        <input id="email-field" type="email" name="email" placeholder="Email" required>
        <input id="amount-field" type="number" name="amount" placeholder="Amount" required>
        <button id="payment-button" type="submit">Submit Payment</button>
    </form>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.getElementById('payment-method-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            let accountHolderName = document.getElementById('account-holder-name-field').value;
            let email = document.getElementById('email-field').value;
            let amount = document.getElementById('amount-field').value;

            // Fetch setup intent client secret from the backend
            let response = await fetch('/create-setup-intent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
            });

            let data = await response.json();
            let clientSecret = data.client_secret;
            let stripe = Stripe('{{ env('STRIPE_KEY') }}');

            stripe.collectBankAccountForSetup({
                clientSecret: clientSecret,
                params: {
                    payment_method_type: 'us_bank_account',
                    billing_details: {
                        name: accountHolderName,
                        email: email,
                    },
                },
            }).then(function (result) {
                if (result.error) {
                    // Handle error
                    alert(result.error.message);
                } else {
                    // Create Payment Intent
                    fetch('/create-payment-intent', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            payment_method_id: result.setupIntent.payment_method,
                            amount: amount,
                        }),
                    }).then(response => response.json()).then(data => {
                        if (data.status === 'succeeded') {
                            alert('Payment succeeded');
                        } else {
                            alert('Payment failed');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
