<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CompanyEmployees;
use Illuminate\Http\Request;

class CompanyEmployeesController extends Controller
{
    public function addEmployee(Request $request)
    {
        $request->validate([
            'rib' => 'required',
            'company_id' => 'required',
        ]);

        $employeeBankAccount = BankAccount::where('rib', $request->rib)->first();

        if (!$employeeBankAccount) {
            return response()->json([
                "error" => "USER_NOT_FOUND",
            ], 404);
        }

        $exists = CompanyEmployees::where('rib', $request->rib)
            ->where('bank_account_id', $request->company_id)
            ->exists();

        if ($exists) {
            return response()->json(["error" => "USER_ALREADY_IN_COMPANY"], 409);
        }

        $companyBankAccount = BankAccount::where('id', $request->company_id)->first();

        if (!$companyBankAccount) {
            return response()->json([
                "error" => "COMPANY_NOT_FOUND",
            ], 404);
        }

        if ($companyBankAccount->user_id != $request->user()->id) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 401);
        }

        $date = new \DateTime();
        $date->modify('-12 hours');
        $date->setTimezone(new \DateTimeZone('Europe/Paris'));

        $employee = CompanyEmployees::create([
            'rib' => $request->rib,
            'bank_account_id' => $request->company_id,
            'user_id' => $employeeBankAccount->user_id,
            'last_payment' => $date
        ]);

        return response()->json([
            "employee" => $employee,
        ], 200);
    }

    public function fireEmployee(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'company_id' => 'required',
        ]);

        $companyAccount = BankAccount::where('id', $request->company_id)->first();

        if (!$companyAccount) {
            return response()->json([
                "error" => "COMPANY_NOT_FOUND",
            ], 404);
        }

        if ($companyAccount->user_id != $request->user()->id) {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 401);
        }

        $employee = CompanyEmployees::where('user_id', $request->user_id)
            ->where('bank_account_id', $request->company_id)
            ->first();

        if (!$employee) {
            return response()->json([
                "error" => "USER_NOT_IN_COMPANY",
            ], 404);
        }

        $employee->delete();

        return response()->json([
            "employee" => $employee,
        ], 200);
    }
}
