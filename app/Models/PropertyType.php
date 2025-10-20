<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    //
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
      
        'name',
        'icon_class',
    ];
    
    public function adVersions(): HasMany
    {
        return $this->hasMany(AdVersion::class, 'property_type_id');
    }
}
