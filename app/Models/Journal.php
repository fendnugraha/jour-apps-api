<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Transaction;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function scopeFilterJournals($query, array $filters)
    {
        $query->when(!empty($filters['search']), function ($query) use ($filters) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('invoice', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('cred_code', 'like', '%' . $search . '%')
                    ->orWhere('debt_code', 'like', '%' . $search . '%')
                    ->orWhere('date_issued', 'like', '%' . $search . '%')
                    ->orWhere('trx_type', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('amount', 'like', '%' . $search . '%')
                    ->orWhere('fee_amount', 'like', '%' . $search . '%')
                    ->orWhereHas('debt', function ($query) use ($search) {
                        $query->where('acc_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('cred', function ($query) use ($search) {
                        $query->where('acc_name', 'like', '%' . $search . '%');
                    });
            });
        });
    }

    public function scopeFilterAccounts($query, array $filters)
    {
        $query->when(!empty($filters['account']), function ($query) use ($filters) {
            $account = $filters['account'];
            $query->where('cred_code', $account)->orWhere('debt_code', $account);
        });
    }

    public function scopeFilterMutation($query, array $filters)
    {
        $query->when($filters['searchHistory'] ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('debt', function ($q) use ($search) {
                    $q->where('acc_name', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('cred', function ($q) use ($search) {
                        $q->where('acc_name', 'like', '%' . $search . '%');
                    });
            });
        });
    }

    public function debt()
    {
        return $this->belongsTo(ChartOfAccount::class, 'debt_code', 'id');
    }

    public function cred()
    {
        return $this->belongsTo(ChartOfAccount::class, 'cred_code', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'invoice', 'invoice');
    }

    public function finance()
    {
        return $this->belongsTo(Finance::class, 'invoice', 'invoice');
    }

    public static function invoice_journal()
    {
        // Ambil nilai MAX(RIGHT(invoice, 7)) untuk user saat ini dan hari ini
        $lastInvoice = DB::table('journals')
            ->where('user_id', auth()->user()->id)
            ->where('trx_type', '!=', 'Sales')
            ->where('trx_type', '!=', 'Purchase')
            ->whereDate('created_at', today())
            ->max(DB::raw('RIGHT(invoice, 7)')); // Gunakan max langsung

        // Tentukan nomor urut invoice
        $kd = $lastInvoice ? (int)$lastInvoice + 1 : 1; // Jika ada, tambahkan 1, jika tidak mulai dari 1

        // Kembalikan format invoice
        return 'JR.BK.' . now()->format('dmY') . '.' . auth()->user()->id . '.' . str_pad($kd, 7, '0', STR_PAD_LEFT);
    }

    public static function generate_invoice_journal($prefix, $table, $condition = [])
    {
        // Ambil nilai MAX(RIGHT(invoice, 7)) berdasarkan kondisi user dan tanggal
        $lastInvoice = DB::table($table)
            ->where('user_id', auth()->user()->id)
            ->whereDate('created_at', today())
            ->where($condition)
            ->max(DB::raw('RIGHT(invoice, 7)')); // Ambil nomor invoice terakhir (7 digit)

        // Tentukan nomor urut invoice
        $kd = $lastInvoice ? (int)$lastInvoice + 1 : 1; // Jika ada invoice, tambahkan 1, jika tidak mulai dari 1

        // Kembalikan format invoice
        return $prefix . '.' . now()->format('dmY') . '.' . auth()->user()->id . '.' . str_pad($kd, 7, '0', STR_PAD_LEFT);
    }

    public function sales_journal()
    {
        return $this->generate_invoice_journal('SO.BK', 'transactions', [['transaction_type', '=', 'Sales']]);
    }

    public function purchase_journal()
    {
        // Untuk purchase journal, kita menambahkan kondisi agar hanya mengembalikan yang quantity > 0
        return $this->generate_invoice_journal('PO.BK', 'transactions', [['quantity', '>', 0], ['transaction_type', '=', 'Purchase']]);
    }

    public static function stock_adjustment_invoice()
    {
        return self::generate_invoice_journal('SA.BK', 'transactions', [['transaction_type', '=', 'Stock Adjustment']]);
    }

    public static function payable_invoice($contact_id)
    {
        return self::generate_invoice_journal('PY.BK.' . $contact_id, 'payables', [['contact_id', '=', $contact_id], ['payment_nth', '=', 0]]);
    }

    public static function receivable_invoice($contact_id)
    {
        return self::generate_invoice_journal('RC.BK.' . $contact_id, 'receivables', [['contact_id', '=', $contact_id], ['payment_nth', '=', 0]]);
    }

    public static function endBalanceBetweenDate($account_code, $start_date, $end_date)
    {
        $initBalance = ChartOfAccount::with('account')->find($account_code);

        if (!$initBalance || !$initBalance->account) {
            Log::error('Account not found: ' . $account_code);
            return 0; // atau lempar error/logging
        }

        $journals = Journal::selectRaw('debt_code, cred_code, SUM(amount) as total')
            ->whereBetween('date_issued', [$start_date, $end_date])
            ->groupBy('debt_code', 'cred_code')
            ->get();

        $debit = $journals->where('debt_code', $account_code)->sum('total');
        $credit = $journals->where('cred_code', $account_code)->sum('total');

        return $initBalance->account->status === 'D'
            ? $initBalance->st_balance + $debit - $credit
            : $initBalance->st_balance + $credit - $debit;
    }


    public static function equityCount($end_date, $includeEquity = true)
    {
        $coa = ChartOfAccount::all();

        foreach ($coa as $coaItem) {
            $coaItem->balance = self::endBalanceBetweenDate($coaItem->id, '0000-00-00', $end_date);
        }

        $initBalance = $coa->where('id', '30100-001')->first()->st_balance;
        $assets = $coa->whereIn('account_id', \range(1, 18))->sum('balance');
        $liabilities = $coa->whereIn('account_id', \range(19, 25))->sum('balance');
        $equity = $coa->where('account_id', 26)->sum('balance');

        // Use Eloquent to update a specific record
        ChartOfAccount::where('id', '30100-001')->update(['st_balance' => $initBalance + $assets - $liabilities - $equity]);

        // Return the calculated equity
        return ($includeEquity ? $initBalance : 0) + $assets - $liabilities - ($includeEquity ? $equity : 0);
    }

    public function cashflowCount($start_date, $end_date)
    {
        $cashAccount = ChartOfAccount::whereIn('account_id', [1, 2])->get();

        $transactions = $this->selectRaw('debt_code, cred_code, SUM(amount) as total')
            ->whereBetween('date_issued', [$start_date, $end_date])
            ->groupBy('debt_code', 'cred_code')
            ->get();

        foreach ($cashAccount as $value) {
            $debit = $transactions->where('debt_code', $value->id)->sum('total');

            $credit = $transactions->where('cred_code', $value->id)->sum('total');

            $value->balance = $debit - $credit;
        }

        $result = $cashAccount->whereIn('account_id', [1, 2])->sum('balance');

        return $result;
    }

    public function journalCount($startDate, $endDate)
    {
        $previousDate = $endDate->copy()->subDay()->toDateString(); // Tanggal untuk mencari saldo awal
        // Log::info("startDate: " . $startDate . " endDate: " . $endDate . " previousDate: " . $previousDate);
        // Log::info('previousDate String: ' . $previousDate . ' endDate String: ' . $endDate->toDateString());
        $sDate = Carbon::parse($endDate)->subDay();
        Log::info('sDate: ' . $sDate . ', endDate: ' . $endDate);
        $eDateTostring = $endDate->toDateString();

        $chartOfAccounts = ChartOfAccount::with('account')->get();

        // Dapatkan semua ID akun untuk kueri berikutnya
        $allAccountIds = $chartOfAccounts->pluck('id')->toArray();

        $previousDayBalances = AccountBalance::whereIn('chart_of_account_id', $allAccountIds)
            ->where('balance_date', $previousDate)
            ->pluck('ending_balance', 'chart_of_account_id')
            ->toArray();

        // 3. Pre-fetch total debit aktivitas untuk HANYA tanggal $endDate
        $dailyDebits = Journal::selectRaw('debt_code as account_id, SUM(amount) as total_amount')
            ->whereIn('debt_code', $allAccountIds)
            ->whereDate('date_issued', $eDateTostring) // HANYA AKTIVITAS HARI INI
            ->groupBy('debt_code')
            ->pluck('total_amount', 'account_id')
            ->toArray();
        // Log::info('dailyDebits: ' . json_encode($dailyDebits));

        // 4. Pre-fetch total credit aktivitas untuk HANYA tanggal $endDate
        $dailyCredits = Journal::selectRaw('cred_code as account_id, SUM(amount) as total_amount')
            ->whereIn('cred_code', $allAccountIds)
            ->whereDate('date_issued', $eDateTostring) // HANYA AKTIVITAS HARI INI
            ->groupBy('cred_code')
            ->pluck('total_amount', 'account_id')
            ->toArray();

        // --- Logic untuk memeriksa dan memicu update saldo yang hilang ---
        $missingDatesToUpdate = [];
        foreach ($allAccountIds as $accountId) {
            // Memeriksa keberadaan saldo menggunakan chart_of_account_id
            if (!isset($previousDayBalances[$accountId])) {
                // Jika saldo hari sebelumnya tidak ditemukan di account_balances
                $missingDatesToUpdate[$previousDate] = true; // Tambahkan tanggal ini ke daftar
            }
        }

        foreach (array_keys($missingDatesToUpdate) as $date) {
            $this->_updateBalancesDirectly($date);
            Log::info('missingDatesToUpdate updated successfully for date: ' . $date);
        }

        // Log::info('missingDatesToUpdate: ' . json_encode($missingDatesToUpdate));

        if (!empty($missingDatesToUpdate)) {
            $previousDayBalances = AccountBalance::whereIn('chart_of_account_id', $allAccountIds)
                ->where('balance_date', $previousDate)
                ->pluck('ending_balance', 'chart_of_account_id')
                ->toArray();
        }


        // --- Perhitungan Saldo per Akun ---
        foreach ($chartOfAccounts as $chartOfAccount) {
            // Mengambil saldo awal dari previousDayBalances atau fallback ke st_balance
            // Menggunakan chart_of_account_id untuk look-up di previousDayBalances
            $initBalance = $previousDayBalances[$chartOfAccount->id] ?? ($chartOfAccount->st_balance ?? 0.00);
            // $initBalance = 0;
            $normalBalance = $chartOfAccount->account->status ?? '';

            // Mengambil debit/credit hari ini dari pre-fetched arrays
            $debitToday = $dailyDebits[$chartOfAccount->id] ?? 0.00;
            $creditToday = $dailyCredits[$chartOfAccount->id] ?? 0.00;

            // Hitung saldo akhir
            // $chartOfAccount->balance = $initBalance;
            $chartOfAccount->balance = $initBalance + ($normalBalance === 'D' ? $debitToday - $creditToday : $creditToday - $debitToday);
        }

        $revenue = $chartOfAccounts->whereIn('account_id', \range(27, 30))->groupBy('account_id');
        $cost = $chartOfAccounts->whereIn('account_id', \range(31, 32))->groupBy('account_id');
        $expense = $chartOfAccounts->whereIn('account_id', \range(33, 45))->groupBy('account_id');
        $assets = $chartOfAccounts->whereIn('account_id', \range(1, 18))->groupBy('account_id');
        $currentAssets = $chartOfAccounts->whereIn('account_id', \range(1, 9))->groupBy('account_id');
        $inventory = $chartOfAccounts->whereIn('account_id', [6, 7])->groupBy('account_id');
        $liabilities = $chartOfAccounts->whereIn('account_id', \range(19, 25))->groupBy('account_id');
        $equity = $chartOfAccounts->where('account_id', 26)->groupBy('account_id');
        $cash = $chartOfAccounts->where('account_id', 1)->groupBy('account_id');
        $bank = $chartOfAccounts->where('account_id', 2)->groupBy('account_id');
        $receivable = $chartOfAccounts->whereIn('account_id', [4, 5])->groupBy('account_id');
        $payable = $chartOfAccounts->whereIn('account_id', \range(19, 25))->groupBy('account_id');

        return [
            'accountBalances' => $chartOfAccounts,
            'revenue' => $revenue,
            'cost' => $cost,
            'expense' => $expense,
            'assets' => $assets,
            'currentAssets' => $currentAssets,
            'inventory' => $inventory,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'cash' => $cash,
            'bank' => $bank,
            'receivable' => $receivable,
            'payable' => $payable
        ];
    }

    public function profitLossCount($start_date, $end_date)
    {
        $journalCount = $this->journalCount($start_date, $end_date);

        return $journalCount['revenue']->flatten()->sum('balance') - $journalCount['cost']->flatten()->sum('balance') - $journalCount['expense']->flatten()->sum('balance');
    }

    public static function _updateBalancesDirectly(string $dateToUpdate): void
    {
        // Parsing tanggal untuk memastikan format yang benar
        $targetDate = Carbon::parse($dateToUpdate)->endOfDay();
        Log::info('targetDate: ' . $targetDate);
        try {
            $chartOfAccounts = ChartOfAccount::all();

            foreach ($chartOfAccounts as $chartOfAccount) {
                // Mengambil saldo awal dari properti model chartOfAccount->st_balance
                // Ini adalah saldo kumulatif dari awal waktu hingga hari sebelumnya
                $initBalance = $chartOfAccount->st_balance ?? 0.00;

                // Menghitung total debit langsung dari database hingga targetDate
                $totalDebit = Journal::where('debt_code', $chartOfAccount->id)
                    ->where('date_issued', '<=', $targetDate)
                    ->sum('amount');

                // Menghitung total credit langsung dari database hingga targetDate
                $totalCredit = Journal::where('cred_code', $chartOfAccount->id)
                    ->where('date_issued', '<=', $targetDate)
                    ->sum('amount');

                // Mengambil normal balance dari relasi 'account'
                $normalBalance = $chartOfAccount->account->status ?? '';

                $endingBalance = 0;
                if ($normalBalance === 'D') { // Asumsi 'D' untuk Debit
                    $endingBalance = $initBalance + $totalDebit - $totalCredit;
                } else { // Asumsi 'C' untuk Credit
                    $endingBalance = $initBalance + $totalCredit - $totalDebit;
                }

                // Simpan atau perbarui saldo di tabel account_balances
                AccountBalance::updateOrCreate(
                    [
                        'chart_of_account_id' => $chartOfAccount->id,
                        'balance_date' => $targetDate->toDateString(),
                    ],
                    [
                        'ending_balance' => $endingBalance,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Error during direct balance update for date {$targetDate->toDateString()}: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
