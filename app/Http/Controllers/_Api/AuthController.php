<?php

namespace App\Http\Controllers\_Api;

use App\Http\Controllers\_Api\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Validator;
use Carbon\Carbon;

class AuthController extends ApiController{

    public function login(Request $request){
        $r = $request->all();
        $validator = Validator::make($r, [
            "email" => "required|string|email",
            "password" => "required|max:10",
            // "confirm_password" => "required|same:password"
        ]);
        
        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;

        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return $this->sendError("credenciales incorrecta", [], 401);
        }

        $user = Auth::user();
        $tokenResult = $user->createToken('MiApp');
        $token = $tokenResult->token;
        // if ($request->remember_me) {
        //     $token->expires_at = Carbon::now()->addWeeks(1);
        // }
        $token->save();

        return $this->sendResponse([
            "token" => [
                'access_token' => $tokenResult->accessToken,
                'token_type'   => 'Bearer',
                'expires_at'   => Carbon::parse($token->expires_at)->toDateTimeString(),
            ],
            "userData" => $user
        ], "Bienveniedo!!");
    }

    
}

