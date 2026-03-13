<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasPushSubscriptions, HasRoles, InteractsWithMedia, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile')->singleFile()->registerMediaConversions(function (?Media $media = null) {
            $this->addMediaConversion('thumb')->quality('10')->nonQueued();
        });

        $this->addMediaCollection('banner')->singleFile()->registerMediaConversions(function (?Media $media = null) {
            $this->addMediaConversion('thumb')->quality('10')->nonQueued();
        });
    }

    public function getAvatarUrlAttribute()
    {
        $media = $this->getFirstMedia('profile');

        // ✅ Step 1: Check if media exists and file is available
        if ($media) {
            $path = $media->getPath('thumb');

            // check if file exists in server
            if (file_exists($path)) {
                return $media->getUrl('thumb');
            }
        }

        // ✅ Step 2: Fallback to external avatar generator
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random';
    }

    public function getBannerUrlAttribute()
    {
        $media = $this->getFirstMedia('banner');

        // Check if media exists and file is available
        if ($media) {
            $path = $media->getPath();

            if (file_exists($path)) {
                return $media->getUrl();
            }
        }

        // Fallback to a default banner image
        return null;
    }

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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is currently online (has active session in last 5 minutes)
     */
    public function isOnline(): bool
    {
        return \DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>', now()->subSeconds(10)->timestamp)
            ->exists();
    }

    /**
     * Get user's last seen time from sessions table
     */
    public function getLastSeenAttribute()
    {
        $session = \DB::table('sessions')
            ->where('user_id', $this->id)
            ->orderBy('last_activity', 'desc')
            ->first();

        if ($session) {
            return \Carbon\Carbon::createFromTimestamp($session->last_activity);
        }

        return null;
    }

    /**
     * Get the user's notification preferences.
     */
    public function notificationPreferences()
    {
        return $this->hasMany(\App\Models\NotificationPreference::class);
    }

    /**
     * Get notification preference for a specific category.
     */
    public function getNotificationPreference(string $category = 'general')
    {
        return $this->notificationPreferences()
            ->where('category', $category)
            ->first() ?? $this->notificationPreferences()->create([
                'category' => $category,
            ]);
    }

    /**
     * Check if push notifications are enabled for a category.
     */
    public function isPushEnabledFor(string $category = 'general'): bool
    {
        return $this->getNotificationPreference($category)->push_enabled ?? true;
    }

    /**
     * Get all activities caused by this user.
     */
    public function activities()
    {
        return $this->morphMany(\App\Models\Activity::class, 'causer')->orderBy('created_at', 'desc');
    }

    /**
     * Get activities where this user is the subject.
     */
    public function subjectActivities()
    {
        return $this->morphMany(\App\Models\Activity::class, 'subject')->orderBy('created_at', 'desc');
    }

    /**
     * Get the user's details.
     */
    public function detail()
    {
        return $this->hasOne(UserDetail::class);
    }

    /**
     * Get conversations where user is participant one.
     */
    public function conversationsAsUserOne()
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    /**
     * Get conversations where user is participant two.
     */
    public function conversationsAsUserTwo()
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    /**
     * Get all conversations for this user.
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('last_read_at', 'is_typing', 'typing_at', 'is_blocked')
            ->withTimestamps()
            ->orderBy('last_message_at', 'desc');
    }

    /**
     * Get all messages sent by this user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get all message reactions by this user.
     */
    public function messageReactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Get all AI conversations for this user.
     */
    public function aiConversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('detail', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Get the user's purchases (as a provider).
     */
    public function purchases()
    {
        return $this->hasMany(\App\Models\Purchase::class, 'provider_id');
    }

    /**
     * Get the user's sales (as a customer).
     */
    public function sales()
    {
        return $this->hasMany(\App\Models\Sale::class, 'customer_id');
    }

    /**
     * Get the user's online orders (as a customer).
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'customer_id');
    }

    /**
     * Get the user's saved addresses.
     */
    public function addresses()
    {
        return $this->hasMany(\App\Models\UserAddress::class);
    }
}
