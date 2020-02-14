<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = "evento";
    public $timestamps = true;

    protected $fillable = [
        'users_id', 'titulo', 'subtitulo', 'descripcion', 'foto', 'fecha_de_asistencia', 'fecha_de_termino'
    ];
}
