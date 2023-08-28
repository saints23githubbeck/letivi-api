<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalInfo extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_experience'
    ];
    function user(){
        return $this->belongsTo(User::class);
    }
}
