<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'community_id',
        'community_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function locations()
    {
        return $this->hasMany(UserLocations::class);
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function invitations()
    {
        return $this->hasMany(CommunityInvitation::class);
    }

    public function personnalAccount()
    {
        return $this->hasOne(BankAccount::class)->where('type', 'personnal');
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function cashAccount()
    {
        return $this->hasOne(BankAccount::class)->where('type', 'cash');
    }

    public function character()
    {
        return $this->hasOne(Characters::class);
    }
}
