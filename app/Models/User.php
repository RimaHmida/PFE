<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use  Notifiable ;

    protected $fillable = [
        'nom', 'prenom', 'email', 'password', 'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'role' => 'string', // or whatever enum type you're using
    ];
}

