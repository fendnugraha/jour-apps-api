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
        return $this->hasMany(Finance::class, 'invoice', 'invoice');
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

    public function journalCount($startDate, $endDate, $warehouse = "all")
    {
        $accountBalances = Journal::selectRaw("
        chart.id as coa_id,
        chart.acc_name as coa_name,
        chart.st_balance,
        acc.status,
        acc.id as acc_id,
        acc.name as account_name,
        SUM(CASE WHEN journals.debt_code = chart.id THEN journals.amount ELSE 0 END) as total_debit,
        SUM(CASE WHEN journals.cred_code = chart.id THEN journals.amount ELSE 0 END) as total_credit
    ")
            ->join('chart_of_accounts as chart', function ($join) {
                $join->on('journals.debt_code', '=', 'chart.id')
                    ->orOn('journals.cred_code', '=', 'chart.id');
            })
            ->join('accounts as acc', 'chart.account_id', '=', 'acc.id')
            ->whereBetween('journals.date_issued', [$startDate, $endDate])
            ->when($warehouse !== 'all', fn($q) => $q->where('chart.warehouse_id', $warehouse))
            ->orderBy('chart.acc_code', 'asc')
            ->groupBy('chart.id', 'chart.st_balance', 'acc.status', 'chart.acc_name')
            ->get();


        foreach ($accountBalances as $acc) {
            $acc->balance = $acc->status === 'D'
                ? $acc->st_balance + $acc->total_debit - $acc->total_credit
                : $acc->st_balance + $acc->total_credit - $acc->total_debit;
        }

        $revenue = $accountBalances->whereIn('acc_id', \range(27, 30))->groupBy('acc_id');
        $cost = $accountBalances->whereIn('acc_id', \range(31, 32))->groupBy('acc_id');
        $expense = $accountBalances->whereIn('acc_id', \range(33, 45))->groupBy('acc_id');
        $assets = $accountBalances->whereIn('acc_id', \range(1, 18))->groupBy('acc_id');
        $currentAssets = $accountBalances->whereIn('acc_id', \range(1, 9))->groupBy('acc_id');
        $inventory = $accountBalances->whereIn('acc_id', [6, 7])->groupBy('acc_id');
        $liabilities = $accountBalances->whereIn('acc_id', \range(19, 25))->groupBy('acc_id');
        $equity = $accountBalances->where('acc_id', 26)->groupBy('acc_id');
        $cash = $accountBalances->where('acc_id', 1)->groupBy('acc_id');
        $bank = $accountBalances->where('acc_id', 2)->groupBy('acc_id');
        $receivable = $accountBalances->whereIn('acc_id', [4, 5])->groupBy('acc_id');
        $payable = $accountBalances->whereIn('acc_id', \range(19, 25))->groupBy('acc_id');

        return [
            'accountBalances' => $accountBalances,
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
}
