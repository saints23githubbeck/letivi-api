<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function post(){

        return $this->belongsTo(Post::class);
    }

    public function user(){

        return $this->belongsTo(User::class);
    }

    public function myDownloaders(){
        return $this->belongsTo(User::class ,'downloader_id','id');
    }

    public function myDownloads(){
        return $this->belongsTo(User::class );
    }
}
