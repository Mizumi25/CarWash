<?php

use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\Service;
use App\Models\Schedule; 
use App\Models\User; 
use App\Models\Reservation;
use Livewire\Volt\Component;
use Carbon\Carbon; 
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

new class extends Component
{
    public $vehicleTypes;
    public $service;
    public $selectedServiceId = null;
    public $selectedVehicleTypeId = null;
    public $model = '';
    public $make = '';
    public $year = '';
    public $license_plate = '';
    public $color = '';
    public $mileage = '';
    public $currentDay;
    public $days = [];
    public $availableTimes;
    public $selectedDate;
    public $selectedTime;
    public $isAgreed = false;
    public $vehicles;
    public $selectedVehicleId = null;
    public $isVehicleInputEnabled = false;
    public $licensePlateError = '';
    public $disabledSchedules;
    
    public function mount(): void 
    {
        $this->vehicles = Vehicle::where('user_id', auth()->id())
        ->select('id', 'make', 'model')
        ->get() ?? collect(); 
        $this->vehicleTypes = VehicleType::select('id', 'name', 'description', 'price', 'icon')->get() ?? collect(); 
        $this->service = Service::select('id', 'service_name', 'icon', 'description', 'price', 'duration', 'is_active', 'category', 'popularity')->get() ?? collect(); 
        $this->currentDay = Carbon::now();
        $this->generateDays();
        $this->availableTimes = [
            '08:00 am', '09:00 am', '10:00 am',
            '11:00 am', '12:00 pm', '01:00 pm',
            '02:00 pm', '03:00 pm', '04:00 pm',
            '05:00 pm'
        ];
        
        $this->disabledSchedules = collect(); 
        $this->fetchDisabledSchedules();
    }
    
    public function updatedSelectedVehicleId($vehicleId)
    {
        $vehicle = Vehicle::find($vehicleId);
        if ($vehicle) {
            $this->make = $vehicle->make;
            $this->model = $vehicle->model;
            $this->year = $vehicle->year;
            $this->license_plate = $vehicle->license_plate;
            $this->color = $vehicle->color;
            $this->mileage = $vehicle->mileage;
            $this->selectedVehicleTypeId = $vehicle->vehicle_type_id; 
            $this->isVehicleInputEnabled = true; 
        }
    }
    
    public function fetchDisabledSchedules()
    {
        $ongoingReservations = Reservation::whereIn('status', ['ongoing', 'in_progress'])->with('schedule')->get();
        
        $this->disabledSchedules = collect(); 
    
        foreach ($ongoingReservations as $reservation) {
            $scheduleDate = Carbon::parse($reservation->schedule->date);
            
            $this->disabledSchedules->push([
                'date' => $scheduleDate->toDateString(),
                'time_slot' => Carbon::parse($reservation->schedule->time_slot)->format('h:i A'), 
            ]);
        }
    }
    
    public function selectVehicleType($vehicleTypeId)
    {
        $this->selectedVehicleTypeId = $vehicleTypeId;
        $vehicleType = $this->vehicleTypes->find($vehicleTypeId);
        if ($vehicleType) {
            $this->vehicleTypeName = $vehicleType->name;
            $this->vehicleTypePrice = $vehicleType->price;
            $this->isVehicleInputEnabled = true; 
        }
    }
    public function selectService($serviceId)
    {
        $this->selectedServiceId = $serviceId;
    }
    
    public function getTotalPriceProperty()
    {
        $servicePrice = optional($this->service->find($this->selectedServiceId))->price ?? 0;
        $vehicleTypePrice = optional($this->vehicleTypes->find($this->selectedVehicleTypeId))->price ?? 0;
    
        return $servicePrice + $vehicleTypePrice;
    }


    public function submitReserve()
{
    $this->validate([
        'selectedVehicleTypeId' => 'required|exists:vehicle_types,id',
        'selectedServiceId' => 'required|exists:services,id',
        'model' => 'required|string',
        'make' => 'required|string',
        'year' => 'required|integer',
        'license_plate' => 'required|string',
        'color' => 'required|string',
        'mileage' => 'nullable|integer',
        'selectedDate' => 'required|date',
        'selectedTime' => 'required|string',
        'isAgreed' => 'accepted',
    ]);

    $timeIn24HourFormat = date('H:i', strtotime($this->selectedTime));

    $existingVehicle = Vehicle::where('make', $this->make)
        ->where('model', $this->model)
        ->where('year', $this->year)
        ->where('license_plate', $this->license_plate)
        ->where('color', $this->color)
        ->where('mileage', $this->mileage)
        ->first();

    if ($existingVehicle) {
        $vehicleId = $existingVehicle->id;
    } else {
      $licensePlateExists = Vehicle::where('license_plate', $this->license_plate)
            ->where('user_id', auth()->id())
            ->exists();

        if ($licensePlateExists) {
            $this->licensePlateError = 'The license plate must be unique. Load an existing one instead';
            return; 
        } else {
            $this->licensePlateError = ''; 
        }
      
        $vehicle = Vehicle::create([
            'user_id' => auth()->id(),
            'vehicle_type_id' => $this->selectedVehicleTypeId,
            'model' => $this->model,
            'make' => $this->make,
            'year' => $this->year,
            'license_plate' => $this->license_plate,
            'color' => $this->color,
            'mileage' => $this->mileage,
        ]);
        $vehicleId = $vehicle->id;
    }
    
    $schedule = Schedule::create([
        'date' => $this->selectedDate,
        'time_slot' => $timeIn24HourFormat, 
    ]);

     $reservation = Reservation::create([
        'user_id' => auth()->id(),
        'vehicle_id' => $vehicleId,
        'service_id' => $this->selectedServiceId,
        'schedule_id' => $schedule->id, 
        'reservation_date' => Carbon::now(), 
        'status' => 'pending',
    ]);
    
    $currentUser  = auth()->user();
    $service = Service::find($this->selectedServiceId);
    $adminUsers = User::where('role', 'admin')->get(); 

    foreach ($adminUsers as $admin) {
        Notification::make()
            ->title('Reservation of ' . $currentUser ->name . ' check now')
            ->body('A Reservation has reserved at: ' . $reservation->id . 'with' . $service->service_name . '. View Pending Reservations Now.')
            //->actions([
               // Action::make('approve')
               //     ->button()
                  //  ->color('success')
                    //->action(function () use ($reservation) {
                  //      $reservation->update(['status' => 'approve']);
                   // }),
                //Action::make('decline')
                 //   ->button()
                    //->color('danger')
                   // ->action(function () use ($reservation) {
                       // $reservation->update(['status' => 'decline']);
                 //   }),
          //  ])
             ->sendToDatabase($admin); 
    }

    session()->flash('message', 'Service reserved successfully!');
    $this->reset();
    
    return redirect()->route('reservations.manage');
}
    
    public function generateDays()
    {
        $this->days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $this->currentDay->copy()->addDays($i);
            $this->days[] = $day;
        }
    }

    public function goToNextDays()
    {
        $this->currentDay->addDays(3);
        $this->generateDays();
    }

    public function goToPreviousDays()
    {
        if ($this->currentDay->greaterThan(Carbon::now())) {
            $this->currentDay->subDays(3);
            $this->generateDays();
        }
    }

    public function selectDay($day)
    {
        $this->selectedDate = $day;
        $this->selectedTime = null;
    }

    public function selectTime($time)
    {
        if ($this->selectedDate) {
            $this->selectedTime = $time;
        }
    }
    
    public function getIsSubmitEnabledProperty()
   {
    return !empty($this->selectedVehicleTypeId) &&
           !empty($this->selectedServiceId) &&
           !empty($this->model) &&
           !empty($this->make) &&
           !empty($this->year) &&
           !empty($this->license_plate) &&
           !empty($this->color) &&
           !empty($this->selectedDate) &&
           !empty($this->selectedTime);
   }
};
?>


            <div class="px-[30px] {{ $mode === 'dark' ? 'bg-[#313246] text-white' : 'bg-white text-black' }} overflow-hidden shadow-sm sm:rounded-lg w-[91%] rounded-[10px]">
                <form wire:submit.prevent="submitReserve">
                    <div class="w-full">
                       <section class="px-4 py-8">
                          <header>
                              <div class="flex flow-row gap-2 items-center">
                                  <div class="bg-[#6c8ee5] h-[60px] w-[60px] rounded-full grid place-items-center">
                                      <span class="text-white">1/4</span>
                                  </div>
                                  <h2 class="text-lg font-medium text-gray-900">{{ __('Input Vehicle') }}</h2>
                              </div>
                              <p class="mt-1 text-sm text-gray-600">{{ __("Select type and input details") }}</p>
                          </header>
                              <h3 class="text-md font-medium text-gray-900 mb-4">Available Vehicle Types</h3>
                              <div class="slider py-10 overflow-x-auto cursor-grab w-full">
                                <div class="sliderWrapper overflow-x-scroll flex flex-row space-x-8 h-[18rem]"> 
                                    @foreach ($vehicleTypes as $type)
                                        <button type="button" 
                                            class="sliderItem rounded-2xl 
                                                   {{ $selectedVehicleTypeId === $type->id ? 'bg-[#87e1ff] text-white' : 'bg-transparent text-gray-800' }} flex-none h-[15rem] w-[15rem] transition-transform duration-300 ease-in-out"
                                            wire:click="selectVehicleType({{ $type->id }})">
                                            <figure 
                                                  class="overflow-hidden bg-white/20 shadow-lg ring-1 ring-black/5 h-[15rem] w-[15rem] rounded-2xl shadow-lg">
                                                <div class="flex flex-col items-center">
                                                        <img src="{{ asset('storage/' . $type->icon) }}" class="h-24 w-24 object-cover" alt="VehicleTypeIcon" />
                                                        <p><strong>ID:</strong> {{ $type->id }}</p>
                                                        <p><strong>Name:</strong> {{ $type->name }}</p>
                                                        <p><strong>Price:</strong> {{ $type->price }}</p>
                                                </div>
                                            </figure>
                                        </button>
                                    @endforeach
                                </div>
                                </div>
                              <div class="vehicleInput">
                                  <div class="mb-6">
                                      <label for="model" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Model</label>
                                      <input wire:model.debounce.500ms="model" placeholder="Enter Model" type="text" id="model-input" class="bg-gray-50 border text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" {{ !$isVehicleInputEnabled ? 'disabled' : '' }}>
                                  </div>
                                  <div class="mb-6">
                                      <label for="make" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Make</label>
                                      <input wire:model.debounce.500ms="make" placeholder="Enter Make" type="text" id="make-input" class="bg-gray-50 border text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" {{ !$isVehicleInputEnabled ? 'disabled' : '' }}>
                                  </div>
                                  <div class="grid grid-cols-2 gap-10">
                                      <div class="mb-6">
                                          <label for="color" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Color</label>
                                          <div class="flex items-center">
                                              <input wire:model.debounce.500ms="color" type="color" id="color-input" class="cursor-pointer" style="border: none; padding: 0; width: 40px; height: 40px;" {{ !$isVehicleInputEnabled ? 'disabled' : '' }}>
                                              <input wire:model.debounce.500ms="color" placeholder="Enter Color" type="text" id="color-text-input" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ml-2" {{ !$isVehicleInputEnabled ? 'disabled' : '' }}>
                                          </div>
                                      </div>
                                       <div class="mb-6">
                                          <label for="license_plate" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">License Plate Number</label>
                                          <input wire:model.debounce.500ms="license_plate" placeholder="e.g., ABC1234" type="text" id="license_plate" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" {{ !$isVehicleInputEnabled ? 'disabled' : '' }}>
                                          <p class="mt-1 text-xs text-gray-500">Please enter the vehicle's license plate number.</p>
                                           <p class="mt-1 text-xs text-red-600">{{ $licensePlateError}}</p>
                                      </div>
                                    </div>
                                    <div>
                                        <label for="year" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Year</label>
                                        <div class="relative w-full">
                                          <div class="absolute inset-y-0 right-[1rem] flex items-center ps-3 pointer-events-none right-0">
                                             <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                              </svg>
                                          </div>
                                            <div class="mb-6">
                                                <select wire:model.debounce.500ms="year" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" {{ !$isVehicleInputEnabled ? 'disabled' : '' }}>
                                                    <option value="">Select Year</option>
                                                    @for ($i = date('Y'); $i >= 1970; $i--)
                                                        <option value="{{ $i }}">{{ $i }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-6">
                                      <label for="mileage" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mileage</label>
                                      <input wire:model.debounce.500ms="mileage" placeholder="Enter Mileage" type="text" id="mileage" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" {{ !$isVehicleInputEnabled ? 'disabled' : '' }}>
                                  </div>
                              </div>
                                      <h3 class="text-center">——————————Or—————————</h3>
                              <div class="mb-6">
                                  <label for="existing_vehicle" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select Existing Vehicle</label>
                                  <select wire:model.live="selectedVehicleId" class="bg-gray-50 border text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                                      >
                                      <option value="">Select Vehicle</option>
                                      @foreach ($vehicles as $vehicle)
                                          <option value="{{ $vehicle->id }}">{{ $vehicle->make }} {{ $vehicle->model }}</option>
                                      @endforeach
                                  </select>
                              </div>
                      </section>
                      
                      
                      <section class="px-4 py-8">
                        <header>
                            <div class="flex flow-row gap-2 items-center">
                                <div class="bg-[#6c8ee5] h-[60px] w-[60px] rounded-full grid place-items-center">
                                    <span class="text-white">2/4</span>
                                </div>
                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ __('Select Service') }}
                                </h2>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __("Select from the following") }}
                            </p>
                        </header>
                    
                        <h3 class="text-md font-medium text-gray-900 mb-4">Available Services</h3>
                        <div class="grid grid-cols-2 gap-6"> 
                            @foreach ($service->where('is_active', true) as $serve)
                                <button class="itemService transform overflow-hidden rounded-lg dark:bg-slate-800 shadow-md duration-300 hover:scale-105 hover:shadow-lg {{ $selectedServiceId === $serve->id ? 'bg-blue-300 text-white' : 'bg-white text-gray-900' }}"
                                      wire:click="selectService({{ $serve->id }})" type="button">
                                  <figure>
                                      <img src="{{ asset('storage/' . $serve->icon) }}" class="h-48 w-full object-cover object-center" alt="ServiceIcon" />
                                      <div class="p-4">
                                          <p class="mb-2 text-lg font-medium dark:text-white text-gray-900"><strong>ID:</strong> {{ $serve->id }}</p>
                                          <p class="mb-2 text-lg font-medium dark:text-white text-gray-900"><strong>Name:</strong> {{ $serve->service_name }}</p>
                                          <p class="mb-2 text-base dark:text-gray-300 text-gray-700"><strong>Description:</strong> {{ $serve->description }}</p>
                                          <p><strong>Price:</strong> {{ $serve->price }}</p>
                                          <p class="ml-auto text-base font-medium text-green-500"><strong>Category:</strong> {{ $serve->category }}</p>
                                      </div>
                                  </figure>
                              </button>
                            @endforeach
                        </div>
                    </section>
                    
                    
                    
                    
                    
                    <section class="px-4 py-8">
                      <header>
                        <div class="flex flow-row gap-2 items-center">
                            <div class="bg-[#6c8ee5] h-[60px] w-[60px] rounded-full grid place-items-center">
                                <span class="text-white">3/4</span>
                            </div>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Set Schedule') }}
                            </h2>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __("Select Schedule for reservation") }}
                        </p>
                    </header>
                    
                    <div class="max-w-md mx-auto p-4">
                        <!-- Navigation for days -->
                        <div class="flex justify-between items-center mb-4">
                            <button class="text-blue-500" wire:click="goToPreviousDays" {{ $currentDay->isSameDay(Carbon::now()) ? 'disabled' : '' }}>
                                ← Previous
                            </button>
                            <div class="text-center font-semibold">{{ $currentDay->format('F Y') }}</div>
                            <button class="text-blue-500" wire:click="goToNextDays">Next →
                            </button>
                        </div>
                
                        <!-- Display days and time slots in a 3-column grid layout -->
                        <div class="grid grid-cols-3 gap-4">
                            @foreach (array_slice($days, 0, 3) as $day)
                                <div>
                                    <!-- Day header -->
                                    <button type="button" 
                                        class="text-center cursor-pointer w-full font-semibold p-2 rounded 
                                               {{ $selectedDate == $day->toDateString() ? 'bg-blue-500 text-white' : '' }}"
                                        wire:click="selectDay('{{ $day->toDateString() }}')">
                                        {{ $day->format('D, d') }}
                                    </button>
                                    
                                    <!-- Time slots for each day -->
                                    <div class="mt-2">
                                       @foreach ($availableTimes as $time)
                                        @php
                                            $formattedDate = $day->toDateString();
                                            $formattedTime = Carbon::parse($time)->format('h:i A'); 
                                            $isDisabled = $this->disabledSchedules->contains(function ($schedule) use ($formattedDate, $formattedTime) {
                                                return $schedule['date'] === $formattedDate && $schedule['time_slot'] === $formattedTime;
                                            });
                                        @endphp
                                        <button type="button"
                                            class="w-full p-2 mb-1 rounded-lg 
                                                   {{ $selectedDate == $formattedDate && $selectedTime == $time ? 'bg-blue-500 text-white' : ($selectedDate == $formattedDate ? 'bg-gray-200' : 'bg-gray-100 text-gray-400') }}"
                                            wire:click="selectTime('{{ $time }}')"
                                            {{ $selectedDate != $formattedDate || $isDisabled ? 'disabled' : '' }}
                                        >
                                            {{ $time }}
                                        </button>
                                    @endforeach
                                  </div>
                
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>


              <section class="px-4 py-8 h-full">
                  <header>
                      <div class="flex flow-row gap-2 items-center">
                          <div class="bg-[#6c8ee5] h-[60px] w-[60px] rounded-full grid place-items-center">
                              <span class="text-white">4/4</span>
                          </div>
                          <h2 class="text-lg font-medium text-gray-900">
                              {{ __('Reservation Details') }}
                          </h2>
                      </div>
                      <p class="mt-1 text-sm text-gray-600">
                          {{ __("Review your reservation details") }}
                      </p>
                  </header>
              
                  <div class="mt-4 flex flex-col space-y-10 h-full justify-center items-center">
                      <div class="border-[#6c8ee5] border-2 h-[5rem] flex items-center pl-9 rounded-2xl w-full">
                          <p>Selected Date: <strong>{{ $selectedDate ?? '' }}</strong></p>
                      </div>
                      <div class="border-[#6c8ee5] border-2 h-[5rem] flex items-center pl-9 rounded-2xl w-full">
                          <p>Selected Time: <strong>{{ $selectedTime ?? '' }}</strong></p>
                      </div>
                      <div class="border-[#6c8ee5] border-2 h-[5rem] flex items-center pl-9 rounded-2xl w-full">
                          <p>Duration: <strong>{{ optional($service->find($selectedServiceId))->duration }} minutes</strong></p>
                      </div>
                      <div class="border-[#6c8ee5] border-2 h-[5rem] flex items-center pl-9 rounded-2xl w-full">
                          <p>Total Price: <strong>P{{ $this->totalPrice }}</strong></p>
                      </div>
                  </div>
                  
                  <div class="mt-4 w-full flex justify-center">
                      <label>
                          <input type="checkbox" wire:model="isAgreed" class="mr-2">
                          I agree to the terms and agreements.
                      </label>
                  </div>
              </section>





                    </div>
                   <div class="w-full flex justify-center items-center">
                      <button class="bg-blue-300 text-white rounded-[5px] w-[80%]" :disabled="!$wire.isSubmitEnabled || !$wire.isAgreed">{{ __('Reserve') }}</button>
                  </div>
                </form>

                @if (session()->has('message'))
                    <div class="mt-4 text-green-600">
                        {{ session('message') }}
                    </div>
                @endif
                    <style>
                      [disabled] {
                          opacity: 0.5;
                          cursor: not-allowed;
                      }
                      .sliderItem {
                          transition: transform 0.3s ease-in-out, filter 0.3s ease-in-out;
                      }
                      .sliderItem.blurred {
                          filter: blur(2px); 
                      }
                  </style>
              
                  <script>
                      document.addEventListener('DOMContentLoaded', function() {
                         const sliderWrapper = document.querySelector('.sliderWrapper');
                         const sliderItems = document.querySelectorAll('.sliderItem');
                         
                          let isSliding = false;
                          
                          
                          
                          sliderWrapper.addEventListener('mousedown', function() {
                              isSliding = true;
                              sliderItems.forEach(item => {
                                  item.style.transform = 'scale(0.8)'; 
                                  item.classList.add('blurred'); 
                              });
                          });
              
                          sliderWrapper.addEventListener('touchstart', function() {
                              isSliding = true;
                              sliderItems.forEach(item => {
                                  item.style.transform = 'scale(0.8)'; 
                                  item.classList.add('blurred'); 
                              });
                          });
                          
                          sliderWrapper.addEventListener('mouseup', function() {
                              isSliding = false;
                              sliderItems.forEach(item => {
                                  item.style.transform = 'scale(1)'; 
                                  item.classList.remove('blurred'); 
                              });
                          });
                          
                          sliderWrapper.addEventListener('touchend', function() {
                              isSliding = false;
                              sliderItems.forEach(item => {
                                  item.style.transform = 'scale(1)'; 
                                  item.classList.remove('blurred'); 
                              });
                          });
                          sliderWrapper.addEventListener('mousemove', function() {
                              if (isSliding) {
                                  sliderItems.forEach(item => {
                                      item.style.transform = 'scale(0.8)';
                                      item.classList.add('blurred'); 
                                  });
                              }
                          });
              
                          sliderWrapper.addEventListener('touchmove', function() {
                              if (isSliding) {
                                  sliderItems.forEach(item => {
                                      item.style.transform = 'scale(0.8)';
                                      item.classList.add('blurred'); 
                                  });
                              }
                          });
              
                          
                          sliderItems.forEach(item => {
                              item.addEventListener('click', function() {
                                  figure.forEach(i => {
                                      i.classList.remove('selected');
                                      i.style.transform = 'scale(1)'; 
                                  });
                                  this.classList.add('selected');
                              });
                          });
                      });
                  </script>
              

            </div>
      