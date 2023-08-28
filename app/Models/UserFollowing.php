<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollowing extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function myFollower(){
        return $this->belongsTo(User::class ,'user_id','id');
    }

    public function amFollowing(){
        return $this->belongsTo(User::class ,'following_id','id');
    }
}
