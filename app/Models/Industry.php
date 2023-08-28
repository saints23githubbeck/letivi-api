<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(){

        return $this->hasOne(User::class);
    }
    public function business(){

        return $this->hasOne(Business::class);
    }
    public function event(){

        return $this->hasOne(Event::class);
    }
    public function project(){

        return $this->hasOne(Project::class);
    }
}
