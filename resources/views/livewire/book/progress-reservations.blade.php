  <?php
  
  use Livewire\Volt\Component;
  
  new class extends Component {
    public $reservationId;
    
    public function mount($reservationId)
    {
        $this->reservationId = $reservationId;

    }

  }; ?>
  
  <div class="grid grid-cols-2 place-items-center mx-2 p-4 sm:p-8 shadow sm:rounded-lg px-[30px] {{ $mode === 'dark' ? 'bg-[#313246] text-white' : 'bg-white text-black' }} overflow-hidden shadow-sm sm:rounded-lg w-[91%] rounded-[10px]">
    <button type="button" class="bg-indigo-500">
      <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
      </svg>
      {{ $reservationId }}
    </button>
  </div>
