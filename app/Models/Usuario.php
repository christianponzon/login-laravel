<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    // Nombre de la tabla en MySQL
    protected $table = 'Usuario';

    // Llave primaria
    protected $primaryKey = 'id';

    // Campos de la tabla
    protected $fillable = [
        'Nombres',
        'Apellido',
        'FechaNacimiento',
        'Correo',
        'Passwordd',
    ];

    // Ocultar datos sensibles
    protected $hidden = [
        'Passwordd',
        'remember_token',
    ];

    // La contraseña utiliza un nombre personalizado
    public function getAuthPasswordName()
    {
        return 'Passwordd';
    }

    // Desactivar timestamps automáticos de Eloquent
    public $timestamps = false;
}
