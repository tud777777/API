<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\RegRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function reg(RegRequest $request){
        $user = User::query()->where('email','=', $request->email)->first();
        if(!empty($user)){
            return response()->json([
                'code' => 403,
                'message' => 'Пользователь уже существует'
            ], 403);
        }
        else{
            $user = User::query()->create($request->validated());
            return response()->json([
                'data' => [
                    'user' => [
                        'name' => $user->last_name.' '.$user->first_name.' '.$user->patronymic,
                        'email' => $user->email
                    ],
                    'code' => 201,
                    'message' => 'Пользователь создан'
                ]

            ], 201);
        }
    }
    public function aut(AuthRequest $request){
        if(auth()->attempt($request->validated())){
            $user = auth()->user();
            return response()->json([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->last_name.' '.$user->first_name.' '.$user->patronymic,
                        'birth_date' => $user->birth_date,
                        'email' => $user->email
                    ],
                    'token' => $user->createToken('token')->plainTextToken
                ]
            ], 201);
        }
        else {
            return throw new AuthenticationException();
        }
    }
    public function out(){
        auth()->user()->currentAccessToken()->delete();
        return response()->noContent();
    }

}



