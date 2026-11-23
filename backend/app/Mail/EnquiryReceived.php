<?php

namespace App\Mail;

use App\Enquiries\Enquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to the sales inbox when a customer enquires about a product.
 * Replying to it goes straight to the customer.
 */
final class EnquiryReceived extends Mailable
{
    public function __construct(public readonly Enquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
            subject: 'New enquiry: '.$this->enquiry->product->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.enquiry-received',
            text: 'mail.enquiry-received-text',
            with: [
                'productUrl' => config('catalogue.storefront_url').'/products/'.$this->enquiry->product->id,
            ],
        );
    }
}
