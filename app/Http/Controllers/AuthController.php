<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                "error" => "WRONG_CREDENTIALS",
            ], 401);
        }

        $user->update([
            'ip_address' => $request->ip()
        ]);

        $user->tokens()->where('tokenable_id',  $user->id)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            "token" => $token,
            "user" => $user,
        ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $exists = User::where('email', $request->email)->exists();

        if ($exists) {
            return response()->json(["error" => "USER_ALREADY_REGISTED"], 409);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'ip_address' => $request->ip(),
        ]);

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            "token" => $token,
            "user" => $user,
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json([
            "user" => auth()->user(),
        ], 200);
    }

    public function logout(Request $request)
    {
        $hasSuccedded = $request->user()->currentAccessToken()->delete();


        if ($hasSuccedded) {
            return response()->json(null, 204);
        }
        return response()->json(['message' => 'USER_NOT_AUTHENTICATED'], 401);
    }
}
