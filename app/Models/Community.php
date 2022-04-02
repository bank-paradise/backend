<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'limit_of_members',
        'starting_amout',
        'starting_message',
        'currency',
    ];

    public function invitations()
    {
        return $this->hasMany(CommunityInvitation::class);
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}
