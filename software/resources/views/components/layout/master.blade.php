<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Area42</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white dark:bg-stone-800 antialiased">
        {{ $slot }}

        @persist('toast')
            <flux:toast position="top end" />
        @endpersist

        @if (session()->has('toast'))
            <script>
                document.addEventListener('livewire:navigated', () => {
                    Flux.toast(@json(session('toast')));
                }, { once: true });
            </script>
        @endif

        @livewireScripts
        @fluxScripts
    </body>
</html>
