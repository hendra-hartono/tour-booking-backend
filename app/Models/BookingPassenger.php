<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPassenger extends Model
{
    // protected $fillable = ['booking_id', 'passenger_id', 'special_request'];
    protected $guarded = ['id'];

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
