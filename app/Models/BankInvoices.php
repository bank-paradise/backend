<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankInvoices extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmitter',
        'receiver',
        'amount',
        'description',
        'status',
    ];
}
