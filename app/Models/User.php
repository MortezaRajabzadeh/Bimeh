<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'mobile',
        'organization_id',
        'password',
        'user_type',
        'is_active',
        'national_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'user_type', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "کاربر {$eventName} شد");
    }

    /**
     * رابطه با سازمان
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * رابطه با خانواده‌های ثبت شده
     */
    public function registeredFamilies()
    {
        return $this->hasMany(Family::class, 'registered_by');
    }

    /**
     * بررسی اینکه آیا کاربر ادمین است
     */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * بررسی اینکه آیا کاربر متعلق به خیریه است
     */
    public function isCharity(): bool
    {
        return $this->user_type === 'charity';
    }

    /**
     * بررسی اینکه آیا کاربر متعلق به بیمه است
     */
    public function isInsurance(): bool
    {
        return $this->user_type === 'insurance';
    }

    /**
     * فیلتر کاربران فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فیلتر کاربران ادمین
     */
    public function scopeAdmins($query)
    {
        return $query->where('user_type', 'admin');
    }

    /**
     * فیلتر کاربران خیریه
     */
    public function scopeCharity($query)
    {
        return $query->where('user_type', 'charity');
    }

    /**
     * فیلتر کاربران بیمه
     */
    public function scopeInsurance($query)
    {
        return $query->where('user_type', 'insurance');
    }
}
