<x-layout.staff
    pageTitle="Edit restaurant booking"
    :user="user()"
>
    <livewire:forms.bookings.restaurant.edit :booking="$booking" />
</x-layout.staff>
