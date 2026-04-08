<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking->load(['session.event', 'tickets.zone']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmació de la teva reserva - QueueLy',
        );
    }

    public function content(): Content
    {
        $qrService = app(\App\Services\QrService::class);
        $qrImage = $qrService->generate($this->booking);

        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'qrImage' => $qrImage,
            ],
        );
    }
}