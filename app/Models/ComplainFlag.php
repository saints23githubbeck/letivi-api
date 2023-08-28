<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplainFlag extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function complain()
    {
        return $this->belongsTo(Complain::class);
    }
}
