<?php

namespace App\Http\Controllers\_Api;

use App\Http\Controllers\_Api\ApiController;
use Illuminate\Http\Request;
Use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Userdata;
use Validator;

class UserController extends ApiController
{
    public function getUserData($id=null){
        $user = User::find( $id == null ? Auth::id() : $id );
        if($user == null){
            return $this->sendError("No existe el usuario", [], 404);
        }
        return $this->sendResponse(["user" => $user], "Datos del usuario");
    }

    public function register(Request $request){
        $user_request = $request->all();

        $validator = Validator::make(
            $user_request,
            [
                "nombres" => "required",
                "email" => "required|email|unique:users",
                "password" => "required|max:10",
                "confirm_password" => "required|same:password",
                "apellidos" => "required",
                "apodo" => "",
                "edad" => "required",
                "genero" => "required|max:1",
                "foto" => "image|mimes:jpeg,png,jpg,gif,svg|max:2048" //en kilobytes
            ]
        );

        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;

        if($request->hasFile("foto")){
            $file = $request->file('foto');
            
            $bandRptaValidate = $this->validationImage($file->getMimeType());
            if($bandRptaValidate) return $bandRptaValidate;

            $rutaImage = '/perfil/'.time().'_'.$file->getClientOriginalName();
            \Storage::disk('public')->put($rutaImage, \File::get($file));
        }

        $user_request["password"] = bcrypt($user_request["password"]);

        DB::beginTransaction();
        try{
        // DB::transaction(function(){
            $user_request["apodo"] = empty($user_request["apodo"]) ? null : $user_request["apodo"];
            $user_request["foto"] = empty($rutaImage) ? null : $rutaImage;
            $user = User::create($user_request);
            DB::commit();
        // });
        }catch(\Exception $e){
            DB::rollback();
            $bandExist = \Storage::disk("public")->exists($rutaImage);
            if($bandExist) \Storage::disk('public')->delete($rutaImage);

            return $this->sendError("Error al crear usuario", $e, 500);
        }

        $token = $user->createToken("MyAppEvento")->accessToken;

        return $this->sendResponse(
            [
                "token" => $token,
                "user" => $user,
                "basePathImage" => assert("public")
            ],
            "Usuario creado con Exito!!"
        );
    }

    public function updateUser(Request $request){
        $user = User::find(Auth::id());
        
        if($user == null){
            return $this->sendError("Error en los datos", ["El usuario no existe"], 422);
        }
        $user_request = $request->all();
        $validator = Validator::make(
            $user_request,
            [
                "nombres" => "required",
                "email" => "required|email|unique:users,email,$user->id,id",
                "apellidos" => "required",
                "apodo" => "",
                "edad" => "required|max:2",
                "genero" => "required|max:1",
                "foto" => "image|mimes:jpeg,png,jpg,gif,svg|max:2048" //en kilobytes
            ]
        );

        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;
        
        $rutaImage = null;

        if($request->hasFile("foto")){
            $file = $request->file('foto');
            
            $bandValidate = $this->validationImage($file->getMimeType());            
            if($bandValidate) return $bandValidate;

            $rutaImage = '/perfil/'.time().'_'.$file->getClientOriginalName();

            $bandExist = \Storage::disk("public")->exists($user->foto);
            if($bandExist) \Storage::disk('public')->delete($user->foto);
            
            \Storage::disk('public')->put($rutaImage, \File::get($file));
            $user->foto = empty($rutaImage) ? null : $rutaImage;
        }

        DB::beginTransaction();
        try{
            $user->nombres = $user_request["nombres"];
            $user->apellidos = $user_request["apellidos"];
            $user->email = $user_request["email"];
            $user->apodo = empty($user_request["apodo"]) ? null : $user_request["apodo"];
            $user->edad = $user_request["edad"];
            $user->genero = $user_request["genero"];

            $user->save();
            DB::commit();
        }catch(\Exception $e){
            DB::rollback();
            $bandExist = \Storage::disk("public")->exists($rutaImage);
            if($bandExist) \Storage::disk('public')->delete($rutaImage);
            
            return $this->sendError("Error al actualizar usuario", ["user" => $user], 500);
        }
        return $this->sendResponse(
            [
                "user" => $user,
                "basePathImage" => asset("storage/")
                // "userData" => $userData
            ],
            "Usuario modificado con Exito!!"
        );
    }

    public function logout(Request $request){
        $user = Auth::user();
        if($user == null){
            return $this->sendError("No ha iniciado session!!");
        }
        $bandRevoke = $user->token()->revoke();
        if(!$bandRevoke){
            return $this->sendError("No se pudo revocar el token");
        }
        return $this->sendResponse(["isRevoke" => $bandRevoke], "Session cerrada");
    }
}
