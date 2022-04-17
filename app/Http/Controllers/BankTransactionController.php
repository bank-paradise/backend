<?php

namespace App\Http\Controllers;

use App\Events\TransactionEvent;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Community;
use App\Models\CompanyEmployees;
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

    public function sendSalary(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'amount' => 'required',
            'receiver' => 'required',   // rib receiver
        ]);

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $companyAccount = BankAccount::where('community_id', auth()->user()->community_id)->where('id', $request->company_id)->first();

        if (!$companyAccount) {
            return response()->json([
                "error" => "COMPANY_NOT_FOUND",
            ], 404);
        }

        if ($companyAccount->user_id != auth()->user()->id) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 404);
        }

        if ($companyAccount->balance < $request->amount) {
            return response()->json([
                "error" => "INSUFFICIENT_FUNDS",
            ], 404);
        }

        $receiver = BankAccount::where('rib', $request->receiver)->where('community_id', auth()->user()->community_id)->first();

        if (!$receiver) {
            return response()->json([
                "error" => "RECEIVER_NOT_FOUND",
            ], 404);
        }

        $transaction = new BankTransaction();
        $transaction->amount = $request->amount;
        $transaction->transmitter = $companyAccount->rib;
        $transaction->receiver = $receiver->rib;
        $transaction->description = "Salaire de la société " . $companyAccount->name;
        $transaction->community_id = auth()->user()->community_id;
        $transaction->save();

        $companyAccount->balance -= $request->amount;
        $companyAccount->save();

        $receiver->balance += $request->amount;
        $receiver->save();

        $company = CompanyEmployees::where('bank_account_id', $companyAccount->id)->first();

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('Europe/Paris'));
        $date = $date->format('Y-m-d H:i:s');
        $employees = json_decode($company->employees);
        foreach ($employees as $employee) {
            if ($employee->rib == $receiver->rib) {
                $employee->last_payment = $date;
            }
        }
        $company->employees = json_encode($employees);
        $company->save();

        $transactionDone = [
            "amount" => $transaction->amount,
            "receiver" => $receiver,
        ];
        broadcast(new TransactionEvent($transactionDone));

        return response()->json([
            "account" => [
                "id" => $companyAccount->id,
                "balance" => $companyAccount->balance,
                "name" => $companyAccount->name,
                "rib" => $companyAccount->rib,
                "user_id" => $companyAccount->user_id,
                "community_id" => $companyAccount->community_id,
                "employees" => $employees,
            ]
        ], 200);
    }

    public function changeSalary(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'amount' => 'required',
            'user_id' => 'required',
        ]);

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $employees = CompanyEmployees::where('bank_account_id', $request->company_id)->first();

        if (!$employees) {
            return response()->json([
                "error" => "COMPANY_NOT_FOUND",
            ], 404);
        }

        $employees = json_decode($employees->employees);

        foreach ($employees as $employee) {
            if ($employee->user_id == $request->user_id) {
                $employee->salary = $request->amount;
            }
        }

        $employees = json_encode($employees);

        $company = CompanyEmployees::where('bank_account_id', $request->company_id)->first();
        $company->employees = $employees;
        $company->save();

        return response()->json([
            "account" => [
                "id" => $company->bank_account_id,
                "balance" => $company->balance,
                "name" => $company->name,
                "rib" => $company->rib,
                "user_id" => $company->user_id,
                "community_id" => $company->community_id,
                "employees" => $employees,
            ]
        ], 200);
    }
}
