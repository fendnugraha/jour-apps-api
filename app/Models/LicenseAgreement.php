<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseAgreement extends Model
{
    protected $guarded = ['id'];

    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
