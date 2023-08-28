<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceCollaborator extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function workspace()
    {
        return $this->belongsTo(WorkspaceSponsor::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class ,'business_id','id');
    }
    public function event()
    {
        return $this->belongsTo(Event::class,'event_id','id');
    }
    public function project()
    {
        return $this->belongsTo(Project::class,'project_id','id');
    }


}
