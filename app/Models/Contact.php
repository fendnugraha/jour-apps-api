<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $guarded = ['id'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function finances()
    {
        return $this->hasMany(Finance::class);
    }
}
