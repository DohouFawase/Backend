<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class PropertyContact extends Model
{
    //

     use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ad_version_id',
        'owner_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'message',
        'status',
        'ip_address',
        'user_agent',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Relation avec l'annonce (version)
     */
    public function adVersion()
    {
        return $this->belongsTo(AdVersion::class, 'ad_version_id');
    }

    /**
     * Relation avec le propriétaire (qui reçoit le message)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead()
    {
        if ($this->status === 'new') {
            $this->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Marquer comme répondu
     */
    public function markAsReplied()
    {
        $this->update(['status' => 'replied']);
    }

    /**
     * Archiver le message
     */
    public function archive()
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Scope pour les messages non lus
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope pour les messages d'un propriétaire
     */
    public function scopeForOwner($query, string $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}
