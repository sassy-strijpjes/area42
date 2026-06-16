<?php

namespace App\Mail\Booking;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccommodationCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $guestName,
        private readonly string $unitName,
        private readonly string $typeName,
        private readonly string $checkIn,
        private readonly string $checkOut,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your accommodation booking has been cancelled – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.accommodation-cancelled',
            with: [
                'guestName' => $this->guestName,
                'unitName'  => $this->unitName,
                'typeName'  => $this->typeName,
                'checkIn'   => Carbon::parse($this->checkIn)->format('l, d F Y'),
                'checkOut'  => Carbon::parse($this->checkOut)->format('l, d F Y'),
            ],
        );
    }

    public function attachments(): array { return []; }
}
