<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Community;
use App\Models\CommunityInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CommunityController extends Controller
{

    private function searchAccounts()
    {

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                'error' => 'USER_NOT_IN_A_COMMUNITY',
            ], 404);
        }

        $accountsPerso = [];
        $accountsPro = [];

        foreach (BankAccount::where('community_id', auth()->user()->community_id)
            ->where('user_id', '!=', auth()->user()->id)
            ->where("type", "personnal")->get() as $account) {
            array_push($accountsPerso, [
                'name' => $account->user->name,
                'rib' => $account->rib,
            ]);
        }

        foreach (BankAccount::where('community_id', auth()->user()->community_id)
            ->where("type", "professional")->get() as $account) {
            array_push($accountsPro, [
                'name' => $account->user->name,
                'rib' => $account->rib,
            ]);
        }

        return [
            'personnal' => $accountsPerso,
            'professional' => $accountsPro,
        ];
    }

    public function get()
    {
        $invitations = [];
        foreach (auth()->user()->invitations()->get() as $invitation) {
            array_push($invitations, [
                'name' => $invitation->community->name,
                'info' => $invitation,
            ]);
        }

        $accounts = $this->searchAccounts();

        return response()->json([
            'community' => auth()->user()->community,
            'invitations' => $invitations,
            'accounts' => $accounts,
        ], 200);
    }

    public function getAccounts()
    {
        $accounts = $this->searchAccounts();

        return response()->json($accounts, 200);
    }

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
     * Join a community (user).
     */
    public function join(Request $request)
    {
        $request->validate([
            'accept' => 'required',
        ]);


        $communityInvitation = CommunityInvitation::where('id', $request->id)->first();

        if (!$communityInvitation) {
            return response()->json([
                "error" => "USER_NOT_INVITED",
            ], 404);
        }

        if ($communityInvitation->user_id != auth()->user()->id) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        if (auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_ALREADY_IN_A_COMMUNITY",
            ], 409);
        }




        if ($request->accept) {
            auth()->user()->update([
                'community_id' => $communityInvitation->community_id,
                'community_role' => 'member',
            ]);

            $communityInvitation->delete();
        } else {
            $communityInvitation->delete();
        }

        $invitations = [];
        foreach (auth()->user()->invitations()->get() as $invitation) {
            array_push($invitations, [
                'name' => $invitation->community->name,
                'info' => $invitation,
            ]);
        }

        $newAccount = BankAccount::create([
            'balance' => auth()->user()->community->starting_amout,
            'name' => auth()->user()->name,
            'type' => 'personnal',
            'rib' => Str::uuid(),
            'user_id' => auth()->user()->id,
            'community_id' => $communityInvitation->community_id
        ]);

        BankTransaction::create([
            'amount' => auth()->user()->community->starting_amout,
            'transmitter' => "COMMUNITY",
            'receiver' => $newAccount->rib,
            'description' => auth()->user()->community->starting_message,
        ]);

        $accountsPerso = [];
        $accountsPro = [];

        foreach (BankAccount::where('community_id', auth()->user()->community_id)->where("type", "personnal")->get() as $account) {
            array_push($accountsPerso, [
                'name' => $account->user->name,
                'rib' => $account->rib,
            ]);
        }

        foreach (BankAccount::where('community_id', auth()->user()->community_id)->where("type", "professional")->get() as $account) {
            array_push($accountsPro, [
                'name' => $account->user->name,
                'rib' => $account->rib,
            ]);
        }

        return response()->json([
            "community" => auth()->user()->community,
            "invitations" => $invitations,
            "accounts" => [
                'personnal' => $accountsPerso,
                'professional' => $accountsPro,
            ],
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
            'currency' => 'required',
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
            'currency' => $request->currency,
        ]);

        $communityCreated = Community::where('id', $community->id)->first();

        auth()->user()->update([
            'community_id' => $community->id,
            'community_role' => 'owner',
        ]);

        $newAccount = BankAccount::create([
            'balance' => $communityCreated->starting_amout,
            'name' => auth()->user()->name,
            'type' => 'personnal',
            'rib' => Str::uuid(),
            'user_id' => auth()->user()->id,
            'community_id' => $community->id,
        ]);

        BankTransaction::create([
            'amount' => $communityCreated->starting_amout,
            'transmitter' => "COMMUNITY",
            'receiver' => $newAccount->rib,
            'description' => $communityCreated->starting_message,
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
