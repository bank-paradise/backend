<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Community;
use App\Models\CommunityInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CommunityController extends Controller
{
    /**
     * Create a new community (owner).
     */
    public function invite(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        if (auth()->user()->community_role != ('owner' || 'admin' || 'moderator')) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $userInvited = User::where('name', $request->name)->first();

        if (!$userInvited) {
            return response()->json([
                "error" => "USER_NOT_FOUND",
            ], 404);
        }

        if (isset($userInvited->community_id)) {
            return response()->json([
                "error" => "USER_ALREADY_IN_A_COMMUNITY",
            ], 409);
        }

        if (CommunityInvitation::where('user_id', $userInvited->id)->where('community_id', auth()->user()->community_id)->exists()) {
            return response()->json([
                "error" => "USER_ALREADY_INVITED",
            ], 409);
        }

        CommunityInvitation::create([
            'user_id' => $userInvited->id,
            'community_id' => auth()->user()->community_id,
        ]);

        return response()->json([
            "user_invited" => $userInvited,
            "community" => auth()->user()->community()->first(),
        ], 200);
    }

    /**
     * Create a new community (owner).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);


        if (isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_ALREADY_HAS_COMMUNITY",
            ], 409);
        }
        if (Community::where('name', $request->name)->exists()) {
            return response()->json(["error" => "COMMUNITY_ALREADY_EXISTS"], 409);
        }

        $community = Community::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        auth()->user()->update([
            'community_id' => $community->id,
            'community_role' => 'owner',
        ]);

        BankAccount::create([
            'name' => 'Compte de ' . auth()->user()->name,
            'type' => 'personnal',
            'rib' => Str::uuid(),
            'user_id' => auth()->user()->id,
        ]);

        return response()->json([
            "community" => $community,
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\Response
     */
    public function show(Community $community)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\Response
     */
    public function edit(Community $community)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Community $community)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\Response
     */
    public function destroy(Community $community)
    {
        //
    }
}
