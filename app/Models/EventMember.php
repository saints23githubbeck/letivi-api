<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EventMember extends Pivot

{
    use HasFactory;
    protected $guarded = [];

//    public function event(){
//
//        return $this->belongsTo(Event::class);
//    }
//    public function user(){
//
//        return $this->belongsTo(User::class);
//    }

}
