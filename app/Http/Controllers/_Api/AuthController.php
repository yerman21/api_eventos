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

        // $user = Auth::user();
        // $tokenResult = $user->createToken('MiApp');
        // $token = $tokenResult->token;
        // $token->expires_at = Carbon::now()->addMinutes(2);
        // if ($request->remember_me) {
        //     $token->expires_at = Carbon::now()->addWeeks(1);
        // }
        // $token->save();

        $data = [
            'grant_type' => 'password',
            'client_id' => 2,
            'client_secret' => "3lDBPTBhk3XnOtnWCzJKaSprznoQmribb2BqXTql",
            'username' => $r['email'],
            'password' => $r['password'],
            ];
            
        $requestToken = Request::create('/oauth/token', 'POST', $data);
        $rpta = app()->handle($requestToken);
        $data = json_decode($rpta->getContent());

        return $this->sendResponse([
            "token" => $data,
            // [
            //     'access_token' => $tokenResult->accessToken,
            //     'token_type'   => 'Bearer',
            //     'expires_in'   => Carbon::now()->diffInRealMilliseconds($token->expires_at),
            //     'token_token' => $data
            // ],
            "userData" => Auth::user()
        ], "Bienveniedo!!");
    }

    public function refreshToken(Request $request){
        $validator = Validator::make($request->all(), [
            "refresh_token" => "required|string"
        ]);

        $bandVali = $this->checkValidation($validator);
        if($bandVali) return $bandVali;

        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => 2,
            'client_secret' => "3lDBPTBhk3XnOtnWCzJKaSprznoQmribb2BqXTql",
            'refresh_token' => request("refresh_token")
        ];

        $requestToken = Request::create('/oauth/token', 'POST', $data);
        $rpta = app()->handle($requestToken);
        $data = json_decode($rpta->getContent());
        if(property_exists($data, "hint") && $data->hint == "Token has expired"){
            return $this->sendError("Se vencio el refresh token", 401);
        }

        return $this->sendResponse([
            "token" => $data,
            "rpta" => $rpta,
            "userData" => Auth::user()
        ], "Bienveniedo de nuevo!!");
    }
}