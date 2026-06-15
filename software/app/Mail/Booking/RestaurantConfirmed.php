<?php

namespace App\Mail\Booking;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RestaurantConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private readonly string $guestName,
        private readonly string $date,
        private readonly string $slotStart,
        private readonly string $slotEnd,
        private readonly int $partySize,
        private readonly ?string $notes = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your table reservation is confirmed - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.restaurant-confirmed',
            with: [
                'guestName' => $this->guestName,
                'date' => Carbon::parse($this->date)->format('l, d F Y'),
                'timeStart' => Carbon::parse($this->slotStart)->format('H:i'),
                'timeEnd' => Carbon::parse($this->slotEnd)->format('H:i'),
                'partySize' => $this->partySize,
                'notes' => $this->notes,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
