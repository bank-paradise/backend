<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\CompanyEmployees;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BankAccountController extends Controller
{


    public function get()
    {

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $accounts = [];

        foreach (auth()->user()->bankAccounts()->get() as $account) {
            if ($account->type == 'professional') {
                $employees = CompanyEmployees::where('bank_account_id', $account->id)->get();
                array_push($accounts, [
                    "id" => $account->id,
                    "balance" => $account->balance,
                    "name" => $account->name,
                    "rib" => $account->rib,
                    "type" => $account->type,
                    "user_id" => $account->user_id,
                    "community_id" => $account->community_id,
                    "employees" => $this->getEmployees($account->id),
                ]);
            } else {
                array_push($accounts, [
                    "id" => $account->id,
                    "balance" => $account->balance,
                    "name" => $account->name,
                    "rib" => $account->rib,
                    "type" => $account->type,
                    "user_id" => $account->user_id,
                    "community_id" => $account->community_id,
                ]);
            }
        }

        $ribs = [];
        $transactions = [];
        $incoming_money = 0;
        $outgoing_money = 0;

        foreach ($accounts as $account) {
            array_push($ribs, $account['rib']);
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

        // trouver les transactions en double et les supprimer
        $transactions = $this->removeDuplicates($transactions);

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

    public function removeDuplicates($transactions)
    {
        $tmp = [];
        foreach ($transactions as $transaction) {
            if (!in_array($transaction, $tmp)) {
                array_push($tmp, $transaction);
            }
        }
        return $tmp;
    }

    public function getAllAccounts()
    {
        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_NOT_IN_A_COMMUNITY",
            ], 404);
        }

        $persoAccounts = BankAccount::where('community_id', auth()->user()->community_id)->where('type', 'personnal')->get();

        $profAccounts = BankAccount::where('community_id', auth()->user()->community_id)->where('type', 'professional')->get();

        return response()->json([
            "personnal" => $persoAccounts,
            "professional" => $profAccounts
        ], 200);
    }

    // fonction qui decode les employés et cherche leur pseudo selon leur id
    public function getEmployees($id)
    {
        $employees = CompanyEmployees::where('bank_account_id', $id)->get();
        $employees_array = [];

        foreach ($employees as $employee) {
            $user = User::where('id', $employee->user_id)->first();
            array_push($employees_array, [
                "id" => $employee->id,
                "user_id" => $employee->user_id,
                "pseudo" => $user->name,
                "grade" => $employee->grade,
                "last_payment" => $employee->last_payment,
                "salary" => $employee->salary,
                "rib" => $employee->rib,
            ]);
        }

        return $employees_array;
    }

    public function createCompanyAccount(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_HAS_NO_COMMUNITY",
            ], 409);
        }

        if (auth()->user()->bankAccounts()->count() >= 4) {
            return response()->json([
                "error" => "USER_HAS_ALREADY_4_BANK_ACCOUNTS",
            ], 409);
        }

        $account = new BankAccount();
        $account->name = $request->name;
        $account->type = "professional";
        $account->rib = Str::uuid();
        $account->user_id = auth()->user()->id;
        $account->community_id = auth()->user()->community_id;
        $account->save();

        $date = new \DateTime();
        $date->modify('-12 hours');
        $date->setTimezone(new \DateTimeZone('Europe/Paris'));

        $personalAccount = BankAccount::where('user_id', auth()->user()->id)->where('type', 'personnal')->first();

        $company = new CompanyEmployees();
        $company->bank_account_id = $account->id;
        $company->user_id = auth()->user()->id;
        $company->grade = 'boss';
        $company->last_payment = $date;
        $company->rib = $personalAccount->rib;
        $company->save();

        return response()->json([
            "account" => [
                "id" => $account->id,
                "balance" => $account->balance,
                "name" => $account->name,
                "rib" => $account->rib,
                "type" => $account->type,
                "user_id" => $account->user_id,
                "community_id" => $account->community_id,
                "employees" => $this->getEmployees($account->id),
            ],
        ], 200);
    }

    public function removeCompany(Request $request)
    {

        if (!isset(auth()->user()->community_id)) {
            return response()->json([
                "error" => "USER_HAS_NO_COMMUNITY",
            ], 409);
        }

        $company = BankAccount::where('id', $request->id)->first();

        if ($company->user_id != auth()->user()->id) {
            return response()->json([
                "error" => "USER_HAS_NO_PERMISSION",
            ], 409);
        }

        $company->user_id = null;
        $company->name = $company->name . '_' . time() . ' {{DELETED}}';
        $company->save();

        $company->employees()->delete();

        return response()->json([
            "account" => [
                "id" => $company->id,
                "balance" => $company->balance,
                "name" => $company->name,
                "rib" => $company->rib,
                "type" => $company->type,
                "user_id" => $company->user_id,
                "community_id" => $company->community_id,
            ],
        ], 200);
    }
}
