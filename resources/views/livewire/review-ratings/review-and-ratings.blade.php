<?php

use Livewire\Volt\Component;
use App\Models\Rating;
use App\Models\Reservation;

new class extends Component {
    public $reservationId;
    public $rating;
    public $comment;
    public $currentId;
    public $reservation;
    public $hideForm;

    protected $rules = [
        'rating' => ['required', 'in:1,2,3,4,5'],
        'comment' => 'required',
    ];
    
    public function mount($reservationId)
    {
        $this->reservationId = $reservationId;
        
        if (auth()->user()) {
            $existingRating = Rating::where('user_id', auth()->user()->id)
                ->where('reservation_id', $this->reservationId)
                ->first();

            if ($existingRating) {
                $this->rating = $existingRating->rating;
                $this->comment = $existingRating->comment;
                $this->currentId = $existingRating->id;
            }
        }
    }
    
    public function rate()
    {
        $this->validate();

        $existingRating = Rating::where('user_id', auth()->user()->id)
            ->where('reservation_id', $this->reservationId) 
            ->first();

        if ($existingRating) {
            $existingRating->update([
                'rating' => $this->rating,
                'comment' => $this->comment,
                'status' => 1,
            ]);
            session()->flash('message', 'Your rating has been updated!');
        } else {
            Rating::create([
                'user_id' => auth()->user()->id,
                'reservation_id' => $this->reservationId, 
                'rating' => $this->rating,
                'comment' => $this->comment,
                'status' => 1,
            ]);
            session()->flash('message', 'Thank you for your rating!');
        }
        
        $reservation = Reservation::find($this->reservationId);
        if ($reservation) {
            $reservation->status = 'completed';
            $reservation->save();
        }
        return redirect()->route('reservations.manage');
    }
};; ?>

<div>
    <h3>Review for Reservation ID: {{ $reservationId }}</h3>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if (!$hideForm)
        <form wire:submit.prevent="rate">
            <div>
                <label for="rating">Rating:</label>
                <select wire:model="rating" id="rating">
                    <option value="">Select Rating</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                @error('rating') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="comment">Comment:</label>
                <textarea wire:model="comment" id="comment"></textarea>
                @error('comment') <span class="error">{{ $message }}</span> @enderror
            </div>

            <button type="submit">Submit Rating</button>
        </form>
    @endif
</div>
