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

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function employees()
    {
        return $this->hasMany(CompanyEmployees::class);
    }
}
