<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityInvitationLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'code',
        'nb_invitations',
        'invitations_limit',
        'expiration_date',
    ];

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function getExpirationDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }
}
