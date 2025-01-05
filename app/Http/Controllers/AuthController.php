<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ResetPasswordRequest;

class AuthController extends Controller
{


    public function login(LoginRequest $request)
    {
        $creadentials = $request->validated();

        if (!$token = Auth::attempt($creadentials)) {
            $this->responseErrors('email dan password salah!');
        }

        $creadentials['name'] = Auth::user()->name;
        unset($creadentials['password']);
        Log::info("user login", ['user' => Auth::user()]);
        return $this->respondWithToken($token, $creadentials);
    }


    public function me()
    {
        $user = Auth::user();
        unset($user['password']);
        return response()->json($user);
    }



    public function reset(ResetPasswordRequest $request)
    {
        $data = $request->validated();
        $user = User::where('token', $data['token']);
        $is_token_exist = $user->count();
        if ($is_token_exist === 0) {
            $this->responseErrors("Token tidak valid!");
        }

        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengubah password'
        ]);
    }


    public function logout()
    {
        Auth::logout();
        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout',
        ]);
    }


    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $data)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'data' => $data
        ]);
    }
}
