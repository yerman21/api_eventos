<?php

namespace App\Http\Controllers\_Api;

use App\Http\Controllers\_Api\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\AsistenciaEvento;
use App\Evento;
use Carbon\Carbon;
use DB;

class AsistenciaEventoController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //apuntarse a un evento
    public function apuntarseEvento(Request $request){
        $r = $request->all();
        $validator = Validator::make(
            $r,
            [
                "evento_id" => "required",
            ]
        );

        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;

        $r["users_id"] = Auth::id();
        $suscrip_evento = AsistenciaEvento::create($r);

        return $this->sendResponse(["suscripcion_evento" => $suscrip_evento], "Se apunto al evento con exito!!");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //

    public function whoAsistentEvent($id){
        $evento = Evento::find($id);
        if($evento == null){
            return $this->sendError("No existe el evento", [], 404);
        }

        $asistententes = AsistenciaEvento::where("evento_id", $id)
                        ->with("user")
                        ->with("evento:id,users_id")
                        ->get();
        /*$asistententes = AsistenciaEvento::where("evento_id", $id)
                        ->with(array('user'=>function($query) use ($server_storage){
                            $query->select(
                                'id', 'nombres', 'apellidos', 'apodo',
                                'edad', 'genero',  DB::raw("CONCAT('$server_storage', foto) AS foto")
                            );
                        }))->get();
        */

        return $this->sendResponse(["asistententes" => $asistententes], "Se recupero los asistentes con exito!!");
    }

/*    public function asistents_to_event($id){
        $this->whoAsistenEvent($id, false);
    }

    public function asistents_my_event($id){
        $this->whoAsistenEvent($id, true);
    }

    public function whoAsistenEvent($id, $validOwner=false){
        $evento = Evento::find($id);
        if($evento == null){
            return $this->sendError("No existe el evento", [], 404);
        }

        if($evento->users_id != Auth::id() && $validOwner){
            return $this->sendError("No eres propietario del evento", [], 404);
        }
        // Para el usuario que no este apuntado al evento
        $ae = AsistenciaEvento::where("users_id", Auth::id())->where("evento_id", $id)->get();
        if(!$ae && !$validOwner){
            return $this->sendError("No esta apuntado al evento", [], 404);
        }
   
        $asistententes = AsistenciaEvento::where("evento_id", $id)->with("user")->get();

        return $this->sendResponse(["asistententes" => $asistententes], "Se recupero los asistentes con exito!!");
    }
*/

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    public function pasarLista(Request $request){
        $r = $request->all();
        $validator = Validator::make(
            $r,
            [
                "evento_id" => "required",
                "usuario_id" => "required"
            ]
        );
        
        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;

        $evento = Evento::find($r["evento_id"]);
        if($evento->users_id != Auth::id()){
            return $this->sendError("No eres propietario del evento", [], 404);
        }

        $nowCarbon = Carbon::now();
        $eventoFecha = Carbon::instance(new \DateTime($evento->fecha_de_asistencia));
        
        if($eventoFecha >= $nowCarbon){
            return $this->sendError("Falta para la fecha del evento", ["nowCarbon" => $nowCarbon]);
        }

        $ae = AsistenciaEvento::where("evento_id", $r["evento_id"])
                        ->where("users_id", $r["usuario_id"])
                        ->update(["check_anfitrion" => 1]);
        
        return $this->sendResponse(["asistencia_evento" => $ae], "Se confirmo por parte del anfitrion la asistencia");
    }

    public function quitarCheckControl(Request $request){
        $r = $request->all();
        $validator = Validator::make(
            $r,
            [
                "usuario_id" => "required",
                "evento_id" => "required"
            ]
        );

        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;

        $evento = Evento::find($r["evento_id"]);
        if($evento->users_id != Auth::id()){
            return $this->sendError("No eres propietario del evento", [], 404);
        }

        $checkControl = AsistenciaEvento::where([
            ["evento_id", $r["evento_id"]],
            ["users_id", $r["usuario_id"]]
        ]);
        $chFirts = $checkControl->first();
        if($chFirts == null){
            return $this->sendError("El usuario no participa del evento", [], 404);
        }

        if($chFirts->check_invitado != null){
            return $this->sendError("La persona ya confirmo su asistencia", [], 404);
        }

        $checkControl = $checkControl->update([
            "check_anfitrion" => null,
            "estado" => 1,
        ]);
        
        return $this->sendResponse(["asistencia_evento" => $checkControl], "Se cancelo el check-control al evento");
    }

    public function confirmarAsistencia(Request $request, $idd){
        $evento = Evento::find($idd);
        if(!$evento){
            return $this->sendError("El evento no existe", []);
        }

        $nowCarbon = Carbon::now();
        $eventoFechaI = Carbon::instance(new \DateTime($evento->fecha_de_asistencia));
        $eventoFechaF = Carbon::instance(new \DateTime($evento->fecha_de_termino));
        
        if($eventoFechaI >= $nowCarbon && $nowCarbon > $eventoFechaF){
            return $this->sendError("EL esta fuera de tiempo del evento", ["nowCarbon" => $nowCarbon]);
        }

        $ae = AsistenciaEvento::where([
                ["evento_id", $idd],
                ["users_id", Auth::id()],
                ["check_anfitrion", 1]
            ]);

        if(!$ae->first()){
            return $this->sendError("No eres asistente del evento o no tienes el check del anfitrion del evento", [], 404);
        }

        $ae = $ae->update([
                "check_invitado" => 1,
                "hora_check_asist" => $nowCarbon,
                "estado" => 2
            ]);
        
        return $this->sendResponse(["asistencia_evento" => $ae], "Se confirmo la asistencia al evento");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function desapuntarceOfEvento($id) {
        $asistencia_evento = AsistenciaEvento::where("users_id", Auth::id())->delete();
        return $this->sendResponse(["asistencia" => $asistencia_evento], "Te desapuntaste del evento con exto!!");
    }
}
