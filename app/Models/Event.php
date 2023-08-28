<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
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
    public function eventProfile(){

        return $this->hasOne(EventProfile::class);
    }
    public function eventMembers(){


        return $this->belongsToMany(User::class,'event_members');

    }

    public function eventSponsors(){


        return $this->belongsToMany(User::class,'event_sponsors');

    }

    public function industry(){

        return $this->belongsTo(Industry::class);
    }

    public function eventFollowers(){

        return $this->hasMany(FollowingEvent::class);
    }

    public function myImages()
    {
        return $this->hasMany(Post::class)->where('type', 'image');
    }


    public function myVideos()
    {
        return $this->hasMany(Post::class)->where('type', 'video');
    }

    public function eventWorkspaceSponsors()
    {
        return $this->hasMany(WorkspaceSponsor::class);
    }


    public function sponsors()
    {
        return $this->hasMany(WorkspaceCollaborator::class,'event_id','id');
    }
}
