<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AsistenciaEvento extends Model
{
    protected $table = "asistencia_evento";
    public $timestamps = false;
    protected $fillable = [ "users_id", "evento_id"];
    // "check_anfitrion", "check_invitado", "hora_check_asist" 

    public function user(){
        return $this->belongsTo("App\User", "users_id", "id");
    }
    public function evento(){
        return $this->belongsTo("App\Evento", "evento_id", "id");
    }
}
