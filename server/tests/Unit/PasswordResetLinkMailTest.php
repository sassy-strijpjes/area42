<?php

use App\Mail\PasswordResetLinkMail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

it('saves the type and reset url', function () {
    $mail = new PasswordResetLinkMail('staff', 'https://area42.nl/reset-password?token=123');

    expect($mail->type)->toBe('staff')
        ->and($mail->resetUrl)->toBe('https://area42.nl/reset-password?token=123');
});

it('has the right subject', function () {
    $mail = new PasswordResetLinkMail('staff', 'https://area42.nl/reset-password?token=123');

    $envelope = $mail->envelope();

    expect($envelope)->toBeInstanceOf(Envelope::class)
        ->and($envelope->subject)->toBe('Reset your Area 42 password');
});

it('uses the right markdown view', function () {
    $mail = new PasswordResetLinkMail('staff', 'https://area42.nl/reset-password?token=123');

    $content = $mail->content();

    expect($content)->toBeInstanceOf(Content::class)
        ->and($content->markdown)->toBe('emails.auth.password-reset-link');
});
