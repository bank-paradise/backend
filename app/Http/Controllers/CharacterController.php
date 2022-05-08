<?php

namespace App\Http\Controllers;

use App\Models\RoleplayInformation;
use Illuminate\Http\Request;

class RoleplayInformationController extends Controller
{
    function getCharacter(Request $request)
    {
        if (!$request->user()->character) {
            return response()->json(['error' => 'USER_HAS_NO_RP_CHARACTER'], 403);
        }

        return response()->json($request->user()->character);
    }
}
