<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyImage extends Model
{
    use HasUuids;

    // 🎯 Indique à Eloquent d'utiliser la table 'property_images'
    protected $table = 'property_images'; 
    
    protected $fillable = [
        'id', 
        'filename', 
        'filepath', // Chemin de stockage (ex: s3://bucket/annonces/...)
        'file_size', 
        'mime_type', 
        'user_id'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Si tu veux masquer des attributs (ex: le chemin réel du fichier)
    // protected $hidden = ['filepath']; 
    
    // ----------------------------------------------------
    // Relations
    // ----------------------------------------------------

    /**
     * Une image peut être associée à plusieurs versions d'annonces.
     */
    public function adVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            AdVersion::class, 
            'ad_version_image', // Nom de la table pivot
            'image_id',       // Clé de ce modèle dans la pivot
            'ad_version_id'   // Clé du modèle AdVersion dans la pivot
        )
        // Comme demandé, nous utilisons le pivot par défaut de Laravel
        ->withPivot('is_main') // Permet d'accéder à $image->pivot->is_main
        ->withTimestamps();
    }
}