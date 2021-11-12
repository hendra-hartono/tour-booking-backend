<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    // protected $fillable = ['tour_id', 'tour_date', 'status'];
    protected $guarded = ['id'];

    public function booking_passenger()
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
