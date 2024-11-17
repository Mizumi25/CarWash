  <?php
  
  use Livewire\Volt\Component;
  use App\Models\Reservation;
  use Stripe\StripeClient;
  
  new class extends Component {
    public $reservationId;
    public $paymentReservationStatus;
    public $amount;
    public $paymentUrl;
    public $serviceName;
    public $totalAmounts;
    public $percentageRemain;
    
    
    public function mount($reservationId)
    {
        $this->reservationId = $reservationId;
        $reservation = Reservation::with(['payment', 'service', 'vehicle.vehicleType'])->findOrFail($reservationId);
        $this->paymentReservationStatus = $reservation->payment->payment_status ?? 'No Payment Found';
        $servicePrice = $reservation->service->price ?? 0;
        $vehicleTypePrice = $reservation->vehicle->vehicleType->price ?? 0;
        $currentDollarRate = 58.07;
        $totalAmount = ($servicePrice + $vehicleTypePrice) / $currentDollarRate;
        $this->totalAmounts = number_format($totalAmount, 2, '.', '');
        $partiallyPaid = $reservation->payment->amount ?? 0;
        $this->amount = number_format(($totalAmount - $partiallyPaid), 2, '.', '');
        $this->percentageRemain = number_format(($totalAmount - $partiallyPaid) / $totalAmount * 100);
        
        $this->serviceName = $reservation->service->service_name ?? 'Unknown Service';
    }
    
    public function initiateCheckout()
      {
      try {
  
          $stripe = new StripeClient(config('cashier.secret'));
          
          
          $session = $stripe->checkout->sessions->create([
              'payment_method_types' => ['card'],
              'line_items' => [[
                  'price_data' => [
                      'currency' => 'usd',
                      'product_data' => [
                          'name' => $this->serviceName,
                      ],
                      'unit_amount' => (int)($this->amount * 100), 
                  ],
                  'quantity' => 1,
              ]],
              'mode' => 'payment',
              'success_url' => route('reservation.partial', [
                  'id' => $this->reservationId,
                  'service_name' => $this->serviceName,
                  'amount' => $this->totalAmounts,
                  'payment_method' => 'stripe',
                  'payment_status' => 'fully_paid',
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

  }; ?>
  
  <div class="grid grid-cols-2 place-items-center mx-2 p-4 sm:p-8 shadow sm:rounded-lg px-[30px] {{ $mode === 'dark' ? 'bg-[#313246] text-white' : 'bg-white text-black' }} overflow-hidden shadow-sm sm:rounded-lg w-[91%] rounded-[10px]">
      @if ($paymentReservationStatus === 'partialy_paid')
       <div class="min-h-screen flex flex-col justify-center sm:py-12">
        <div class="relative py-3 sm:max-w-xl sm:mx-auto">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-300 to-blue-600 shadow-lg transform -skew-y-3 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
            <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
                <div class="max-w-md mx-auto">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-semibold">Pay for {{ $serviceName }}</h1>
                    </div>
                    
                    <div class="mb-6">
                        <div>Total Payment: {{ $totalAmounts }} </div>
                        <div>Remaining Percentage: {{ $percentageRemain }}%</div>
                        <button class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200" wire:click="initiateCheckout">Pay remaining through Stripe ${{ number_format($amount, 2, '.', '') }}</button>
    
                        <!-- Error Display -->
                        @if (session()->has('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
      @elseif ($paymentReservationStatus === 'fully_paid')
         <livewire:review-ratings.review-and-ratings :reservationId="$reservationId" />
      @else
         <h2>No further details</h2>
      @endif
  </div>
