<x-layout.master>
    <div class="min-h-screen bg-white dark:bg-zinc-900">
        <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
            <div class="mx-auto max-w-3xl px-6 py-16 text-center">
                <flux:heading size="xl" level="1" class="text-3xl! sm:text-4xl! font-bold! leading-tight!">
                    What would you like to book?
                </flux:heading>

                <flux:text size="lg" class="mt-3 text-zinc-500 dark:text-zinc-400">
                    Pick a service below and we will take you straight to your booking.
                </flux:text>
            </div>
        </div>

        <div class="mx-auto max-w-5xl px-6 py-14">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <a href="#"
                   class="group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 rounded-xl">
                    <flux:card class="h-full flex flex-col gap-5 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors duration-200">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400">
                            <flux:icon.utensils-crossed variant="outline" class="size-5" />
                        </div>

                        <div class="flex-1">
                            <flux:heading>Restaurant</flux:heading>
                            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                                Reserve a table for breakfast, lunch, or dinner. Choose your date, time, and party size.
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-1.5 text-sm font-medium text-amber-600 dark:text-amber-400 group-hover:gap-2.5 transition-all duration-150">
                            Book a table
                            <flux:icon.arrow-right variant="micro" class="size-3.5" />
                        </div>
                    </flux:card>
                </a>

                <a href="#"
                   class="group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded-xl">
                    <flux:card class="h-full flex flex-col gap-5 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors duration-200">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400">
                            <flux:icon.building-office-2 variant="outline" class="size-5" />
                        </div>

                        <div class="flex-1">
                            <flux:heading>Accommodation</flux:heading>
                            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                                Browse available rooms, choose your check-in and check-out dates, and confirm instantly.
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 group-hover:gap-2.5 transition-all duration-150">
                            Book a room
                            <flux:icon.arrow-right variant="micro" class="size-3.5" />
                        </div>
                    </flux:card>
                </a>

                <a href="#"
                   class="group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500 rounded-xl">
                    <flux:card class="h-full flex flex-col gap-5 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors duration-200">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-100 dark:bg-green-500/15 text-green-600 dark:text-green-400">
                            <flux:icon.wrench-screwdriver variant="outline" class="size-5" />
                        </div>

                        <div class="flex-1">
                            <flux:heading>Bike Rental</flux:heading>
                            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                                Rent a city or touring bike for a few hours or the whole day at your own pace.
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400 group-hover:gap-2.5 transition-all duration-150">
                            Rent a bike
                            <flux:icon.arrow-right variant="micro" class="size-3.5" />
                        </div>
                    </flux:card>
                </a>
            </div>

            <flux:separator class="my-10" />

            <div class="flex flex-wrap justify-center gap-x-10 gap-y-3">
                <div class="flex items-center gap-2">
                    <flux:icon.shield-check variant="micro" class="size-4 text-zinc-400" />
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">No account required</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon.clock variant="micro" class="size-4 text-zinc-400" />
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Instant confirmation</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon.lock-closed variant="micro" class="size-4 text-zinc-400" />
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Secure &amp; private</flux:text>
                </div>
            </div>
        </div>
    </div>
</x-layout.master>
