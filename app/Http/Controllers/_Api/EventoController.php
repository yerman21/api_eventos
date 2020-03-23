<?php

namespace App\Http\Controllers\_Api;

use App\Http\Controllers\_Api\ApiController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\AsistenciaEvento;
use App\Evento;
use DB;
use Validator;
use Carbon\Carbon;

class EventoController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listarEventos($opcion=0){
    //opcion 0: eventos que no son del usuario; otra opcion: eventos del usuario
        $operator = ($opcion == 0) ? "!=" : "=";
        $eventos = Evento::where("users_id", $operator, Auth::id())->get();
        if($opcion == 0){
            foreach($eventos as $index => $evento){
                $ae = AsistenciaEvento::where("users_id", Auth::id())->where("evento_id", $evento->id)->first();
                $eventos[$index]["bandApuntado"] = !is_null($ae);
                $eventos[$index]["bandCheckAnfitrion"] = !is_null($ae) ? $ae->check_anfitrion : 0;
                $eventos[$index]["bandCheckInvitado"] = !is_null($ae) ? $ae->check_invitado : 0;
            }
        }

        return $this->sendResponse(
            ["eventos" => $eventos, "basePathImage" => asset("storage/")],
             "Eventos recuperados con exito!!"
        );
    }

    public function listarEventosByUsaurio($opcion, $idUsuario){
        // $opcion -> 1(eventos del usuario), 0(eventos al que asistio el usuario)
        if($opcion == 1){
            $eventos = Evento::where("users_id", "=", $idUsuario)->get();

            if($idUsuario != Auth::id()){
                foreach($eventos as $index => $evento){
                    $ae = AsistenciaEvento::where("users_id", "=", Auth::id())->where("evento_id", $evento->id)->first();
                    $eventos[$index]["bandApuntado"] = !is_null($ae);
                    $eventos[$index]["bandCheckAnfitrion"] = !is_null($ae) ? $ae->check_anfitrion : 0;
                    $eventos[$index]["bandCheckInvitado"] = !is_null($ae) ? $ae->check_invitado : 0;
                }
            }
        }else{
            $eventos = DB::table("evento as e")
                    ->join("asistencia_evento as ae", "e.id", "=", "ae.evento_id")
                    ->where("ae.users_id", "=", $idUsuario)
                    ->where("ae.estado", "=", "2")
                    ->select("e.*")
                    ->get();
        }

        return $this->sendResponse(
            [
                "eventos" => $eventos, 
                "basePathImage" => asset("storage/"),
                "opcion" => $opcion,
                "idUsuario" => $idUsuario
            ],
             "Eventos recuperados con exito!!"
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $_Erequest = $request->all();
        $validator = Validator::make(
            $_Erequest,
            [
                "titulo" => "required",
                "subtitulo" => "required",
                "descripcion" => "required",
                "foto" => "required|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
                "fecha_de_asistencia" => "required|date",
                "fecha_de_termino" => "required|date|after_or_equal:fecha_de_asistencia"
            ]
        );

        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;

        $rutaImage = null;

        if($request->hasFile("foto")){
            $file = $request->file('foto');
            
            $bandRptaValidate = $this->validationImage($file->getMimeType());
            if($bandRptaValidate) return $bandRptaValidate;

            $rutaImage = '/eventos/'.time().'_'.$file->getClientOriginalName();
            \Storage::disk('public')->put($rutaImage, \File::get($file));
        }

        try{
            $_Erequest["foto"] = empty($rutaImage) ? null : $rutaImage;
            $_Erequest["fecha_de_asistencia"] = $_Erequest["fecha_de_asistencia"];
            $_Erequest["fecha_de_termino"] = $_Erequest["fecha_de_termino"];
            $_Erequest["users_id"] = Auth::id();
            $evento = Evento::create($_Erequest);

        }catch(\Exception $e){
            $bandExist = \Storage::disk("public")->exists($rutaImage);
            if($bandExist) \Storage::disk('public')->delete($rutaImage);
            
            return $this->sendError("Error al crear evento", ["detalleError" => $e], 500);
        }

        return $this->sendResponse(["evento" => $evento], "Evento creado con exito!!");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $evento = Evento::find($id);
        if($evento == null){
            return $this->sendError("No existe el evento", [], 404);
        }
        return $this->sendResponse(["evento" => $evento], "Evento recuperado con exito!!");
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        $evento = Evento::where([
            ["id", $id],
            ["users_id", Auth::id()]
        ])->first();

        if($evento == null){
            return $this->sendError("No existe el evento o no eres el organizador", [], 404);
        }

        $r = $request->all();
        $validator = Validator::make(
            $r,
            [
                "titulo" => "required",
                "subtitulo" => "required",
                "descripcion" => "required",
                "foto" => "image|mimes:jpeg,png,jpg,gif|max:2048",
                "fecha_de_asistencia" => "required|date",
                "fecha_de_termino" => "required|date|after_or_equal:fecha_de_asistencia"
            ]
            );

        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;
        
        $rutaImage = null;

        if($request->hasFile("foto")){
            $file = $request->file('foto');
            
            $bandValidate = $this->validationImage($file->getMimeType());            
            if($bandValidate) return $bandValidate;

            $rutaImage = '/eventos/'.time().'_'.$file->getClientOriginalName();

            $bandExist = \Storage::disk("public")->exists($evento->foto);
            if($bandExist) \Storage::disk('public')->delete($evento->foto);
            
            \Storage::disk('public')->put($rutaImage, \File::get($file));
            $evento->foto = $rutaImage;
        }

        try{
            $evento->titulo = $r["titulo"];
            $evento->subtitulo = $r["subtitulo"];
            $evento->descripcion = $r["descripcion"];
            $evento->fecha_de_asistencia = $r["fecha_de_asistencia"];
            $evento->fecha_de_termino = $r["fecha_de_termino"];
            $evento->save();
        }
        catch(\Exception $e){
            $bandExist = \Storage::disk("public")->exists($rutaImage);
            if($bandExist) \Storage::disk('public')->delete($rutaImage);

            return $this->sendError("Error al crear Evento", ["errores" => $e], 500);
        }
        return $this->sendResponse(["evento" => $evento], "Evento modificado con exito!!");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //Validar si existe el eevnto y si pertenece al usuario
        $evento = Evento::where([
            ["id", $id],
            ["users_id", Auth::id()]
        ])->first();

        if($evento == null){
            return $this->sendError("No existe el evento o no eres el organizador", [], 404);
        }        
        // comprobar si no hay asistentes
        $asistentes = AsistenciaEvento::where("evento_id", $id)->first();

        if($asistentes){
            $evento = Evento::where("id", $id)->update("estado", 0);
            return $this->sendResponse($evento, "El evento tiene asistentes. Se desactivo el evento");
        }else{
            return $this->sendResponse($evento->delete(), "Se elimino el evento");
        }        
    }
    
}