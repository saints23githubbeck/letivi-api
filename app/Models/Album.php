<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    protected $guarded = [];

    public  function user(){

        return $this->belongsTo(User::class);
    }

    public  function business(){

        return $this->belongsTo(Business::class);
    }

    public  function event(){

        return $this->belongsTo(Event::class);
    }

    public  function project(){

        return $this->belongsTo(Project::class);
    }

    public  function posts(){

        return $this->hasMany(Post::class);
    }
}
