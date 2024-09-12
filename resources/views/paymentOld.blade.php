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
            alert('submit call');
            let accountHolderName = document.getElementById('account-holder-name-field').value;
            let email = document.getElementById('email-field').value;
            let amount = document.getElementById('amount-field').value;

            // Fetch setup intent client secret from the backend
            let response = await fetch('/api/stripe/createSetupIntent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ customer_id: 'cus_QpQdJbzBCCZvNy' })
            });
            console.log(response);
            let data = await response.json();
            let clientSecret = data.token;
            let stripe = Stripe('pk_test_51Pxlhj08WUq6MS2yf3yVafk9BWo0ikYYTfMLn6Wo8nk4kQ7YZowUwuHhwsBK5YBeBJIxHJyhGOwn4TwZOYNJOBKW00UzXhAZGL');
            stripe.collectBankAccountForSetup({
                clientSecret: clientSecret,
                params: {
                    payment_method_type: 'us_bank_account',
                    payment_method_data: {
                        billing_details: { name: accountHolderName, email: email }
                    },
                },
            }).then(({setupIntent, error}) => {
                if (error) {
                    console.error(error.message);
                } else if (setupIntent.status === 'requires_payment_method') {
                    alert('requires_payment_method');
                } else if (setupIntent.status === 'requires_confirmation') {
                    //alert('requires_confirmation');
                    stripe.confirmUsBankAccountSetup(clientSecret)
                    .then(({setupIntent, error}) => {
                    console.log('setup intent2 '+ JSON.stringify(setupIntent));
                    if (error) {
                    console.error(error.message);
                    // The payment failed for some reason.
                    } else if (setupIntent.status === "requires_payment_method") {
                    // Confirmation failed. Attempt again with a different payment method.
                    } else if (setupIntent.status === "succeeded") {
                        if (setupIntent.status === 'succeeded') {
                    alert('Bank account setup succeeded!');
                    fetch('/api/stripe/createPaymentIntent', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            customer_id: 'cus_QpQdJbzBCCZvNy',
                            payment_method_id: setupIntent.payment_method,
                            amount: amount
                        })
                    }).then(res => res.json()).then(result => {
                        if (result.error) {
                            console.error(result.error);
                        } else {
                            alert('Payment succeeded!');
                        }
                    });
                }
                    } else if (setupIntent.next_action?.type === "verify_with_microdeposits") {
                    // The account needs to be verified via microdeposits.
                    // Display a message to consumer with next steps (consumer waits for
                    // microdeposits, then enters a statement descriptor code on a page sent to them via email).
                    } 
                });
                }
               
            });
        });
    </script>
</body>
</html>
