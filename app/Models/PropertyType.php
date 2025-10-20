<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    //
    use HasFactory;

    // 🎯 Utilisation des UUIDs
    public $incrementing = false;
    protected $keyType = 'string';

    // Champs que nous allons pouvoir remplir
    protected $fillable = [
        'id',
        'name',
        'icon_class',
    ];
    
    // Relation : Un Type de bien peut avoir plusieurs versions d'annonces
    public function adVersions(): HasMany
    {
        return $this->hasMany(AdVersion::class, 'property_type_id');
    }
}
