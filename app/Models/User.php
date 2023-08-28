<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function industry()
    {

        return $this->belongsTo(Industry::class);
    }

    public function profile()
    {

        return $this->hasOne(Profile::class);
    }

    public function profession()
    {

        return $this->hasOne(Profession::class);
    }

    public function professionalInfo()
    {

        return $this->hasMany(ProfessionalInfo::class);
    }

    public function albums()
    {

        return $this->hasMany(Album::class);
    }

    public function posts()
    {

        return $this->hasMany(Post::class);
    }

    public function businesses()
    {

        return $this->hasMany(Business::class);
    }

    public function businessMember()
    {

        return $this->belongsToMany(Business::class, 'business_members');
    }

    public function businessSponsor()
    {

        return $this->belongsToMany(Business::class, 'business_sponsors');
    }


    public function events()
    {

        return $this->hasMany(Event::class);
    }
    public function eventMember()
    {


        return $this->belongsToMany(Event::class, 'event_members');
    }

    public function eventSponsor()
    {


        return $this->belongsToMany(Event::class, 'event_sponsors');
    }

    public function projects()
    {

        return $this->hasMany(Project::class);
    }

    public function projectMember()
    {

        return $this->belongsToMany(Project::class, 'project_members');
    }

    public function projectSponsor()
    {

        return $this->belongsToMany(Project::class, 'project_sponsors');
    }

    public function myfollowers()
    {

        return $this->hasMany(UserFollowing::class, 'following_id', 'id');
    }
    public function amfollowing()
    {

        return $this->hasMany(UserFollowing::class, 'user_id', 'id');
    }

    public function followbusiness()
    {

        return $this->hasMany(FollowingBusiness::class);
    }

    public function followevent()
    {

        return $this->hasMany(FollowingEvent::class);
    }
    public function followproject()
    {

        return $this->hasMany(FollowingProject::class);
    }

    public function invites()
    {

        return $this->hasMany(Invite::class);
    }

    public function downloads()
    {

        return $this->hasMany(Download::class);
    }

    public function savePosts()
    {

        return $this->hasMany(SaveFromPost::class);
    }

    public function mydownloaders()
    {

        return $this->hasMany(Download::class, 'downloader_id', 'downloader_id');
    }

    public function blockedUsers()
    {
        return $this->hasMany(BlockUser::class);
    }

    public function blockedPosts()
    {
        return $this->hasMany(BlockPost::class);
    }

    public function mydownloads()
    {

        return $this->hasMany(Download::class);
    }

    public function comments()
    {

        return $this->hasMany(Comment::class);
    }

    public function replies()
    {

        return $this->hasMany(Reply::class);
    }
    public function likes()
    {

        return $this->hasMany(Like::class);
    }

    public function claps()
    {

        return $this->hasMany(Clap::class);
    }

    public function loves()
    {

        return $this->hasMany(Love::class);
    }

    public function impressions()
    {

        return $this->hasMany(Impression::class);
    }

    public function views()
    {

        return $this->hasMany(View::class);
    }

    public function saves()
    {

        return $this->hasMany(MySave::class);
    }


    public function loginCountry()
    {

        return $this->hasMany(LoginCountry::class);
    }

    public function userProjects()
    {
        return $this->hasMany(UserProject::class);
    }

    public function myImages()
    {
        return $this->hasMany(Post::class)->where('type', 'image');
    }


    public function myVideos()
    {
        return $this->hasMany(Post::class)->where('type', 'video');
    }

    public function myPrivatePosts()
    {
        return $this->hasMany(Post::class)->where('private', true);
    }

    public function verifyUser()
    {
        $this->otp = null;
        $this->email_verified_at = Carbon::now();
        $this->save();
    }

    public function WelcomeMailSent(){
        $this->welcome_sent = true;
        $this->save();
    }
    public function sendOTP($otp, $isCheckingMail = true)
    {
        $this->otp = $otp;
        $isCheckingMail == true ? $this->email_verified_at = null : '';
        $this->save();
    }

    public function resetCode()
    {
        $this->timestamps = false; //Dont update the 'updated_at' field yet
        $this->otp = null;
        $this->save();
    }

    public function togglePrivacyStatus()
    {
        switch ($this->private) {
            case 1:
                $this->private = 0;
                $this->save();
                break;
            case 0:
                $this->private = 1;
                $this->save();
                break;
        }
    }
}
