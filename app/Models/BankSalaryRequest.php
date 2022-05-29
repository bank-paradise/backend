<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankSalaryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'salary',
        'description',
        'status',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
