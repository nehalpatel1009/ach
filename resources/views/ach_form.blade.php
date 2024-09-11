<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
 <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>
</head>
<body>
    

    <div class="container">
        <h2>ACH Direct Debit Payment</h2>

        <form id="ach-form" action="{{ route('ach.submit') }}" method="post">
            @csrf

            <input type="email" id="email" name="email" placeholder="Email" required>
            
            <div id="payment-element">
                <!-- Stripe will insert the ACH form here -->
            </div>
            
            <button type="submit" id="submit-button">Submit Payment</button>
        </form>
    </div>
    </body>
</html>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const clientSecret = "{{$clientSecret}}";
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements();

        const paymentElement = elements.create('payment', {
            clientSecret: clientSecret,
        });
        paymentElement.mount('#payment-element');

        const form = document.getElementById('ach-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const { setupIntent, error } = await stripe.confirmSetup({
                elements,
                confirmParams: {
                    return_url: "{{ route('ach.submit') }}",
                },
            });

            if (error) {
                // Show error to your customer
                alert(error.message);
            } else {
                form.submit();
            }
        });
    </script>
