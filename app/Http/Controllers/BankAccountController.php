<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $accounts = BankAccount::where('user_id', auth()->user()->id)->get();

        // $transactions =  BankTransaction::where('transmitter', auth()->user()->id)
        //     ->orWhere('receiver', auth()->user()->id)
        //     ->orderby('created_at', 'desc')
        //     ->get();

        $ribs = [];
        $transactions = [];
        $incoming_money = 0;
        $outgoing_money = 0;

        foreach ($accounts as $account) {
            array_push($ribs, $account->rib);
        }

        foreach ($ribs as $rib) {
            $tmp = BankTransaction::where('transmitter', $rib)
                ->orWhere('receiver', $rib)
                ->orderby('created_at', 'desc')
                ->get();


            foreach ($tmp as $transaction) {
                $transmitter = BankAccount::where('rib', $transaction->transmitter)->first();
                $receiver = BankAccount::where('rib', $transaction->receiver)->first();

                if ($transaction->transmitter == $rib) {
                    $outgoing_money += $transaction->amount;
                } else {
                    $incoming_money += $transaction->amount;
                }


                array_push($transactions, [
                    'amount' => $transaction->amount,
                    'transaction' => $transaction,
                    'transmitter' => $transmitter,
                    'receiver' => $receiver,
                    'created_at' => $transaction->created_at,
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                ]);
            }
        }

        $transactions = array_reverse($transactions);

        return response()->json([
            "accounts" => $accounts,
            "transactions" => $transactions,
            "currency" => auth()->user()->community->currency,
            "statistics" => [
                "incoming" => $incoming_money,
                "outgoing" => $outgoing_money
            ]
        ], 200);
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
            'name' => 'required',
            'type' => 'required',
        ]);

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_HAS_NO_COMMUNITY",
            ], 409);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function show(BankAccount $bankAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(BankAccount $bankAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankAccount $bankAccount)
    {
        //
    }
}
