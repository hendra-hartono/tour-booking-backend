<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    // protected $fillable = ['given_name', 'surname', 'email', 'mobile', 'passport', 'birth_date', 'status'];
    protected $guarded = ['id'];
    public $timestamps = false;
}
