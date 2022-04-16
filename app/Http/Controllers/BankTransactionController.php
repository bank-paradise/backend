<?php

namespace App\Http\Controllers;

use App\Events\TransactionEvent;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Community;
use Illuminate\Http\Request;

class BankTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required',
            'transmitter' => 'required', // rib transmitter
            'receiver' => 'required',   // rib receiver
        ]);

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $receiver = BankAccount::where('rib', $request->receiver)->first();

        if (!$receiver) {
            return response()->json([
                "error" => "RECEIVER_NOT_FOUND",
            ], 404);
        }
        $receiverCommunity = Community::where('id', $receiver->community_id)->first();


        if (auth()->user()->community_id != $receiverCommunity->id) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $account = BankAccount::where('rib', $request->transmitter)->where('community_id', auth()->user()->community_id)->first();

        if (!isset($account)) {
            return response()->json([
                "error" => "TRANSMITTER_NOT_FOUND",
            ], 404);
        }

        if ($account->balance < $request->amount) {
            return response()->json([
                "error" => "INSUFFICIENT_FUNDS",
            ], 404);
        }

        $transaction = new BankTransaction();
        $transaction->amount = $request->amount;
        $transaction->transmitter = $request->transmitter;
        $transaction->receiver = $request->receiver;
        $transaction->description = $request->description ? $request->description : "";
        $transaction->community_id = auth()->user()->community_id;
        $transaction->save();

        $account->balance -= $request->amount;
        $account->save();

        $receiver->balance += $request->amount;
        $receiver->save();

        $transactionDone = [
            "transaction" => [
                "amount" => $transaction->amount,
                "transmitter" => $account,
                "receiver" => $receiver,
                "created_at" => $transaction->created_at,
                "id" => $transaction->id,
                "description" => $transaction->description,
            ],
            "transmitter" => $account,
            "receiver" => $receiver,
        ];


        broadcast(new TransactionEvent($transactionDone));

        return response()->json([
            "transaction" => [
                "amount" => $transaction->amount,
                "transmitter" => $account,
                "receiver" => $receiver,
                "created_at" => $transaction->created_at,
                "id" => $transaction->id,
                "description" => $transaction->description,
                "community_id" => $transaction->community_id,
            ],
            "account" => $account,
            "receiver" => $receiver,
        ], 200);
    }

    public function injectMoney(Request $request)
    {
        // retirer en anglais
        $request->validate([
            'amount' => 'required',
            'receiver' => 'required',   // rib receiver
        ]);

        if (auth()->user()->community_role != ('owner' || 'admin')) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 409);
        }

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $receiver = BankAccount::where('rib', $request->receiver)->first();

        if (!$receiver) {
            return response()->json([
                "error" => "RECEIVER_NOT_FOUND",
            ], 404);
        }
        $receiverCommunity = Community::where('id', $receiver->community_id)->first();


        if (auth()->user()->community_id != $receiverCommunity->id) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $transaction = new BankTransaction();
        $transaction->amount = $request->amount;
        $transaction->transmitter = "COMMUNITY";
        $transaction->receiver = $request->receiver;
        $transaction->description = $request->description ? $request->description : "";
        $transaction->community_id = auth()->user()->community_id;
        $transaction->save();

        $receiver->balance += $request->amount;
        $receiver->save();

        $transactionDone = [
            "amount" => $transaction->amount,
            "receiver" => $receiver,
        ];


        broadcast(new TransactionEvent($transactionDone));

        return response()->json([
            "transaction" => [
                "amount" => $transaction->amount,
                "transmitter" => auth()->user()->comminity_id,
                "receiver" => $receiver,
                "created_at" => $transaction->created_at,
                "id" => $transaction->id,
                "description" => $transaction->description,
                "community_id" => $transaction->community_id,
            ],
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BankTransaction  $bankTransaction
     * @return \Illuminate\Http\Response
     */
    public function show(BankTransaction $bankTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankTransaction  $bankTransaction
     * @return \Illuminate\Http\Response
     */
    public function edit(BankTransaction $bankTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankTransaction  $bankTransaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankTransaction $bankTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankTransaction  $bankTransaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankTransaction $bankTransaction)
    {
        //
    }
}
