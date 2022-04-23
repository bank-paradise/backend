<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistered;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\Http;

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



        $response = Http::get('https://geolocation-db.com/json/' . $request->ip());

        $ip_data = $response->json();

        $user->update([
            'ip_address' => $request->ip()
        ]);

        $user->tokens()->where('tokenable_id',  $user->id)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            "token" => $token,
            "user" => $user,
            "ip" => $ip_data,
            "ip_address" => $request->ip(),
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

        $nameExists = User::where('name', $request->name)->exists();

        if ($nameExists) {
            return response()->json(["error" => "USER_NAME_ALREADY_REGISTED"], 409);
        }

        try {
            $mailParams = [
                'subject' => 'Bienvenue sur Bank-Paradise',
                'mail' => "noreply@bank-paradise.fr",
                'name' => "Bank-Paradise",
            ];

            Mail::to($request->email)->send(new UserRegistered($mailParams));
        } catch (\Exception $e) {
            return response()->json(["error" => "MAIL_SEND_ERROR"], 500);
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
