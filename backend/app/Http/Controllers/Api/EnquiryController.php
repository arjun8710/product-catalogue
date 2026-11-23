<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\CatalogueService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Mail\EnquiryReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class EnquiryController extends Controller
{
    public function __invoke(StoreEnquiryRequest $request, CatalogueService $catalogue): JsonResponse
    {
        // Look the product up server-side rather than trusting a title sent by the client.
        $product = $catalogue->product($request->integer('productId'))
            ?? throw ValidationException::withMessages(['productId' => 'The selected product does not exist.']);

        try {
            // The mail driver (SMTP, SES, Postmark, ...) is chosen by MAIL_MAILER in the environment.
            $salesInbox = new Address(config('catalogue.sales_inbox.address'), config('catalogue.sales_inbox.name'));

            Mail::to($salesInbox)->send(new EnquiryReceived($request->toEnquiry($product)));
        } catch (TransportExceptionInterface $e) {
            report($e);

            return response()->json([
                'message' => 'We could not send your enquiry right now. Please try again shortly.',
            ], 503);
        }

        return response()->json(['message' => 'Enquiry received.'], 202);
    }
}
