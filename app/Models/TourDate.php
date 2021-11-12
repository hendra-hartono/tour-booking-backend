<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourDate extends Model
{
    // protected $fillable = ['tour_id', 'date', 'status'];
    protected $guarded = ['id'];
    public $timestamps = false;

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
