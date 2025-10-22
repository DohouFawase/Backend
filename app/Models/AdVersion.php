<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class AdVersion extends Model
{
    /** @use HasFactory<\Database\Factories\AdVersionFactory> */
    use HasFactory, HasUuids;



   

    protected $fillable = [
    'ad_id',
        'status',
        'validated_at',
        'validated_by_id',
        'ad_type',
        'property_type_id', 
        'seo_description',
        'full_address',
        'country',
        'department',
        'city',
        'district',
        'street',
        'additional_info',
        'longitude',
        'latitude',
        'area_value',
        'area_unit',
        'unit_count',
        'construction_type',
        'description',
        'price',
        'currency',
        'commission',
        'deposit_months',
        'periodicity',
        'is_negotiable',
        'photos_json',
        'main_photo_filename',
        'video_url',
        'property_type_id',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'price' => 'decimal:2',
        'commission' => 'decimal:2',
        'is_negotiable' => 'boolean',
        'equipments' => 'array', // Pour stocker une liste d'équipements
        'photos_json' => 'array', // Pour stocker les URLs des photos
    ];

    // RELATION : La version appartient à une annonce "dossier"
    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
    
    // RELATION : L'administrateur qui a validé cette version (optionnel)
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }
public function propertyType(): BelongsTo
    {
        // 🎯 Lier AdVersion à PropertyType via la clé property_type_id
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }
    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class, 
            'ad_version_equipment', // Table pivot
            'ad_version_id',        // Clé de ce modèle dans la pivot
            'equipment_id'          // Clé du modèle cible dans la pivot
        )->withTimestamps();
    }

 public function images(): BelongsToMany
    {
        return $this->belongsToMany(
            PropertyImage::class, // Utilisation du modèle PropertyImage
            'ad_version_image', 
            'ad_version_id',
            'image_id'
        )
        ->withPivot('is_main') // Accès au champ is_main
        ->withTimestamps();
    }
    
    // ----------------------------------------------------
    // Accesseur pour l'Image Principale
    // ----------------------------------------------------

    /**
     * Relation pour récupérer l'image qui est marquée comme principale.
     */
    public function mainImage(): BelongsToMany
    {
        return $this->images()
                    ->wherePivot('is_main', true)
                    ->limit(1);
    }
    
    /**
     * Accesseur rapide pour récupérer l'objet Image principale (via $adVersion->main_image).
     */
    public function getMainImageAttribute(): ?PropertyImage
    {
        return $this->mainImage()->first();
    }
}
