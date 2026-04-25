<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table      = 'users';
    protected $primaryKey = 'user_id';
    public    $timestamps = true;

    /** Laravel Auth menggunakan 'username' sebagai identifier login */
    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    protected $fillable = [
        'user_id', 'username', 'email', 'password',
        'gender', 'age', 'remember_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'age'      => 'integer',
        ];
    }
}
