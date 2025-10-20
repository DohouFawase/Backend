<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Equipment extends Model
{
     //
      use HasFactory ,HasUuids;

    protected $table = 'equipments';
    // 🎯 Utilisation des UUIDs
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [ 'category_id', 'name'];
    
    // Relation : L'équipement appartient à une catégorie
 public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }
    
    // Relation : Plusieurs-à-Plusieurs avec AdVersion via la table pivot
    public function adVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            AdVersion::class, 
            'ad_version_equipment', // Nom de la table pivot
            'equipment_id',         // Clé de ce modèle dans la pivot
            'ad_version_id'         // Clé du modèle AdVersion dans la pivot
        )->withTimestamps(); // Si tu as mis des timestamps dans la table pivot
    }

}
