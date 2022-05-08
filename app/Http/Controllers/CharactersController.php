<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CharactersController extends Controller
{
    function getCharacter(Request $request)
    {
        if (!$request->user()->character) {
            return response()->json(['error' => 'USER_HAS_NO_RP_CHARACTER'], 403);
        }

        return response()->json($request->user()->character);
    }

    function createCharacter(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'zip_code' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'height' => 'required|integer',
            'weight' => 'required|integer',
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if (auth()->user()->character) {
            return response()->json(['error' => 'USER_HAS_RP_CHARACTER'], 403);
        }

        $character = new \App\Models\Characters();
        $character->uuid = Str::uuid();
        $character->first_name = $request->first_name;
        $character->last_name = $request->last_name;
        $character->birthday = $request->birthday;
        $character->address = $request->address;
        $character->city = $request->city;
        $character->country = $request->country;
        $character->zip_code = $request->zip_code;
        $character->phone_number = $request->phone_number;
        $character->height = $request->height;
        $character->weight = $request->weight;
        $character->user_id = $request->user()->id;

        $avatar = $request->file('avatar');
        $filename = time() . '.' . $avatar->getClientOriginalExtension();
        $path = $avatar->storeAs('public/avatars', $filename);
        $character->avatar = $filename;

        $character->save();

        return response()->json($character);
    }
}
