{{-- Plain-text part: values are printed raw because nothing here is rendered as HTML. --}}
New product enquiry

PRODUCT
{!! $enquiry->product->title !!}
Product ID: {{ $enquiry->product->id }}
Price: {!! $enquiry->product->price->format() !!}
{!! $productUrl !!}

CUSTOMER
Name: {!! $enquiry->name !!}
Email: {!! $enquiry->email !!}
Phone: {!! $enquiry->phone ?? 'Not provided' !!}

MESSAGE
{!! $enquiry->message ?? 'No message provided.' !!}

Reply to this email to respond to the customer directly.
