<?php

use Flux\Flux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {

    public string $type = 'staff';

    public string $token = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(?string $type = null, ?string $token = null, ?string $email = null): void
    {
        $this->type = in_array($type, ['admin', 'staff'], true) ? $type : 'staff';
        $this->token = $token ?? '';
        $this->email = $email ?? '';
    }

    public function resetPassword(): RedirectResponse|null
    {
        $this->validate();

        $table = $this->type == 'admin' ? 'admins' : 'staff';

        $user = DB::table($table)
            ->where('email', $this->email)
            ->first();

        $token = DB::table('password_reset_tokens')
            ->where('email', $this->email)
            ->first();

        $expires = (int) config('auth.passwords.users.expire', 60);

        $tokenIsValid = $token
            && Hash::check($this->token, $token->token)
            && $token->created_at
            && Carbon::parse($token->created_at)
                ->addMinutes($expires)
                ->greaterThanOrEqualTo(now());

        if (! $user || ! $tokenIsValid) {
            $this->addError('email', 'The reset link is invalid or has expired.');
            return null;
        }

        DB::table($table)
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($this->password),
                'updated_at' => now(),
            ]);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();

        return redirect()
            ->route("{$this->type}.login")
            ->with('toast', [
                'heading' => 'Password reset',
                'text' => 'You can now log in with your new password.',
                'variant' => 'success',
            ]);
    }
};
