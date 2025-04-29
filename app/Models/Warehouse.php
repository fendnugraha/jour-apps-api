<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = ['id'];

    public function ChartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function journal()
    {
        return $this->hasMany(Journal::class);
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }
}
