<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLocations extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ipv4',
        'country_code',
        'country_name',
        'city',
        'postal',
        'latitude',
        'longitude',
        'state',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
