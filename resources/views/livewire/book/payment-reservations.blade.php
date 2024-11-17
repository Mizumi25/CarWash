<?php

use Livewire\Volt\Component;
use App\Models\Reservation;
use Stripe\StripeClient;

new class extends Component {
    public $amount;
    public $reservationId;
    public $paymentOption;
    public $paymentUrl;
    public $serviceName;

    public function mount($reservationId)
    {
        $this->reservationId = $reservationId;

        $reservation = Reservation::with(['service', 'vehicle.vehicleType'])->findOrFail($reservationId);
        
        $servicePrice = $reservation->service->price ?? 0;
        $vehicleTypePrice = $reservation->vehicle->vehicleType->price ?? 0;
        $currentDollarRate = 58.07;
        $totalAmount = ($servicePrice + $vehicleTypePrice) / $currentDollarRate;
        $this->amount = number_format($totalAmount, 2, '.', '');
        $this->paymentOption = 'full';

        $this->serviceName = $reservation->service->service_name ?? 'Unknown Service';
    }

    public function updatedPaymentOption($value)
    {
        $reservation = Reservation::with(['service', 'vehicle.vehicleType'])->findOrFail($this->reservationId);
        $servicePrice = $reservation->service->price ?? 0;
        $vehicleTypePrice = $reservation->vehicle->vehicleType->price ?? 0;
        $currentDollarRate = 58.07;
        $totalAmount = ($servicePrice + $vehicleTypePrice) / $currentDollarRate;
        
        if ($value === 'full') {
            $this->amount = $totalAmount;
        } else {
            $percentage = (float)$value;
            $this->amount = ($totalAmount * $percentage) / 100;
        }
        
        $this->formattedAmount = number_format($this->amount, 2, '.', '');
    }

    
      public function initiateCheckout()
      {
      try {
  
          $stripe = new StripeClient(config('cashier.secret'));
          
          $paymentStatus = (float)$this->paymentOption == 100 ? 'fully_paid' : 'partialy_paid';
          
          $session = $stripe->checkout->sessions->create([
              'payment_method_types' => ['card'],
              'line_items' => [[
                  'price_data' => [
                      'currency' => 'usd',
                      'product_data' => [
                          'name' => $this->serviceName,
                      ],
                      'unit_amount' => (int)($this->amount * 100), // Amount in cents
                  ],
                  'quantity' => 1,
              ]],
              'mode' => 'payment',
              'success_url' => route('reservation.reserved', [
                  'id' => $this->reservationId,
                  'service_name' => $this->serviceName,
                  'amount' => $this->amount,
                  'payment_method' => 'stripe',
                  'payment_status' => $paymentStatus,
              ]),
              'cancel_url' => route('payment.cancel', [
                  'reservationId' => $this->reservationId
              ]),
          ]);
  
          $this->paymentUrl = $session->url;
          $this->redirect($this->paymentUrl);
      } catch (\Exception $e) {
          session()->flash('error', 'Failed to initiate payment: ' . $e->getMessage());
      }
    }
};
?>


 <div class="grid grid-cols-2 place-items-center mx-2 p-4 sm:p-8 shadow sm:rounded-lg px-[30px] {{ $mode === 'dark' ? 'bg-[#313246] text-white' : 'bg-white text-black' }} overflow-hidden shadow-sm sm:rounded-lg w-[91%] rounded-[10px]">
    <ol class="relative text-gray-500 border-s border-gray-200 dark:border-gray-700 dark:text-gray-400">                  
        <li class="mb-10 ms-6">            
            <span class="absolute flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full -start-4 ring-4 ring-white dark:ring-gray-900 dark:bg-green-900">
                4/5
            </span>
            <h3 class="font-medium leading-tight">Personal Info</h3>
            <p class="text-sm">Currently Pending</p>
        </li>
        <li class="mb-10 ms-6">
            <span class="absolute flex items-center justify-center w-8 h-8 bg-green-200 rounded-full -start-4 ring-4 ring-white dark:ring-gray-900 dark:bg-blue-900">
                5/5
            </span>
            <h3 class="font-medium leading-tight">Account Info</h3>
            <p class="text-sm">Pay total amount here</p>
        </li>
        <li class="mb-10 ms-6">
            <span class="absolute flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full -start-4 ring-4 ring-white dark:ring-gray-900 dark:bg-gray-700">
               Complete
            </span>
            <h3 class="font-medium leading-tight">Review</h3>
            <p class="text-sm">Step details here</p>
        </li>
    </ol> 
    
    
  <div class="min-h-screen flex flex-col justify-center sm:py-12">
    <div class="relative py-3 sm:max-w-xl sm:mx-auto">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-300 to-blue-600 shadow-lg transform -skew-y-3 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
        <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
            <div class="max-w-md mx-auto">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-semibold">Pay for {{ $serviceName }}</h1>
                </div>
                
                <div class="mb-6">
                    <label class="block mb-2 text-sm font-medium text-gray-700" for="paymentOption">Select Payment Option:</label>
                    <div class="relative">
                        <i class="absolute left-0 top-[50%] transform translate-y-[-50%] ml-3 text-gray-500 fa-solid fa-percent"></i>
                        <select class="w-full" id="paymentOption" wire:model.live="paymentOption">
                            <option value="full">Pay Full Amount</option>
                            <option value="50">Pay 50%</option>
                            <option value="55">Pay 55%</option>
                            <option value="60">Pay 60%</option>
                            <option value="65">Pay 65%</option>
                            <option value="70">Pay 70%</option>
                            <option value="75">Pay 75%</option>
                            <option value="80">Pay 80%</option>
                            <option value="85">Pay 85%</option>
                            <option value="90">Pay 90%</option>
                            <option value="95">Pay 95%</option>
                        </select>
                    </div>
                    <button class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200" wire:click="initiateCheckout">Proceed to Pay Stripe ${{ number_format($amount, 2, '.', '') }}</button>

                    <!-- Error Display -->
                    @if (session()->has('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

