<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BusinessMember extends Pivot

{
    use HasFactory;

    protected $guarded = [];


//    public function business(){
//
//        return $this->belongsTo(Business::class);
//    }
//    public function user(){
//
//        return $this->belongsTo(User::class);
//    }


}
