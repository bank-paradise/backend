<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Characters extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'birthday',
        'address',
        'city',
        'country',
        'zip_code',
        'phone_number',
        'height',
        'weight',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
