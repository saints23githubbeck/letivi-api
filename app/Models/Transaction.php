<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public static function checkPaymentStatus(){
        return self::whereEmail(auth()->user()->email)->latest();
    }

    public static function checkBioStatus(){
        return self::whereEmail(auth()->user()->email)->whereStatus('success')->whereIn('bio_generation_status', ['pending', 'failed'])->latest();
    }
}
