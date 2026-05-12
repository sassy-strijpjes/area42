<?php

use App\Mail\PasswordResetLinkMail;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    public string $type = 'staff';

    #[Validate('required|email')]
    public string $email = '';

    public function mount(?string $type = null): void
    {
        $this->type = in_array($type, ['admin', 'staff'], true) ? $type : 'staff';
    }

    public function sendResetLink(): void
    {
        $this->validate();

        $table = $this->type == 'admin' ? 'admins' : 'staff';

        $user = DB::table($table)
            ->where('email', $this->email)
            ->first();

        if (! $user) {
            Flux::toast('If the email exists, we have sent a reset link.', variant: 'success');
            return;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = route("{$this->type}.reset-password", [
            'token' => $token,
            'email' => $user->email,
        ]);

        Mail::to($user->email)->send(new PasswordResetLinkMail($this->type, $resetUrl));

        Flux::toast('If the email exists, we have sent a reset link.', variant: 'success');
    }
};
