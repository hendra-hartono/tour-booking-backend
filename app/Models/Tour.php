<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    // protected $fillable = ['name', 'itinerary', 'status'];
    protected $guarded = ['id'];
    public $timestamps = false;

    public function tour_date()
    {
        return $this->hasMany(TourDate::class);
    }

    public function booking()
    {
        return $this->hasMany(Booking::class);
    }
}
