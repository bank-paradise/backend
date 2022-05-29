<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiInformations extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
    ];
}
