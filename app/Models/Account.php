<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public function ChartOfAccount()
    {
        $this->hasMany(ChartOfAccount::class);
    }
}
