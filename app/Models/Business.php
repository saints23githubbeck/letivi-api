<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
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
    public function businessProfile(){

        return $this->hasOne(BusinessProfile::class);
    }

    public function businessMembers(){


        return $this->belongsToMany(User::class,'business_members');
    }


    public function businessSponsors(){


        return $this->belongsToMany(User::class,'business_sponsors');
    }


    public function industry(){

        return $this->belongsTo(Industry::class);
    }

    public function businessFollowers(){

        return $this->hasMany(FollowingBusiness::class);
    }

    public function myImages()
    {
        return $this->hasMany(Post::class)->where('type', 'image');
    }


    public function myVideos()
    {
        return $this->hasMany(Post::class)->where('type', 'video');
    }

    public function businessWorkspaceSponsors()
    {
        return $this->hasMany(WorkspaceSponsor::class);
    }

    public function sponsors()
    {
        return $this->hasMany(WorkspaceCollaborator::class,'business_id','id');
    }

}
