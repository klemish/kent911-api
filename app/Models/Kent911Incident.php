<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kent911Incident extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'created_at',
        'located_at',
        'description',
        'agency',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'location',
    ];

    /**
     * Get Latitude and Longitude.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function getLocationAttribute()
    {
        return \App\Models\Kent911Location::where('located_at', '=', $this->located_at)->first();
    }
}
