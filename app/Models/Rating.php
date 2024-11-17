<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reservation_id',
        'rating',
        'comment',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
    
    public function service()
    {
        return $this->hasOneThrough(Service::class, Reservation::class, 'id', 'id', 'reservation_id', 'service_id');
    }
}