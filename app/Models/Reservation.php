<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ReservationStatus;

class Reservation extends Model
{
    /** @use HasFactory<\Database\Factories\ReservationFactory> */
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'service_id',
        'schedule_id',
        'payment_id',
        'reservation_date',
        'status',
        'decline_message',
    ];

    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
    
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    
    public function ratings()
    {
        return $this->hasMany(Rating::class); 
    }
    
    public function getVehicleTypeNameAttribute()
    {
        return $this->vehicle->vehicleType->name ?? 'N/A';
    }
}


