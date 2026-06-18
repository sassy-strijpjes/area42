<?php

namespace App\Mail\Booking;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccommodationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $guestName,
        private readonly string $unitName,
        private readonly string $typeName,
        private readonly string $checkIn,
        private readonly string $checkOut,
        private readonly int    $guests,
        private readonly float  $totalPrice,
        private readonly string $paymentStatus,
        private readonly ?string $specialRequests = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your accommodation booking is confirmed – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.accommodation-confirmed',
            with: [
                'guestName'       => $this->guestName,
                'unitName'        => $this->unitName,
                'typeName'        => $this->typeName,
                'checkIn'         => Carbon::parse($this->checkIn)->format('l, d F Y'),
                'checkOut'        => Carbon::parse($this->checkOut)->format('l, d F Y'),
                'nights'          => Carbon::parse($this->checkIn)->diffInDays($this->checkOut),
                'guests'          => $this->guests,
                'totalPrice'      => number_format($this->totalPrice, 2),
                'paymentStatus'   => ucfirst($this->paymentStatus),
                'specialRequests' => $this->specialRequests,
            ],
        );
    }

    public function attachments(): array { return []; }
}
