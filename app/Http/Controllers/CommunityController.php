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
            'email' => 'required',
        ]);

        if (auth()->user()->community_role != ('owner' || 'admin' || 'moderator')) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $userInvited = User::where('email', $request->email)->first();

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
            "invitations" => auth()->user()->community->invitations,
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
            'community_id' => $communityInvitation->community_id,
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
            'community_id' => $community->id,
        ]);

        return response()->json([
            "community" => $community,
            "user" => auth()->user(),
        ], 200);
    }

    public function getInvitations()
    {
        if (!auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        if (auth()->user()->community_role != ('owner' || 'admin' || 'moderator')) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $invitations = [];

        foreach (auth()->user()->community->invitations()->get() as $invitation) {
            $user = User::where('id', $invitation->user_id)->first();
            array_push($invitations, [
                'id' => $invitation->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $invitation->created_at,
            ]);
        }

        return response()->json([
            "invitations" => $invitations,
        ], 200);
    }

    public function getTransactions()
    {
        if (!auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        if (auth()->user()->community_role != ('owner' || 'admin' || 'moderator')) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $transactions = [];

        foreach (auth()->user()->community->transactions()->get() as $transaction) {

            $transmitter = BankAccount::where('rib', $transaction->transmitter)->first();
            $reciever = BankAccount::where('rib', $transaction->receiver)->first();

            if ($transaction->transmitter == "COMMUNITY") {
                $transmitter = [
                    "name" => auth()->user()->community->name
                ];
            }
            if ($transaction->receiver == "COMMUNITY") {
                $reciever = [
                    "name" => auth()->user()->community->name
                ];
            }

            array_push($transactions, [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency' => auth()->user()->community->currency,
                'transmitter' => $transmitter['name'],
                'receiver' =>  $reciever['name'],
                'description' => $transaction->description,
                'created_at' => $transaction->created_at,
            ]);
        }

        return response()->json([
            "transactions" => $transactions,
        ], 200);
    }

    public function update(Request $request)
    {
        $community = auth()->user()->community()->first();

        if (auth()->user()->community_role != 'owner') {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'currency' => 'required',
            'starting_amout' => 'required',
            'starting_message' => 'required',
        ]);

        $community->update([
            'name' => $request->name,
            'description' => $request->description,
            'currency' => $request->currency,
            'starting_amout' => $request->starting_amout,
            'starting_message' => $request->starting_message,
        ]);

        return response()->json([
            "community" => $community,
        ], 200);
    }

    public function getMembers()
    {
        if (!auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        if (auth()->user()->community_role != ('owner' || 'admin' || 'moderator')) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $members = [];

        foreach (auth()->user()->community->members()->get() as $member) {
            array_push($members, [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->community_role,
            ]);
        }

        return response()->json([
            "members" => $members,
        ], 200);
    }

    public function changeRole(Request $request)
    {

        if (!auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        if (auth()->user()->community_role == 'member') {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }


        if (auth()->user()->community_role == 'moderator') {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $request->validate([
            'user_id' => 'required',
            'role' => 'required',
        ]);

        $user = User::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json([
                "error" => "USER_NOT_FOUND",
            ], 404);
        }

        if (auth()->user()->community_role == "owner" && $user->community_role == "owner") {
            $nbOwner = auth()->user()->community->members()->where('community_role', 'owner')->count();
            if ($nbOwner == 1) {
                return response()->json([
                    "error" => "CANNOT_CHANGE_ROLE_ONLY_ONE_OWNER",
                ], 409);
            }
        }

        if (auth()->user()->community_role == 'admin' && $user->community_role == 'owner') {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }



        if ($user->community_id != auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $user->update([
            'community_role' => $request->role,
        ]);

        return response()->json([
            "user" => $user,
        ], 200);
    }

    public function kickMember(Request $request)
    {

        if (!auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        if (auth()->user()->community_role != ('owner' || 'admin' || 'moderator')) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        $request->validate([
            'user_id' => 'required',
        ]);

        $user = User::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json([
                "error" => "USER_NOT_FOUND",
            ], 404);
        }

        if ($user->community_id != auth()->user()->community_id) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        if ($user->community_role == 'owner' && auth()->user()->community_role != 'owner') {
            return response()->json([
                "error" => "CANNOT_KICK_OWNER",
            ], 409);
        }

        if ($user->community_role == 'admin' && auth()->user()->community_role != ('owner')) {
            return response()->json([
                "error" => "CANNOT_KICK_ADMIN",
            ], 409);
        }

        if ($user->community_role == 'moderator' && auth()->user()->community_role != ('owner' || 'admin')) {
            return response()->json([
                "error" => "CANNOT_KICK_MODERATOR",
            ], 409);
        }

        $user->update([
            'community_role' => 'member',
            'community_id' => null,
        ]);


        $user->bankAccounts()->update([
            'user_id' => null,
        ]);

        return response()->json([
            "user" => $user,
        ], 200);
    }
}
