<?php

namespace App\Models;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $guarded = ['id'];

    public function debt()
    {
        return $this->hasMany(Journal::class, 'debt_code', 'acc_code');
    }

    public function cred()
    {
        return $this->hasMany(Journal::class, 'cred_code', 'acc_code');
    }

    public function acc_code($account_id)
    {
        $accounts = Account::find($account_id);

        $lastCode = DB::table('chart_of_accounts')
            ->select(DB::raw('MAX(RIGHT(acc_code,3)) AS lastCode'))
            ->where('account_id', $account_id)
            ->get();

        if ($lastCode[0]->lastCode != null) {
            $kd = $lastCode[0]->lastCode + 1;
        } else {
            $kd = "001";
        }

        return $accounts->code . '-' . \sprintf("%03s", $kd);
    }
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function updateInitEquityBalance()
    {
        $assets = $this->whereIn('account_id', \range(1, 18))->sum('st_balance');
        $liabilities = $this->whereIn('account_id', \range(19, 25))->sum('st_balance');
        $equity = $this->where('account_id', 26)->where('id', '!=', 15)->sum('st_balance');

        $this->where('id', 15)->update(['st_balance' => $assets - $liabilities - $equity]);

        return $assets - $liabilities - $equity;
    }
}
