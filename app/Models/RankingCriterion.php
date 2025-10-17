<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingCriterion extends Model
{
    //

    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'name',
        'weight',
        'description',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    // On désactive les timestamps car ce sont des données de configuration
    public $timestamps = false;
}
