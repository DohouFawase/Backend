<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Ad extends Model
{
    /** @use HasFactory<\Database\Factories\AdFactory> */
    use HasFactory, HasUuids;


    protected $fillable = [
        'user_id',
        'active_version_id',
        'global_status',
        'published_at',
        'views_count',
        'contact_count',
        'favorites_count',
        'badge_score',
        'recency_score',
        'location_score',
        'views_score',
        'final_score',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'views_count' => 'integer',
        'contact_count' => 'integer',
        'favorites_count' => 'integer',
        'badge_score' => 'float',
        'recency_score' => 'float',
        'location_score' => 'float',
        'views_score' => 'float',
        'final_score' => 'float',
    ];

    // RELATION : L'annonce appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // RELATION : L'annonce a plusieurs versions
    public function versions()
    {
        return $this->hasMany(AdVersion::class);
    }

    // RELATION : L'annonce a une version active (celle qui est en ligne)
    public function activeVersion()
    {
        return $this->belongsTo(AdVersion::class, 'active_version_id');
    }
}
