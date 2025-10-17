<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory, HasUuids;
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'max_rent_ads',
        'max_sale_ads',
        'visibility_level',
        'has_dashboard',
        'has_verified_badge',
        'has_multi_user_management',
        'has_priority_support',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'max_rent_ads' => 'integer',
        'max_sale_ads' => 'integer',
        'has_dashboard' => 'boolean',
        'has_verified_badge' => 'boolean',
        'has_multi_user_management' => 'boolean',
        'has_priority_support' => 'boolean',
    ];

    // RELATION : Un plan peut avoir plusieurs abonnements actifs ou passés
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
