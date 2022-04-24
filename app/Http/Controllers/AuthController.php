<?php

namespace App\Http\Controllers;

use App\Mail\UserDelete;
use App\Mail\UserEdit;
use App\Mail\UserNewLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistered;
use App\Models\CompanyEmployees;
use App\Models\UserLocations;
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

        $locationExists = UserLocations::where('ipv4', $request->ip())->where('user_id', $user->id)->first();
        $location = $this->getLocalization($request->ip());

        if (!$locationExists && $location) {
            UserLocations::create([
                'user_id' => $user->id,
                'ipv4' => $location['IPv4'],
                'country_code' => $location['country_code'],
                'country_name' => $location['country_name'],
                'city' => $location['city'],
                'postal' => $location['postal'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'state' => $location['state'],
            ]);

            $mailParams = [
                'name' => $user->name,
                'ipv4' => $location['IPv4'],
                'country_name' => $location['country_name'],
                'state' => $location['state'],
            ];

            Mail::to($request->email)->send(new UserNewLocation($mailParams));
        }

        $user->tokens()->where('tokenable_id',  $user->id)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            "token" => $token,
            "user" => $user
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

        $location = $this->getLocalization($request->ip());

        if ($location) {
            UserLocations::create([
                'user_id' => $user->id,
                'ipv4' => $location['IPv4'],
                'country_code' => $location['country_code'],
                'country_name' => $location['country_name'],
                'city' => $location['city'],
                'postal' => $location['postal'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'state' => $location['state'],
            ]);
        }

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

    public function edit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $nameExists = User::where('name', $request->name)->where('id', '!=', auth()->user()->id)->exists();
        $emailExists = User::where('email', $request->email)->where('id', '!=', auth()->user()->id)->exists();

        if ($nameExists) {
            return response()->json(["error" => "USER_NAME_ALREADY_REGISTED"], 409);
        }

        if ($emailExists) {
            return response()->json(["error" => "USER_ALREADY_REGISTED"], 409);
        }

        $user = auth()->user();



        $user->name = $request->name;
        $user->email = $request->email;




        if ($request->password && $request->password != "") {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(["error" => "WRONG_PASSWORD"], 400);
            } else {
                $user->password = Hash::make($request->new_password);
            }
        }

        try {
            $mailParams = [
                'email' => $request->email,
                'name' => $request->name,
            ];

            Mail::to($request->email)->send(new UserEdit($mailParams));
        } catch (\Exception $e) {
            return response()->json(["error" => "MAIL_SEND_ERROR"], 500);
        }

        $user->save();

        return response()->json([
            "user" => $user,
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

    public function delete(Request $request)
    {
        $user = auth()->user();

        try {
            $mailParams = [
                'name' => $user->name,
            ];

            Mail::to($user->email)->send(new UserDelete($mailParams));
        } catch (\Exception $e) {
            return response()->json(["error" => "MAIL_SEND_ERROR"], 500);
        }

        if ($user->community_role == "owner") {
            $usersInCommu = User::where('community_id', $user->community_id)->get();
            foreach ($usersInCommu as $userInCommu) {
                $userInCommu->community_id = null;
                $userInCommu->community_role = "member";
                $userInCommu->save();
            }
            $user->community->delete();
        }

        $bankAccounts = $user->bankAccounts;
        // retirer tout les userid qui sont dans les bankAccounts
        foreach ($bankAccounts as $bankAccount) {
            $bankAccount->user_id = null;
            $bankAccount->save();
        }

        $jobs = CompanyEmployees::where('user_id', $user->id)->get();
        foreach ($jobs as $job) {
            $job->delete();
        }
        $user->community_id = null;
        $user->save();

        $user->delete();



        return response()->json(null, 204);
    }


    public function getLocalization($ip)
    {
        $locationData = \Location::get($ip);

        if (!$locationData) {
            return null;
        } else {
            return [
                'IPv4' => $locationData->ip,
                'country_code' => $locationData->countryCode,
                'country_name' => $locationData->countryName,
                'city' => $locationData->cityName,
                'postal' => $locationData->postalCode,
                'latitude' => $locationData->latitude,
                'longitude' => $locationData->longitude,
                'state' => $locationData->regionName,
            ];
        }
    }
}
