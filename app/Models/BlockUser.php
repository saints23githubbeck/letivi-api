<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockUser extends Model
{
    use HasFactory;

    protected $guarded = [];

    public  function user(){

        return $this->belongsTo(User::class);
    }

    public  function blockUser(){

        return $this->belongsTo(Post::class,'user_id','user_id');
    }
}
