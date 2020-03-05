<?php

namespace App\Http\Controllers\_Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function sendResponse($data, $msg="Peticion realizada con exito!!"){
        return response()->json(
            [
                "success" => true,
                "data" => $data,
                "message" => $msg
            ],
            200
        );
    }

    public function sendError($errorGeneral, $errorsMsg=[] ,$code=404){
        return response()->json(
            [
                "success" => false,
                "errorGeneral" => $errorGeneral,
                "errorMessages" => $errorsMsg
            ],
            $code
        );
    }

    function convertTime2UTC($str, $userTimezone="GMT-5", $format = 'YYYY-m-d H:i:s a'){
        return Carbon::parse($_Erequest["fecha_de_asistencia"], "GMT-5")
                ->setTimezone("UTC");
    }

    public function checkValidation($validator){
        if($validator->fails()){
            return $this->sendError("Error de validacion", $validator->errors(), 422);
        }
    }

    public function validationImage($mimeType){
        $mimesAcept = array("image/jpeg", "image/png", "image/jpg", "image/gif", "image/svg");
        $m = strtolower($mimeType);

        if(!in_array($m, $mimesAcept)){
            return $this->sendError("La imagen no cumple", [
                "MimesAceptadas" => $mimesAcept,
                "MimeEnviado" => $m
            ]);
        }
    }
}
