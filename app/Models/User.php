<?php

namespace App\Models;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\DrupalPassword;
use Illuminate\Support\Facades\Hash;

#[Fillable([
    'name',
    'username',
    'receipt',
    'is_admin',
    'email',
    'password',
    'legacy_password',
    'fullname',
    'country',
    'is_active',
    'drupal_uid',
    'drupal_created_at',
    'drupal_last_access_at',
    'drupal_last_login_at',
])]
#[Hidden([
    'password',
    'legacy_password',
    'remember_token',
])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }
    public function checkAppPassword(string $password): bool
    {
        // ابتدا رمز Laravel را بررسی کن.
        if (
            $this->password &&
            Hash::check($password, $this->password)
        ) {
            return true;
        }
    
        // سپس رمز قدیمی Drupal را بررسی کن.
        if (
            $this->legacy_password &&
            DrupalPassword::check(
                $password,
                $this->legacy_password
            )
        ) {
            // مهاجرت خودکار رمز به Laravel.
            // چون password cast = hashed است،
            // رمز به صورت امن Hash می‌شود.
            $this->password = $password;
            $this->save();
    
            return true;
        }
    
        return false;
    } 
    public function readBooks()
    {
        return $this->belongsToMany(
            Content::class,
            'user_read_books',
            'user_id',
            'content_id'
        )->withTimestamps();
    }
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_active && $this->is_admin;
    }
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'drupal_created_at' => 'datetime',
            'drupal_last_access_at' => 'datetime',
            'drupal_last_login_at' => 'datetime',
        ];
    }
}