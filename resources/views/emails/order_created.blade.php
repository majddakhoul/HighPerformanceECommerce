<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
</head>

<body style="margin:0; padding:0; background-color:#f6f7fb; font-family:Arial, sans-serif;">

    <!-- Container -->
    <div style="max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

        <!-- Header -->
        <div style="background:#4f46e5; padding:20px; text-align:center; color:white;">
            <h1 style="margin:0; font-size:22px;">Order Confirmed 🎉</h1>
        </div>

        <!-- Body -->
        <div style="padding:25px; color:#333;">

            <p style="font-size:16px;">Hello <strong>{{ $user->name }}</strong>,</p>

            <p style="font-size:15px; line-height:1.6;">
                Your order has been successfully created and is now being processed.
            </p>

            <!-- Order Card -->
            <div style="margin-top:20px; padding:15px; border:1px solid #eee; border-radius:10px; background:#fafafa;">
                <h3 style="margin-top:0; color:#4f46e5;">📦 Order Details</h3>

                <p style="margin:5px 0;"><strong>Order ID:</strong> #{{ $order->id }}</p>
                <p style="margin:5px 0;"><strong>Status:</strong> {{ strtoupper($order->status) }}</p>
                <p style="margin:5px 0;"><strong>Total Price:</strong> ${{ $order->total_price }}</p>
            </div>

            <!-- Invoice Card -->
            <div style="margin-top:20px; padding:15px; border:1px solid #eee; border-radius:10px; background:#fafafa;">
                <h3 style="margin-top:0; color:#10b981;">🧾 Invoice</h3>

                <p style="margin:5px 0;"><strong>Invoice ID:</strong> #{{ $invoice->id }}</p>
                <p style="margin:5px 0;"><strong>Amount:</strong> ${{ $invoice->total }}</p>
                <p style="margin:5px 0;"><strong>Date:</strong> {{ $invoice->created_at }}</p>
            </div>

            <!-- Footer Message -->
            <p style="margin-top:25px; font-size:14px; color:#555;">
                Thank you for shopping with us 🚀<br>
                If you have any questions, feel free to contact support.
            </p>

        </div>

        <!-- Footer -->
        <div style="background:#f1f5f9; text-align:center; padding:15px; font-size:12px; color:#888;">
            © {{ date('Y') }} Your Company. All rights reserved.
        </div>

    </div>

</body>
</html>