<?php

namespace App\Http\Controllers;

use App\Events\TransactionEvent;
use App\Models\BankAccount;
use App\Models\BankInvoices;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class BankInvoicesController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string',
            'receiver' => 'required|string',
            'transmitter' => 'required|string',
        ]);

        if (BankAccount::where('rib', $request->transmitter)->count() == 0) {
            return response()->json([
                'error' => 'TRANSMITTER_NOT_FOUND',
            ], 404);
        }

        if (BankAccount::where('rib', $request->receiver)->count() == 0) {
            return response()->json([
                'error' => 'RECEIVER_NOT_FOUND',
            ], 404);
        }

        $invoice = BankInvoices::create([
            'amount' => $request->amount,
            'description' => $request->description,
            'receiver' => $request->receiver,
            'transmitter' => $request->transmitter,
        ]);

        return response()->json([
            'invoice' => $invoice,
        ]);
    }

    public function awnser(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|numeric',
            'status' => 'required|in:accepted,refuse',
        ]);

        // get user bank account
        $bank_account_customer = $request->user()->personnalAccount;

        // get invoice
        $invoice = BankInvoices::where('id', $request->invoice_id)
            ->where('receiver', $bank_account_customer->rib)
            ->where('status', 'pending')->first();
        if ($invoice == null) {
            return response()->json([
                'error' => 'INVOICE_NOT_FOUND',
            ], 404);
        }

        // get bank enterprise account
        $bank_account_enterprise = BankAccount::where('rib', $invoice->transmitter)->first();
        $transaction = new BankTransaction();

        if ($request->status == 'accepted') {

            // check if custom account has sufficient balance
            if ($bank_account_customer->balance < $invoice->amount) {
                return response()->json([
                    'error' => 'INSUFFICIENT_FUNDS',
                ], 400);
            }
            $bank_account_customer->balance -= $invoice->amount;
            $bank_account_enterprise->balance += $invoice->amount;

            $transaction->amount = $invoice->amout;
            $transaction->transmitter = $invoice->transmitter;
            $transaction->receiver = $invoice->receiver;
            $transaction->description = "Facture " . $bank_account_enterprise->name . " acceptée";
            $transaction->community_id = auth()->user()->community_id;
        } else {
            $transaction->amount = 0;
            $transaction->transmitter = $invoice->transmitter;
            $transaction->receiver = $invoice->receiver;
            $transaction->description = "Facture " . $bank_account_enterprise->name . " refusée";
            $transaction->community_id = auth()->user()->community_id;
        }
        $transaction->save();

        // send event transaction
        $transactionDone = [
            "amount" => $invoice->amout,
            "receiver" => $bank_account_enterprise,
        ];
        broadcast(new TransactionEvent($transactionDone));
        $invoice->delete();
    }
}
