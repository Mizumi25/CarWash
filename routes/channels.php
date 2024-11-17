<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin-notifications', function ($user) {
    return true; 
});

Broadcast::channel('reservation.{reservationId}', function ($user, $reservationId) {
    $reservation = Reservation::find($reservationId);
    return $reservation && ($reservation->user_id === $user->id || $user->role === 'admin');
});
