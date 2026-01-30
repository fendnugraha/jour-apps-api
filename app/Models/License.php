<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function agreements()
    {
        return $this->hasMany(LicenseAgreement::class);
    }

    public function isActive()
    {
        return $this->is_active && now()->lte($this->expired_at);
    }
}
