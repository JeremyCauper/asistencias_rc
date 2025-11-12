<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = "personal";
    protected $primaryKey = "id";
    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'dni',
        'cardno',
        'estado_sync',
        'sistema',
        'rol_system',
        'usuario',
        'password_system'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_system',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_system'] = bcrypt($value);
    }

    public function getAuthPassword()
    {
        return $this->password_system; // Devuelve la columna correcta para la contraseña
    }
}
