<?php

namespace Tests\Feature\Api;

use App\Enquiries\Enquiry;
use App\Mail\EnquiryReceived;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Support\Products;
use Tests\TestCase;

final class EnquiryApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFakeStore();
    }

    #[Test]
    public function submitting_an_enquiry_emails_the_sales_inbox(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', $this->validPayload())
            ->assertAccepted()
            ->assertJsonPath('message', 'Enquiry received.');

        Mail::assertSent(EnquiryReceived::class, function (EnquiryReceived $mail) {
            return $mail->hasTo('sales@example.test', 'Sales Team')
                && $mail->hasReplyTo('ada@example.com', 'Ada Lovelace')
                && $mail->enquiry->product->id === 3
                && $mail->enquiry->phone === '+44 20 7946 0000'
                && $mail->enquiry->message === 'Do you have this in blue?';
        });
        Mail::assertSentCount(1);
    }

    #[Test]
    public function phone_and_message_are_optional(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', [
            'productId' => 3,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])->assertAccepted();

        Mail::assertSent(EnquiryReceived::class, fn (EnquiryReceived $mail) => $mail->enquiry->phone === null
            && $mail->enquiry->message === null);
    }

    #[Test]
    public function it_rejects_invalid_contact_details_without_sending_email(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', [
            'productId' => 3,
            'name' => "Ada\r\nBcc: victim@example.com",
            'email' => 'not-an-email',
            'phone' => 'call me maybe',
            'message' => str_repeat('a', 2001),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'message']);

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_requires_product_name_and_email(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['productId', 'name', 'email']);

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_rejects_enquiries_for_products_that_do_not_exist(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', $this->validPayload(['productId' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['productId' => 'The selected product does not exist.']);

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_returns_503_when_the_email_cannot_be_sent(): void
    {
        Mail::shouldReceive('to')->andThrow(new TransportException('SMTP server unreachable'));

        $this->postJson('/api/enquiries', $this->validPayload())
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'We could not send your enquiry right now. Please try again shortly.');
    }

    #[Test]
    public function it_rate_limits_enquiries_per_client(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/enquiries', $this->validPayload())->assertAccepted();
        }

        $this->postJson('/api/enquiries', $this->validPayload())->assertTooManyRequests();
        Mail::assertSentCount(5);
    }

    #[Test]
    public function the_email_contains_the_enquiry_and_escapes_customer_input(): void
    {
        $mail = new EnquiryReceived(new Enquiry(
            product: Products::make(7, title: 'Gold Bracelet', priceMinorUnits: 69500),
            name: 'Ada <b>Lovelace</b>',
            email: 'ada@example.com',
            phone: null,
            message: "<script>alert('x')</script>\nSecond line",
        ));

        $mail->assertHasSubject('New enquiry: Gold Bracelet');
        $mail->assertSeeInHtml('Gold Bracelet');
        $mail->assertSeeInHtml('695.00 USD');
        $mail->assertSeeInHtml('https://shop.test/products/7', false);
        $mail->assertSeeInHtml('Ada <b>Lovelace</b>');
        $mail->assertDontSeeInHtml('<script>', false);
        $mail->assertSeeInHtml('Not provided');
        $mail->assertSeeInText("<script>alert('x')</script>");
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'productId' => 3,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+44 20 7946 0000',
            'message' => 'Do you have this in blue?',
        ], $overrides);
    }
}
