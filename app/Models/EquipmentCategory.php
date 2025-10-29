<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str; // Utile si vous gérez la génération de l'UUID manuellement

class EquipmentCategory extends Model
{
    use HasFactory;
    
    public $incrementing = false;
    
    protected $keyType = 'string';
    protected $fillable = [
        'id', 
        'name', 
        'icon_class', 
        'sort_order'
    ];
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class, 'category_id');
    }
}