<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New product enquiry</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2933; line-height: 1.5;">
    <h1 style="font-size: 20px;">New product enquiry</h1>

    <h2 style="font-size: 16px;">Product</h2>
    <p>
        <a href="{{ $productUrl }}">{{ $enquiry->product->title }}</a><br>
        Product ID: {{ $enquiry->product->id }}<br>
        Price: {{ $enquiry->product->price->format() }}
    </p>

    <h2 style="font-size: 16px;">Customer</h2>
    <p>
        Name: {{ $enquiry->name }}<br>
        Email: <a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a><br>
        Phone: {{ $enquiry->phone ?? 'Not provided' }}
    </p>

    <h2 style="font-size: 16px;">Message</h2>
    <p>{!! $enquiry->message !== null ? nl2br(e($enquiry->message)) : 'No message provided.' !!}</p>

    <p style="color: #616e7c; font-size: 13px;">Reply to this email to respond to the customer directly.</p>
</body>
</html>
