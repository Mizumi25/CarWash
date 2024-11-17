<?php

use Livewire\Volt\Component;
use App\Models\Reservation;
use App\Models\Service;

new class extends Component {
    public $reservations;
    public $services;
    public $selectedService = '';
    public $searchTerm = '';

    public function mount()
{
    $this->services = Service::all();
    $this->reservations = Reservation::with(['vehicle', 'service', 'schedule'])
        ->where('user_id', auth()->id()) 
        ->whereNotIn('status', ['cancelled', 'decline', 'completed'])  
        ->get();
}


    public function updatedSelectedService($value)
    {
        $this->filterReservations();
    }

    public function updatedSearchTerm()
    {
        $this->filterReservations();
    }

    private function filterReservations()
    {
        $this->reservations = Reservation::query()
            ->with(['vehicle', 'service', 'schedule'])
            ->where('user_id', auth()->id()) 
            ->when($this->selectedService, function ($query) {
                $query->where('service_id', $this->selectedService);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($query) {
                    $query->whereHas('vehicle', function ($q) {
                        $q->whereHas('vehicleType', function ($qt) {
                            $qt->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                    })
                    ->orWhereHas('service', function ($q) {
                        $q->where('service_name', 'like', '%' . $this->searchTerm . '%');
                    })
                    ->orWhereDate('reservation_date', '=', $this->searchTerm)
                    ->orWhereHas('schedule', function ($q) {
                        $q->where('time_slot', 'like', '%' . $this->searchTerm . '%');
                    });
                });
            })
            ->whereNotIn('status', ['cancelled', 'decline', 'completed'])
            ->get();
        
        Log::info('Reservations filtered: ' . count($this->reservations));
    }

    public function continueReservation($reservationId, $serviceName)
    {
        return redirect()->route('reservation.continue', [
            'id' => $reservationId,
            'service_name' => $serviceName,
        ]);
    }

    public function cancelReservation($reservationId)
    {
        $reservation = Reservation::find($reservationId);
        if ($reservation) {
            $reservation->status = 'cancelled';
            $reservation->save();
          
            $this->filterReservations(); 
        }
    }


};
?>

<div>
    <section class="mt-10">
        <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="flex items-center justify-between d p-4">
                    <div class="flex">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                    fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input  type="text" wire:model.live="searchTerm"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 "
                                placeholder="Search" required="">
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <div class="flex space-x-3 items-center">
                            <label class="w-40 text-sm font-medium text-gray-900">Service :</label>
                            <select wire:model.live="selectedService"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 ">
                                <option value="">All Services</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3">Reservation Id</th>
                                <th scope="col" class="px-4 py-3">Vehicle</th>
                                <th scope="col" class="px-4 py-3">Service</th>
                                <th scope="col" class="px-4 py-3">Reservation Status</th>
                                <th scope="col" class="px-4 py-3">Payment Status</th>
                                <th scope="col" class="px-4 py-3">Reserved Date</th>
                                <th scope="col" class="px-4 py-3">Reserved Time</th>
                                <th scope="col" class="px-4 py-3">Last update</th>
                                <th scope="col" class="px-4 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $reservation)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3">{{ $reservation->id }}</td>
                                    <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $reservation->vehicle->vehicleType->name ?? 'N/A' }} 
                                    </th>
                                    <td class="px-4 py-3">{{ $reservation->service->service_name }}</td>
                                    <td class="px-4 py-3">{{ $reservation->status }}</td>
                                    <td class="px-4 py-3">{{ $reservation->payment->payment_status ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $reservation->reservation_date }}</td>
                                    <td class="px-4 py-3">{{ $reservation->schedule->time_slot }}</td>
                                    <td class="px-4 py-3">{{ $reservation->updated_at }}</td>
                                    <td class="px-4 py-3 flex items-center justify-end space-x-2">
                                        <button wire:click="continueReservation({{ $reservation->id }}, '{{ $reservation->service->service_name }}')" class="px-3 py-1 bg-green-500 text-white rounded">
                                            <i class="fa-solid fa-right-to-bracket"></i> Continue
                                        </button>
                                        <button wire:click="cancelReservation({{ $reservation->id }})" class="px-3 py-1 bg-red-500 text-white rounded">
                                            <i class="fa-solid fa-times"></i> Cancel
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="py-4 px-3">
                    <div class="flex ">
                        <div class="flex space-x-4 items-center mb-3">
                            <label class="w-32 text-sm font-medium text-gray-900">Per Page</label>
                            <select
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 ">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>
