<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const LOCALES = ['en' => 'English', 'rw' => 'Kinyarwanda', 'fr' => 'Français'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'notify_email',
        'notify_sms',
        'locale',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'role' => 'tenant',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    public function serviceProvider(): HasOne
    {
        return $this->hasOne(ServiceProvider::class);
    }

    public function propertyRoles(): HasMany
    {
        return $this->hasMany(PropertyUserRole::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function tenant()
    {
        return $this->hasOne(\App\Models\Tenant::class);
    }

    public function propertyOwner(): HasOne
    {
        return $this->hasOne(PropertyOwner::class);
    }

    public function propertyManager(): HasOne
    {
        return $this->hasOne(PropertyManager::class);
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /** Phone number from whichever profile the user has (used by the SMS channel). */
    public function routeNotificationForSms($notification = null): ?string
    {
        return $this->tenant?->phone
            ?? $this->propertyManager?->phone
            ?? $this->propertyOwner?->phone;
    }

    /** Number of conversations with unread messages, for the topbar badge. */
    public function unreadConversationCount(): int
    {
        return Conversation::unreadFor($this)->count();
    }
}
