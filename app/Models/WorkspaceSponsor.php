<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceSponsor extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sponsors()
    {
        return $this->hasMany(WorkspaceCollaborator::class);
    }
}
