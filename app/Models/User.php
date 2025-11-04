<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasUuids,HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
       'profile_type',
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'country',
        'city',
        'profile_description',
        'profile_photo_url',
        'password',
        'badge',
        'profile_status',
        'subscription_id',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'badge' => 'boolean',
        ];
    }


    // RELATION : Un utilisateur a un abonnement actif (ou aucun)
    public function activeSubscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    // RELATION : Un utilisateur peut avoir plusieurs annonces
    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    // Relation vers le role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
