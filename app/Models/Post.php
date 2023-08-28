<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function category(){

        return $this->belongsTo(Category::class);
    }

    public function user(){

        return $this->belongsTo(User::class);
    }

    public function business(){

        return $this->belongsTo(Business::class);
    }
    public function event(){

        return $this->belongsTo(Event::class);
    }
    public function project(){

        return $this->belongsTo(Project::class);
    }

    public function medias(){

        return $this->hasMany(Media::class);
    }

    public function downloads(){

        return $this->hasMany(Download::class );
    }

    public function postCount(){

        return $this->hasOne(PostDownloadCount::class );
    }

    public function postShares(){

        return $this->hasOne(SharePostCount::class );
    }


    public function postSave(){

        return $this->hasMany(SaveFromPost::class );
    }


    public function comments(){

        return $this->hasMany(Comment::class);
    }

    public function likes(){

        return $this->hasMany(Like::class);
    }

    public function claps(){

        return $this->hasMany(Clap::class);
    }
    public function loves(){

        return $this->hasMany(Love::class);
    }

    public function impressions(){

        return $this->hasMany(Impression::class);
    }
    public function views(){

        return $this->hasMany(View::class);
    }

    public function album(){

        return $this->belongsTo(Album::class);
    }
    public  function blocks(){

        return $this->hasMany(BlockPost::class);
    }

    public  function blockUsers(){

        return $this->hasMany(BlockUser::class,'user_id','user_id');
    }



}
