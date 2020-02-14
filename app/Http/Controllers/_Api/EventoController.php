<?php

namespace App\Http\Controllers\_Api;

use App\Http\Controllers\_Api\ApiController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\AsistenciaEvento;
use App\Evento;
use Validator;
use Carbon\Carbon;

class EventoController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
/*
    public function misEventos(){
        return $this->listarEventos("=");
    }

    public function otrosEventos(){
        return $this->listarEventos("!=");
    }
*/
    public function listarEventos($opcion=0){
    //opcion 0: eventos que no son del usuario; otra opcion: eventos del usuario
        $operator = ($opcion == 0) ? "!=" : "=";
        $eventos = Evento::where("users_id", $operator, Auth::id())->get();

        if($opcion == 0){
            foreach($eventos as $index => $evento){
                $ae = AsistenciaEvento::where("users_id", Auth::id())->where("evento_id", $evento->id)->get();
                $eventos[$index]["bandApuntado"] = !$ae->isEmpty();
            }
        }

        return $this->sendResponse(
            ["eventos" => $eventos, "basePathImage" => asset("storage/")],
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
                "fecha_de_termino" => "required|date"
            ]
        );

        $this->checkValidation($validator);
        $rutaImage = null;

        if($request->hasFile("foto")){
            $file = $request->file('foto');
            
            $bandRptaValidate = $this->validationImage($file->getMimeType());
            if($bandRptaValidate) return $bandRptaValidate;

            $rutaImage = '/eventos/'.time().'_'.$file->getClientOriginalName();
            \Storage::disk('public')->put($rutaImage, \File::get($file));
        }

        try{
            // $_Erequest["fecha_de_asistencia"] = $this->convertTime2UTC($_Erequest["fecha_de_asistencia"]);
            // $_Erequest["fecha_de_termino"] = $this->convertTime2UTC($_Erequest["fecha_de_termino"]);
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
                "foto" => "required|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
                "fecha_de_asistencia" => "required|date",
                "fecha_de_termino" => "required|date"
            ]
            );

        $this->checkValidation($validator);
        $rutaImage = null;

        if($request->hasFile("foto")){
            $file = $request->file('foto');
            
            $bandValidate = $this->validationImage($file->getMimeType());            
            if($bandValidate) return $bandValidate;

            $rutaImage = '/eventos/'.time().'_'.$file->getClientOriginalName();

            $bandExist = \Storage::disk("public")->exists($evento->foto);
            if($bandExist) \Storage::disk('public')->delete($evento->foto);
            
            \Storage::disk('public')->put($rutaImage, \File::get($file));
        }

        try{
            $evento->titulo = $r->get("titulo");
            $evento->subtitulo = $r->get("subtitulo");
            $evento->descripcion = $r->get("descripcion");
            $evento->foto = $r->get("foto");
            $evento->foto = empty($rutaImage) ? null : $rutaImage;
            $evento->fecha_de_asistencia = $this->convertTime2UTC($_Erequest["fecha_de_asistencia"]);
            $evento->fecha_de_termino = $this->convertTime2UTC($_Erequest["fecha_de_termino"]);
            $evento->save();
        }
        catch(\Exception $e){
            $bandExist = \Storage::disk("public")->exists($rutaImage);
            if($bandExist) \Storage::disk('public')->delete($rutaImage);

            return $this->sendError("Error al crear Evento", $e, 500);
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


// Route::get('storage/{filename}', function ($filename)
// {
//     $path = storage_path('public/' . $filename);

//     if (!File::exists($path)) {
//         abort(404);
//     }

//     $file = File::get($path);
//     $type = File::mimeType($path);

//     $response = Response::make($file, 200);
//     $response->header("Content-Type", $type);

//     return $response;
// });