@php($label = ucfirst($type))
<x-mail::message>
# Reset your {{ $label }} password

We received a request to reset the password for your {{ strtolower($label) }} account.

<x-mail::button :url="$resetUrl">
Reset password
</x-mail::button>

If you did not request this, you can safely ignore this email.

Thanks,<br/>
{{ config('app.name') }}
</x-mail::message>
