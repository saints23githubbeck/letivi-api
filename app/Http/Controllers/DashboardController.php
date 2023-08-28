<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserAuth\SignupController;
use App\Models\Industry;
use App\Models\Profession;
use App\Models\ProfessionalInfo;
use App\Models\User;
use App\Models\UserFollowing;
use App\Models\UserProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function __construct()
    {
    }

    private function user_login()
    {
        $authorizedEmails = [
            "azubirepeter@gmail.com",
            "letiviapp@gmail.com",
            "charlesagbemashior@gmail.com",
            "thinkactivemedia@gmail.com",
            "martkatshop@gmail.com"
        ];

        $userEmail = strtolower(auth()->user()->email);

        if (!in_array($userEmail, $authorizedEmails)) {
            abort(407, 'You Are Not Authorized To Perform This Action');
        }
    }

    public function index(Request $request)
    {
        $this->user_login();

        if (request()->user('sanctum')) {
            $user = User::with([
                'profile',
                'profession',
                'professionalInfo',
                'industry:id,name',
            ])
                ->withCount('myfollowers', 'amfollowing', 'posts', 'myImages', 'myVideos')
                ->where(function ($query) use ($request) {
                    if ($request->name) {
                        $query->where('first_name', 'LIKE', "%{$request->name}%")
                            ->orWhere('last_name', 'LIKE', "%{$request->name}%")
                            ->orWhere('email', 'LIKE', "%{$request->name}%");
                    }

                    if ($request->private) {
                        if (strtolower($request->private) === "true") {
                            $query->wherePrivate(true);
                        } elseif (strtolower($request->private) === "false") {
                            $query->wherePrivate(false);
                        }
                    }
                })->where('email','!=','martkatshop@gmail.com"')
                ->paginate(15);

            $data['users'] = $user;
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        }
    }

    public function create(Request $request)
    {
        $this->user_login();

        $signup = new SignupController;
        return $signup->create($request, true);
    }

    public function destroyUser($user)
    {
        $this->user_login();

        return DB::transaction(function () use ($user) {
            // Revoke the token that was used to authenticate the current request...
            $user = User::find($user);
            if ($user) {
                return $user->delete() ? $this->statusCode(200, 'Account deleted successfully.') : $this->statusCode(422, "Error occured whiles processing your request");
            } else {
                return $this->statusCode(404, 'User Not found');
            }
        });
    }


    public function updateProfile(Request $request, User $user)
    {
        $this->user_login();
        $profile = new ProfileController;
        return $profile->updateProfile($request, $user->id);
    }

    public function profileInvite(Request $request)
    {
        $this->user_login();
        $invite = new InviteController;
        return $invite->profileInvite($request);
    }

    public function activate(Request $request, User $user)
    {
        $status = 0;
        $this->user_login();
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'status' => ['required'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            if (strtolower($request->status) === "true") {
                $status = 1;
            } elseif (strtolower($request->status) === "false") {
                $status = 0;
            } else {
                return $this->statusCode(422, 'Invalid value. Only true / false accepted');
            }

            // check if user is not sending invite to self
            // $chk = User::whereId($user->id)->exists();
            // if (!$chk) {
            //     return $this->statusCode(404, 'This User Does Not Exist.');
            // }
            $user->update([
                'status' => $status
            ]);

            $data = User::find($user->id);
            return $this->statusCode(200, "User " . ($status == 1 ? "Activated" : "Deactivated") . " successfully", ['data' => $data->status]);
        } catch (\Throwable $e) {
            // return $this->statusCode(422, $e->getMessage());
            return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }

    public function sendNonUserInvite(Request $request)
    {
        $invite = new InviteController;
        return $invite->inviteNonUSer($request);
    }


    public function profileInfo(User $user)
    {
        $data = [];
        if (User::whereId($user->id)->count() > 0) {
            try {
                $user = User::select('id', 'first_name', 'last_name', 'private', 'gender', 'date_of_birth', 'industry_id', 'other_industry')->with(
                    'profession:id,user_id,profession',
                    'profile:id,user_id,picture,country,phone_number,bio'
                )->whereId($user->id)->first();
                $data['totalFollowers'] = UserFollowing::whereFollowing_id($user->id)->count();
                $data['totalFollowing'] = UserFollowing::whereUser_id($user->id)->count();
                $data['totalPosts'] = $user->posts()->count();
                $data['basicInfo'] = $user;
                $data['industry'] = Industry::select('id', 'name')->whereId($user->industry_id)->first();
                $data['professionalInfo'] = [
                    'organization' => ProfessionalInfo::select('id', 'organization', 'role', 'country')->whereNotNull('organization')->whereNotNull('role')->whereNotNull('country')->whereUser_id($user->id)->get(),
                    'awards' => ProfessionalInfo::select('id', 'awards', 'country')->whereNotNull('awards')->whereNotNull('country')->whereUser_id($user->id)->get(),
                    'nomination' => ProfessionalInfo::select('id', 'nomination', 'country')->whereNotNull('nomination')->whereNotNull('country')->whereUser_id($user->id)->get(),
                    'qualification' => ProfessionalInfo::select('id', 'qualification', 'education')->whereNotNull('qualification')->whereNotNull('education')->whereUser_id($user->id)->get(),
                    'work_experience' => ProfessionalInfo::select('id', 'work_experience')->whereNotNull('work_experience')->whereUser_id($user->id)->first()
                ];
                $data['projects'] = [
                    'books' => UserProject::select('id', 'name')->whereUser_id($user->id)->whereType('books')->get(),
                    'articles' => UserProject::select('id', 'name')->whereUser_id($user->id)->whereType('articles')->get(),
                    'photography' => UserProject::select('id', 'name')->whereUser_id($user->id)->whereType('photography')->get(),
                    'films' => UserProject::select('id', 'name')->whereUser_id($user->id)->whereType('films')->get(),
                    'exhibition' => UserProject::select('id', 'name')->whereUser_id($user->id)->whereType('exhibition')->get(),
                    'others' => UserProject::select('id', 'name')->whereUser_id($user->id)->whereType('others')->get(),
                ];
                $data['additionalInfo'] = Profession::select('id', 'linkedin', 'facebook', 'twitter', 'instagram', 'youtube', 'website', 'tiktok')->whereUser_id($user->id)->first();
                //            return ['data' => $data];
                return $this->statusCode(200, 'Record available', ['data' => $data]);
            } catch (\Throwable $e) {
                return $this->statusCode(400, $e->getMessage());
            }
        } else {
            return $this->statusCode(400, 'This User Record Not  available', ['data' => $data]);
        }
    }
}
