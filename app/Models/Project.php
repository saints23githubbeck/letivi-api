<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function albums(){

        return $this->hasMany(Album::class);
    }

    public function user(){

        return $this->belongsTo(User::class);
    }

    public function posts(){

        return $this->hasMany(Post::class);
    }
    public function projectProfile(){

        return $this->hasOne(ProjectProfile::class);
    }
    public function projectMembers(){


        return $this->belongsToMany(User::class,'project_members');
    }

    public function projectSponsors(){


        return $this->belongsToMany(User::class,'project_sponsors');
    }

    public function industry(){

        return $this->belongsTo(Industry::class);
    }

    public function projectFollowers(){

        return $this->hasMany(FollowingProject::class);
    }

    public function myImages()
    {
        return $this->hasMany(Post::class)->where('type', 'image');
    }


    public function myVideos()
    {
        return $this->hasMany(Post::class)->where('type', 'video');
    }

    public function projectWorkspaceSponsors()
    {
        return $this->hasMany(WorkspaceSponsor::class);
    }


    public function sponsors()
    {
        return $this->hasMany(WorkspaceCollaborator::class,'project_id','id');
    }
}
