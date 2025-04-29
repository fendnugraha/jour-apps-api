<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Journal;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\AccountResource;

class ChartOfAccountController extends Controller
{
    public $startDate;
    public $endDate;
    protected $appends = ['balance'];

    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->startDate = Carbon::now()->startOfMonth();
        $this->endDate = Carbon::now()->endOfMonth();
    }

    public function index()
    {
        $chartOfAccounts = ChartOfAccount::with(['account', 'warehouse'])->orderBy('acc_code')->paginate(10)->onEachSide(0);
        return new AccountResource($chartOfAccounts, true, "Successfully fetched chart of accounts");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $chartOfAccount = new ChartOfAccount();
        $request->validate(
            [
                'category_id' => 'required',  // Make sure category_id is present
                'name' => 'required|string|max:255|unique:chart_of_accounts,acc_name',
                'st_balance' => 'nullable|numeric',  // Allow st_balance to be nullable
            ],
            [
                'category_id.required' => 'Category account tidak boleh kosong.',
                'name.required' => 'Nama akun harus diisi.',
                'name.unique' => 'Nama akun sudah digunakan, silakan pilih nama lain.',
            ]
        );

        $chartOfAccount->create([
            'acc_code' => $chartOfAccount->acc_code($request->category_id),
            'acc_name' => $request->name,
            'account_id' => $request->category_id,
            'st_balance' => $request->st_balance ?? 0,
        ]);

        return response()->json([
            'message' => 'Chart of account created successfully',
            'chart_of_account' => $chartOfAccount
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $chartOfAccount = ChartOfAccount::with(['account', 'warehouse'])->find($id);
        return new AccountResource($chartOfAccount, true, "Successfully fetched chart of account");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChartOfAccount $chartOfAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $chartOfAccount = ChartOfAccount::find($request->id);
        $request->validate(
            [
                'id' => 'required|exists:chart_of_accounts,id',
                'acc_name' => 'required|string|max:255|unique:chart_of_accounts,acc_name,' . $chartOfAccount->id,
                'st_balance' => 'nullable|numeric',
            ],
            [
                'acc_name.required' => 'Nama akun harus diisi.',
                'acc_name.unique' => 'Nama akun sudah digunakan, silakan pilih nama lain. ID:' . $chartOfAccount->id,
            ]
        );

        try {
            $chartOfAccount->update([
                'acc_name' => $request->acc_name,
                'st_balance' => $request->st_balance ?? 0,
            ]);

            return response()->json([
                'message' => 'Chart of account updated successfully',
                'chart_of_account' => $chartOfAccount
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update chart of account: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update chart of account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $chartOfAccount = ChartOfAccount::find($id);

        if ($chartOfAccount->is_locked) {
            return response()->json([
                'message' => 'Chart of account is locked and cannot be deleted.',
            ], 403);
        }

        if (!$chartOfAccount) {
            return response()->json([
                'message' => 'Chart of account not found.',
            ], 404); // Return a 404 error if not found
        }

        try {
            $journalExists = Journal::where('debt_code', $chartOfAccount->acc_code)
                ->orWhere('cred_code', $chartOfAccount->acc_code)
                ->exists();

            if ($journalExists) {
                return response()->json([
                    'message' => 'Chart of account cannot be deleted because it is used in a journal entry.',
                ], 400);
            }
            // Deleting the Chart of Account
            $chartOfAccount->delete();

            // Return a success response
            return response()->json([
                'message' => 'Chart of account deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete chart of account. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getCashAndBankByWarehouse($warehouse)
    {
        $chartOfAccounts = ChartOfAccount::with('warehouse')->where('warehouse_id', $warehouse)->orderBy('acc_code', 'asc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Successfully fetched chart of accounts',
            'data' => $chartOfAccounts
        ]);
    }

    /*************  ✨ Codeium Command ⭐  *************/
    /**
     * Delete multiple Chart of Account records.
     *
     * This function deletes the specified Chart of Account records based on the provided IDs.
     * Prior to deletion, it checks if any of the records are locked. If locked records are found,
     * it returns a response indicating that some accounts are locked and cannot be deleted.
     * Otherwise, it proceeds to delete the records and returns a success response.
     *
     * @param Request $request The HTTP request containing the IDs of the Chart of Account records to be deleted.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the deletion operation.
     * If some accounts are locked, it returns a 403 status with the IDs of the locked accounts.
     * If deletion is successful, it returns a 200 status with the count of deleted records.
     */

    /******  f3f3cbc0-44ef-4107-98ae-33c5ad357b83  *******/
    public function deleteAll(Request $request)
    {
        // Retrieve the records that are about to be deleted
        $accounts = ChartOfAccount::whereIn('id', $request->ids)->get();

        // Check if any of the records are locked
        $lockedAccounts = $accounts->filter(function ($account) {
            return $account->is_locked;
        });

        if ($lockedAccounts->isNotEmpty()) {
            return response()->json(
                [
                    'message' => 'Some chart of accounts are locked and cannot be deleted.',
                    'locked_accounts' => $lockedAccounts->pluck('id'), // Optionally return the ids of locked accounts
                ],
                403
            );
        }

        // Perform the deletion if no accounts are locked
        $deletedCount = ChartOfAccount::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => 'All chart of accounts deleted successfully',
            'deleted_count' => $deletedCount
        ], 200);
    }

    public function getCashAndBank()
    {
        $chartOfAccounts = ChartOfAccount::with('warehouse')->whereIn('account_id', [1, 2])->orderBy('acc_code', 'asc')->get();
        return new AccountResource($chartOfAccounts, true, "Successfully fetched chart of accounts");
    }

    public function profitLossReport($startDate, $endDate)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $journal = new Journal();

        $journalCount = $journal->journalCount(Carbon::create(0000, 1, 1)->endOfDay(), $endDate);
        $profitLoss = $journalCount['revenue']->flatten()->sum('balance') - $journalCount['cost']->flatten()->sum('balance') - $journalCount['expense']->flatten()->sum('balance');

        $profitloss = [
            'revenue' => [
                'total' => $journalCount['revenue']->flatten()->sum('balance'),
                'accounts' => $journalCount['revenue']->map(function ($a) {
                    return [
                        'acc_name' => $a->first()->account->name,
                        'balance' => intval($a->sum('balance')),
                        'coa' => $a->map(function ($coa) {
                            return [
                                'acc_name' => $coa->acc_name,
                                'balance' => intval($coa->balance)
                            ];
                        })
                            ->values()
                            ->toArray()
                    ];
                })
                    ->values()
                    ->toArray()
            ],
            'cost' => [
                'total' => $journalCount['cost']->flatten()->sum('balance'),
                'accounts' => $journalCount['cost']->map(function ($a) {
                    return [
                        'acc_name' => $a->first()->account->name,
                        'balance' => intval($a->sum('balance')),
                        'coa' => $a->map(function ($coa) {
                            return [
                                'acc_name' => $coa->acc_name,
                                'balance' => intval($coa->balance)
                            ];
                        })
                            ->values()
                            ->toArray()
                    ];
                })
                    ->values()
                    ->toArray()
            ],
            'expense' => [
                'total' => $journalCount['expense']->flatten()->sum('balance'),
                'accounts' => $journalCount['expense']->map(function ($a) {
                    return [
                        'acc_name' => $a->first()->account->name,
                        'balance' => intval($a->sum('balance')),
                        'coa' => $a->map(function ($coa) {
                            return [
                                'acc_name' => $coa->acc_name,
                                'balance' => intval($coa->balance)
                            ];
                        })
                            ->values()
                            ->toArray()
                    ];
                })
                    ->values()
                    ->toArray()
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Successfully fetched balance sheet',
            'data' => $profitloss
        ]);
    }

    public function balanceSheetReport($startDate, $endDate)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $journal = new Journal();

        $journalCount = $journal->journalCount(Carbon::create(0000, 1, 1)->endOfDay(), $endDate);
        $profitLoss = $journalCount['revenue']->flatten()->sum('balance') - $journalCount['cost']->flatten()->sum('balance') - $journalCount['expense']->flatten()->sum('balance');

        $balanceSheet = [
            'assets' => [
                'total' => $journalCount['assets']->flatten()->sum('balance'),
                'accounts' => $journalCount['assets']->map(function ($a) {
                    return [
                        'acc_name' => $a->first()->account->name,
                        'balance' => intval($a->sum('balance')),
                        'coa' => $a->map(function ($coa) {
                            return [
                                'acc_name' => $coa->acc_name,
                                'balance' => intval($coa->balance)
                            ];
                        })
                            ->values()
                            ->toArray()
                    ];
                })
                    ->values()
                    ->toArray()
            ],
            'liabilities' => [
                'total' => $journalCount['liabilities']->flatten()->sum('balance'),
                'accounts' => $journalCount['liabilities']->map(function ($a) {
                    return [
                        'acc_name' => $a->first()->account->name,
                        'balance' => intval($a->sum('balance')),
                        'coa' => $a->map(function ($coa) {
                            return [
                                'acc_name' => $coa->acc_name,
                                'balance' => intval($coa->balance)
                            ];
                        })
                            ->values()
                            ->toArray()
                    ];
                })
                    ->values()
                    ->toArray()
            ],
            'equity' => [
                'total' => $journalCount['equity']->flatten()->sum('balance'),
                'accounts' => $journalCount['equity']->map(function ($a) {
                    return [
                        'acc_name' => $a->first()->account->name,
                        'balance' => intval($a->sum('balance')),
                        'coa' => $a->map(function ($coa) {
                            return [
                                'acc_name' => $coa->acc_name,
                                'balance' => intval($coa->balance)
                            ];
                        })
                            ->values()
                            ->toArray()
                    ];
                })
                    ->values()
                    ->toArray()
            ],
            'profitloss' => $profitLoss
        ];

        return response()->json([
            'success' => true,
            'message' => 'Successfully fetched balance sheet',
            'data' => $balanceSheet
        ]);
    }

    public function addCashAndBankToWarehouse($warehouse, $id)
    {
        $chartOfAccount = ChartOfAccount::find($id);

        if (!$warehouse || !$chartOfAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse or chart of account not found'
            ], 404);
        }
        $updateValue = $chartOfAccount->warehouse_id ? null : $warehouse;
        $chartOfAccount->update(['warehouse_id' => $updateValue]);

        $message = $chartOfAccount->warehouse_id ? 'Cash and bank account added to warehouse' : 'Cash and bank account removed from warehouse';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $chartOfAccount
        ]);
    }

    public function getExpenses()
    {
        $chartOfAccounts = ChartOfAccount::whereIn('account_id', range(33, 45))->get();
        return new AccountResource($chartOfAccounts, true, "Successfully fetched chart of accounts");
    }

    public function getCashBankBalance($warehouse, $endDate)
    {

        $journal = new Journal();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $transactions = $journal->with(['debt', 'cred'])
            ->selectRaw('debt_code, cred_code, SUM(amount) as total, warehouse_id')
            ->whereBetween('date_issued', [Carbon::create(0000, 1, 1, 0, 0, 0)->startOfDay(), $endDate])
            // ->where('warehouse_id', Auth::user()->warehouse_id) // Tambahkan filter di query
            ->groupBy('debt_code', 'cred_code', 'warehouse_id')
            ->get();

        $chartOfAccounts = ChartOfAccount::with(['account'])->where('warehouse_id', $warehouse)->get();

        foreach ($chartOfAccounts as $value) {
            $debit = $transactions->where('debt_code', $value->id)->sum('total');
            $credit = $transactions->where('cred_code', $value->id)->sum('total');

            $value->balance = ($value->account->status == "D")
                ? ($value->st_balance + $debit - $credit)
                : ($value->st_balance + $credit - $debit);
        }

        return new AccountResource($chartOfAccounts, true, "Successfully fetched chart of accounts");
    }

    public function dailyDashboard($warehouse, $startDate, $endDate)
    {
        $journal = new Journal();

        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $transactions = $journal->selectRaw('debt_code, cred_code, SUM(amount) as total, warehouse_id')
            ->whereBetween('date_issued', [Carbon::create(0000, 1, 1, 0, 0, 0)->startOfDay(), $endDate])
            // ->where('warehouse_id', $warehouse)
            ->groupBy('debt_code', 'cred_code', 'warehouse_id')
            ->get();

        $chartOfAccounts = ChartOfAccount::with(['account'])->where(fn($query) => $warehouse == "all" ? $query : $query->where('warehouse_id', $warehouse))->get();

        foreach ($chartOfAccounts as $value) {
            $debit = $transactions->where('debt_code', $value->id)->sum('total');
            $credit = $transactions->where('cred_code', $value->id)->sum('total');

            $value->balance = ($value->account->status == "D")
                ? ($value->st_balance + $debit - $credit)
                : ($value->st_balance + $credit - $debit);
        }

        $trxForSalesCount = $journal->whereBetween('date_issued', [$startDate, $endDate])
            ->where(fn($query) => $warehouse == "all" ?
                $query : $query->where('warehouse_id', $warehouse))
            ->get();

        $dailyReport = [
            'totalCash' => $chartOfAccounts->where('account_id', 1)->sum('balance'),
            'totalBank' => $chartOfAccounts->where('account_id', 2)->sum('balance'),
            'totalTransfer' => $trxForSalesCount->where('trx_type', 'Transfer Uang')->sum('amount'),
            'totalCashWithdrawal' => $trxForSalesCount->where('trx_type', 'Tarik Tunai')->sum('amount'),
            'totalCashDeposit' => $trxForSalesCount->where('trx_type', 'Deposit')->sum('amount'),
            'totalVoucher' => $trxForSalesCount->where('trx_type', 'Voucher & SP')->sum('amount'),
            'totalAccessories' => $trxForSalesCount->where('trx_type', 'Accessories')->sum('amount'),
            'totalExpense' => $trxForSalesCount->where('trx_type', 'Pengeluaran')->sum('fee_amount'),
            'totalFee' => $trxForSalesCount->where('fee_amount', '>', 0)->sum('fee_amount'),
            'profit' => $trxForSalesCount->sum('fee_amount'),
            'salesCount' => $trxForSalesCount->whereIn('trx_type', ['Transfer Uang', 'Tarik Tunai', 'Deposit', 'Voucher & SP'])->count(),
        ];

        return new AccountResource($dailyReport, true, "Successfully fetched chart of accounts");
    }

    public function getAllAccounts()
    {
        $chartOfAccounts = ChartOfAccount::with(['account'])->orderBy('acc_code')->get();
        return new AccountResource($chartOfAccounts, true, "Successfully fetched chart of accounts");
    }

    public function getAccountByAccountId(Request $request)
    {
        $accountIds = $request->input('account_ids', []);

        // Ensure it's an array
        if (!is_array($accountIds)) {
            $accountIds = explode(',', $accountIds); // Convert comma-separated values into an array
        }

        $chartOfAccounts = ChartOfAccount::with(['account'])
            ->whereIn('account_id', $accountIds)
            ->orderBy('acc_code')
            ->get();

        return new AccountResource($chartOfAccounts, true, "Successfully fetched chart of accounts");
    }
}
