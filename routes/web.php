<?php

use Illuminate\Support\Facades\Route;
use App\Models\Reservation;
use App\Models\Payment;
use App\Livewire\Actions\Logout;

Route::view('/', 'welcome')->name('welcome');

Route::get('/logout', Logout::class)->name('logout');


Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
    
Route::view('new-reservations', 'booking')
    ->middleware(['auth', 'verified'])
    ->name('reservation.new');
    
Route::view('vehicles', 'MyVehicles')
    ->middleware(['auth', 'verified'])
    ->name('vehicles');
    

   
    
Route::get('continue-reservations/{id}/{service_name}', function ($id, $service_name) {
    $reservation = Reservation::findOrFail($id);  
    
    if ($reservation->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.'); 
    }
    
    return view('reservations', compact('reservation', 'service_name'));
})
    ->middleware(['auth', 'verified'])
    ->name('reservation.continue');
    
    
Route::get('continue-reservations/{id}/{service_name}/{amount}/{payment_method}/{payment_status}/reserved', function ($id, $service_name, $amount, $payment_method, $payment_status) {
    
    $reservation = Reservation::findOrFail($id);  
    
    if ($reservation->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }
    $payment = Payment::create([
        'amount' => $amount,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status,
    ]);
    $reservation->payment_id = $payment->id; 
    $reservation->status = 'ongoing';
    $reservation->save();
    
    $service = $reservation->service; 
    if ($service) {
        $service->increment('popularity'); 
    }

    return view('reservations', compact('reservation', 'service_name', 'amount', 'payment_method', 'payment_status'));
})->middleware(['auth', 'verified'])->name('reservation.reserved');


Route::get('continue-reservations/{id}/{service_name}/{amount}/{payment_method}/{payment_status}/partial', function ($id, $service_name, $amount, $payment_method, $payment_status) {
    
    $reservation = Reservation::findOrFail($id);  
    
    if ($reservation->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }
    $payment = $reservation->payment;

    if ($payment) {
        $payment->amount = $amount;
        $payment->payment_method = $payment_method;
        $payment->payment_status = $payment_status;
        $payment->save();
    } else {
        $payment = Payment::create([
            'amount' => $amount,
            'payment_method' => $payment_method,
            'payment_status' => $payment_status,
        ]);
        $reservation->payment_id = $payment->id;
    }
    
    $reservation->save();
    
    $service = $reservation->service; 
    if ($service) {
        $service->increment('popularity'); 
    }

    return view('reservations', compact('reservation', 'service_name', 'amount', 'payment_method', 'payment_status'));
})->middleware(['auth', 'verified'])->name('reservation.partial');





Route::get('payment/cancel/{reservationId}', function ($reservationId) {
    return redirect()->route('reservations.manage')->with('error', 'Payment was canceled.');
})->name('payment.cancel');


Route::view('manage-reservations', 'ManageReservations')
    ->middleware(['auth', 'verified'])
    ->name('reservations.manage');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
