<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Continue Carwash Reservation') }}
        </h2>
        
        <p>Reservation ID: {{ $reservation->id }} -   Service: {{ $service_name }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if($reservation->status === 'pending')
                <livewire:book.pending-reservations :reservationId="$reservation->id" />
            @elseif($reservation->status === 'approve')
                <livewire:book.payment-reservations :reservationId="$reservation->id" />
            @elseif($reservation->status === 'ongoing')
                <livewire:book.confirm-reservations :reservationId="$reservation->id" />
            @elseif($reservation->status === 'in_progress')
                <livewire:book.progress-reservations :reservationId="$reservation->id" />
            @elseif($reservation->status === 'done')
                <livewire:book.done-reservations :reservationId="$reservation->id" />
            @else
                <p>No actions available for this reservation status.</p>
            @endif
        </div>
    </div>
</x-app-layout>