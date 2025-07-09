<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'profile_image', 
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password_changed_at' => 'datetime',
    ];

    protected $appends = ['profile_image_url']; // ✅ correspond à l'accessor ci-dessous

    // ✅ Génère une URL de profil (avatar par défaut si vide)
    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image
            ? asset('storage/images/profiles/' . $this->profile_image)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->prenom . ' ' . $this->nom);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
