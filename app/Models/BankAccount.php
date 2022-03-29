<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance',
        'name',
        'type',
        'rib',
        'user_id',
        'community_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
