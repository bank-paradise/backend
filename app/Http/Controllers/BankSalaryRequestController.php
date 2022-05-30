<?php

namespace App\Http\Controllers;

use App\Events\TransactionEvent;
use App\Models\BankSalaryRequest;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class BankSalaryRequestController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'salary' => 'required|numeric',
            'description' => 'required|string',
        ]);

        $lastRequest = BankSalaryRequest::where('bank_account_id', $request->bank_account_id)->where('status', 'accepted')->orderBy('created_at', 'desc')->first();

        if ($lastRequest && $lastRequest->created_at->diffInHours(now()) < 12) {
            return response()->json([
                "error" => "TOO_SOON",
            ], 400);
        }

        $salaryRequest = BankSalaryRequest::create([
            'bank_account_id' => $request->bank_account_id,
            'salary' => $request->salary,
            'description' => $request->description,
            'status' => 'waiting',
        ]);

        return response()->json([
            'salary_request' => $salaryRequest,
        ]);
    }

    public function answer(Request $request)
    {
        $request->validate([
            'salary_request_id' => 'required|exists:bank_salary_requests,id',
            'status' => 'required|in:accepted,refuse',
        ]);

        if (!in_array($request->user()->community_role, ['owner', 'admin'])) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 403);
        }

        $salaryRequest = BankSalaryRequest::find($request->salary_request_id);

        if ($salaryRequest->bankAccount->community_id != $request->user()->community_id) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 403);
        }

        if ($salaryRequest->status !== 'waiting') {
            return response()->json([
                "error" => "ALREADY_ANSWERED",
            ], 400);
        }

        $salaryRequest->update([
            'status' => $request->status,
        ]);

        $transaction = new BankTransaction();
        $transaction->amount = $request->status === 'accepted' ? $salaryRequest->salary : 0;
        $transaction->transmitter = "COMMUNITY";
        $transaction->receiver = $salaryRequest->bankAccount->rib;
        $transaction->description = $request->status === 'accepted' ? 'Salaire accepté' : 'Salaire refusé';
        $transaction->community_id = auth()->user()->community_id;
        $transaction->save();

        $salaryRequest->bankAccount->update([
            'balance' => $salaryRequest->bankAccount->balance + $transaction->amount,
        ]);

        $transactionDone = [
            "amount" => $transaction->amount,
            "receiver" => $salaryRequest->bankAccount,
        ];

        broadcast(new TransactionEvent($transactionDone));

        return response()->json([
            'salary_request' => $salaryRequest,
        ]);
    }

    public function getLast(Request $request)
    {
        $personnalBankAccount = $request->user()->personnalAccount;

        $lastRequest = BankSalaryRequest::where('bank_account_id', $personnalBankAccount->id)->orderBy('created_at', 'desc')->first();

        return response()->json([
            'last_request' => $lastRequest,
        ]);
    }

    public function getAll(Request $request)
    {
        if (!in_array($request->user()->community_role, ['owner', 'admin'])) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 403);
        }

        $salaryRequestsFinished = [];
        $salaryRequestsWaiting = [];

        $communityAccounts = $request->user()->community->bankAccounts;

        foreach ($communityAccounts as $communityAccount) {
            $salaryRequests = BankSalaryRequest::where('bank_account_id', $communityAccount->id)->orderBy('created_at', 'desc')->get();
            foreach ($salaryRequests as $salaryRequest) {
                if ($salaryRequest->status === 'waiting') {
                    $salaryRequestsWaiting[] = ["salary" => $salaryRequest, "bank_account" => $communityAccount];
                } else {
                    $salaryRequestsFinished[] = ["salary" => $salaryRequest, "bank_account" => $communityAccount];
                }
            }
        }

        // trier les salaires par date et classer chaque jour dans un tableau
        $salaryRequestsFinished = array_map(function ($salaryRequest) {
            $salaryRequest['date'] = $salaryRequest['salary']->created_at->format('Y-m-d');
            return $salaryRequest;
        }, $salaryRequestsFinished);

        $salaryRequestsFinished = array_reduce($salaryRequestsFinished, function ($carry, $item) {
            if (!isset($carry[$item['date']])) {
                $carry[$item['date']] = [];
            }
            $carry[$item['date']][] = $item;
            return $carry;
        }, []);


        return response()->json([
            'salary_requests_waiting' => $salaryRequestsWaiting,
            'salary_requests_finished' => $salaryRequestsFinished,
        ]);
    }
}
