<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyEmployees extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'user_id',
        'grade',
        'last_payment',
        'salary',
        'rib'
    ];


    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
