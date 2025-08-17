<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedFilter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'organization_id',
        'filter_type',
        'filters_config',
        'is_active',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    // Mutator for filters_config
    public function setFiltersConfigAttribute($value)
    {
        $this->attributes['filters_config'] = is_array($value) ? json_encode($value) : $value;
    }

    // Accessor for filters_config
    public function getFiltersConfigAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions(): MorphMany
    {
        return $this->morphMany(SavedItemPermission::class, 'item');
    }

    // Scopes

    public function scopeEditable($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id) // مالک فیلتر
              ->orWhereHas('permissions', function ($permQuery) use ($user) {
                  $permQuery->where('user_id', $user->id)
                           ->where('permission_type', 'edit'); // مجوز ویرایش
              });
        });
    }

    // Helper methods
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function canView($user): bool
    {
        if ($this->user_id === $user->id) return true;
        if ($this->organization_id === $user->organization_id) return true;
        
        return $this->permissions()
                   ->where('user_id', $user->id)
                   ->where('permission_type', 'view')
                   ->exists();
    }

    public function canEdit($user): bool
    {
        if ($this->user_id === $user->id) return true;
        
        return $this->permissions()
                   ->where('user_id', $user->id)
                   ->where('permission_type', 'edit')
                   ->exists();
    }

    public function canDelete($user): bool
    {
        if ($this->user_id === $user->id) return true;
        
        return $this->permissions()
                   ->where('user_id', $user->id)
                   ->where('permission_type', 'delete')
                   ->exists();
    }
}
