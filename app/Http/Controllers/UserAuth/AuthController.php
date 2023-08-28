<?php

namespace App\Http\Controllers\UserAuth;

use App\Models\Business;
use App\Models\Event;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Mail\UserSendMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // Reset Password
    /**
     * @OA\Post(
     * path="/api/password/save",
     * operationId="Save new user Password",
     * tags={"Save new user Password"},
     * summary="Save new user Password",
     * description="Change Password here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"token","password", "confirm_password"},
     *               @OA\Property(property="token", type="string"),
     *               @OA\Property(property="password", type="password"),
     *               @OA\Property(property="confirm_password", type="password"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Password changed successfully. Proceed to login",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=300,
     *          description="Confirm Email OTP sent",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Passwords mismatch"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */
    public function resetPassword(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'password' => ['required', 'max:50', 'min:7'],
                    'confirm_password' => ['required', 'max:50', 'min:7'],
                    'token' => ['required']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            $chk = User::whereToken(trim(strtolower($request->token)))->count();
            if ($chk > 0) {
                if ($request->password != $request->confirm_password) {
                    return $this->statusCode(401, 'Passwords mismatch');
                }
                $user = User::whereToken(trim(strtolower($request->token)))->first();
                $user->update(['password' => Hash::make($request->password)]);
                return $this->statusCode(200, 'Password changed successfully. Proceed to login');
            } else {
                return $this->statusCode(404, 'User Not found');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(404, $e->getMessage());
        }
    }

    // Check if email exist before sending resetPasswordOTP

    /**
     * @OA\Post(
     * path="/api/verify/email",
     * operationId="Check if email exist",
     * tags={"Check if email exist before sending resetPasswordOTP"},
     * summary="Check if email exist before sending resetPasswordOTP",
     * description="Check email here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email"},
     *               @OA\Property(property="email", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Password changed successfully. Proceed to login",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=300,
     *          description="Password reset link sent successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */
    public function checkEmail(Request $request)
    {
        $otpController = new OtpController();
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => ['required', 'email', 'indisposable']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }
        } catch (\Throwable $e) {
            return $this->statusCode(404, $e->getMessage());
        }
        // check if email exists
        $chk = User::whereEmail(trim(strtolower($request->email)))->count();

        if ($chk > 0) {
            $user = User::whereEmail(trim(strtolower($request->email)))->first();

            return $otpController->sendResetPasswordOTP($user);
        } else {
            return $this->statusCode(404, 'Email does not exist');
        }
    }

    // delete User's Account for life

    /**
     * @OA\Delete(
     * path="/api/users/remove",
     * operationId="Delete User's Account",
     * tags={"Delete User's Account"},
     * summary="Delete User's Account",
     * description="Delete Account here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Account deleted successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */
    public function deleteAccount()
    {
        try {
            if (request()->user('sanctum')) {
                $user = '';
                return DB::transaction(function () use ($user) {
                    $user = Auth::user();
                    // Revoke the token that was used to authenticate the current request...
                    $chk = User::whereEmail(trim(strtolower($user->email)))->count();
                    if ($chk > 0) {
                        $user->currentAccessToken()->delete();
                        $user = User::whereEmail(trim(strtolower($user->email)))->first();
                        return $user->delete() ? $this->statusCode(200, 'Account deleted successfully.') : $this->statusCode(500, "Error occured whiles processing your request");
                    } else {
                        return $this->statusCode(404, 'User Not found');
                    }
                });
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Post(
     * path="/api/users/logout",
     * operationId="User Logout",
     * tags={"User Logout"},
     * summary="User Logout",
     * description="User  Logout  here",
     *      @OA\Response(
     *          response=201,
     *          description="Logout   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */


    // This logout single user

    public function logout()
    {
        if (request()->user('sanctum')) {
            $user = Auth::user();
            // Revoke the token that was used to authenticate the current request...
            $user->currentAccessToken()->delete();
            // response to this message if successful logout
            return $this->statusCode(200, 'Logout Successfully');
        } else {
            return $this->statusCode(407, 'Please Login First');
        }
    }


    /**
     * @OA\GET(
     * path="/api/users",
     * operationId="All users",
     * tags={"All users"},
     * summary="All users",
     * description="All users here",
     *      @OA\Response(
     *          response=201,
     *          description="Logout   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function indexOnLogin()
    {


        //        return request()->user('sanctum')->id;
        if (request()->user('sanctum')) {
            $data['users'] = User::with(
                'profile',
                'profession',
                'professionalInfo',
                'industry:id,name',
                'myfollowers.myFollower:id,first_name,last_name',
                'myfollowers.myFollower.profile:id,user_id,picture',
                'amfollowing.amFollowing:id,first_name,last_name',
                'amfollowing.amFollowing.profile:id,user_id,picture'
            )->whereHas('profile', function ($query) {
                $query->where('user_id', '!=', null);
            })->withCount('myfollowers', 'amfollowing', 'posts', 'myImages', 'myVideos')
                ->wherePrivate(false)
                ->where('id', '!=', request()->user('sanctum')->id)
                ->paginate(30);

            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        }
    }
    public function indexUsers()
    {
        $data['users'] = User::with(
            'profile',
            'profession',
            'professionalInfo',
            'industry:id,name',
            'myfollowers.myFollower:id,first_name,last_name',
            'myfollowers.myFollower.profile:id,user_id,picture',
            'amfollowing.amFollowing:id,first_name,last_name',
            'amfollowing.amFollowing.profile:id,user_id,picture'
        )->whereHas('profile', function ($query) {
            $query->where('picture', '!=', null);
        })->withCount('myfollowers', 'amfollowing', 'posts', 'myImages', 'myVideos')->whereIn("id", [20, 2, 11, 19])
            ->paginate(4);
        //                ->wherePrivate(false)
        //                ->where('id','!=',request()->user('sanctum')->id)
        //                ->paginate(30);

        return $this->statusCode(200, 'Request  successful', ['data' => $data]);
    }


    /**
     * @OA\GET(
     * path="/api/all/workspaces",
     * operationId="All  pages",
     * tags={"All  pages"},
     * summary="All  pages",
     * description="All  pages here",
     *      @OA\Response(
     *          response=200,
     *          description="Reqeust   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function allWorkspace()
    {
        if (request()->user('sanctum')) {
            $data['business'] = Business::with(
                'businessProfile',
                'businessMembers:id,first_name,last_name',
                'businessMembers.profile',
                'businessMembers.profession',
                //               'businessSponsors:id,first_name,last_name',
                //               'businessSponsors.profile:id,user_id,picture',
                'businessFollowers.user:id,first_name,last_name',
                'businessFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->wherePrivate(false)
                ->where('user_id', '!=', request()->user('sanctum')->id)
                ->orderByDesc('created_at')->paginate(30);
            $data['projects'] = Project::with(
                'projectProfile',
                'projectMembers:id,first_name,last_name',
                'projectMembers.profile',
                'projectMembers.profession',
                //               'projectSponsors:id,first_name,last_name',
                //               'projectSponsors.profile:id,user_id,picture',
                'projectFollowers.user:id,first_name,last_name',
                'projectFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->wherePrivate(false)
                ->where('user_id', '!=', request()->user('sanctum')->id)
                ->orderByDesc('created_at')->paginate(30);
            $data['events'] = Event::with(
                'eventProfile',
                'eventMembers:id,first_name,last_name',
                'eventMembers.profile',
                'eventMembers.profession',
                //               'eventSponsors:id,first_name,last_name',
                //               'eventSponsors.profile:id,user_id,picture',
                'eventFollowers.user:id,first_name,last_name',
                'eventFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )
                ->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->where('user_id', '!=', request()->user('sanctum')->id)
                ->wherePrivate(false)
                ->orderByDesc('created_at')->paginate(30);
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {

            $data['business'] = Business::with(
                'businessProfile',
                'businessMembers:id,first_name,last_name',
                'businessMembers.profile',
                'businessMembers.profession',
                //               'businessSponsors:id,first_name,last_name',
                //               'businessSponsors.profile:id,user_id,picture',
                'businessFollowers.user:id,first_name,last_name',
                'businessFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->wherePrivate(false)
                ->orderByDesc('created_at')->paginate(30);
            $data['projects'] = Project::with(
                'projectProfile',
                'projectMembers:id,first_name,last_name',
                'projectMembers.profile',
                'projectMembers.profession',
                //               'projectSponsors:id,first_name,last_name',
                //               'projectSponsors.profile:id,user_id,picture',
                'projectFollowers.user:id,first_name,last_name',
                'projectFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )
                ->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->wherePrivate(false)
                ->orderByDesc('created_at')->paginate(30);
            $data['events'] = Event::with(
                'eventProfile',
                'eventMembers:id,first_name,last_name',
                'eventMembers.profile',
                'eventMembers.profession',
                //               'eventSponsors:id,first_name,last_name',
                //               'eventSponsors.profile:id,user_id,picture',
                'eventFollowers.user:id,first_name,last_name',
                'eventFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->wherePrivate(false)
                ->orderByDesc('created_at')->paginate(30);

            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/{id}",
     * operationId="Single user workspaces",
     * tags={"Single user workspaces"},
     * summary="Single user workspaces",
     * description="Single user workspaces here",
     *      @OA\Response(
     *          response=200,
     *          description="Reqeust   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function userWorkspace(User $user)
    {

        $chk = User::whereId($user->id)->count();
        if ($chk > 0) {
            $data['business'] = Business::with(
                'businessProfile',
                'businessMembers:id,first_name,last_name',
                'businessMembers.profile',
                'businessMembers.profession',
                //               'businessSponsors:id,first_name,last_name',
                //               'businessSponsors.profile:id,user_id,picture',
                'businessFollowers.user:id,first_name,last_name',
                'businessFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->whereUser_id($user->id)
                ->wherePrivate(false)
                ->orderByDesc('created_at')->paginate(30);
            $data['projects'] = Project::with(
                'projectProfile',
                'projectMembers:id,first_name,last_name',
                'projectMembers.profile',
                'projectMembers.profession',
                //               'projectSponsors:id,first_name,last_name',
                //               'projectSponsors.profile:id,user_id,picture',
                'projectFollowers.user:id,first_name,last_name',
                'projectFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->whereUser_id($user->id)->wherePrivate(false)->orderByDesc('created_at')->paginate(30);
            $data['events'] = Event::with(
                'eventProfile',
                'eventMembers:id,first_name,last_name',
                'eventMembers.profile',
                'eventMembers.profession',
                'eventFollowers.user:id,first_name,last_name',
                'eventFollowers.user.profile:id,user_id,picture',
                'industry',
                'albums'
            )->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->whereUser_id($user->id)->wherePrivate(false)->orderByDesc('created_at')->paginate(30);
            if ($data) {
                return $this->statusCode(200, 'Request  successful', ['data' => $data]);
            } else {
                return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
            }
        }
    }


    /**
     * @OA\GET(
     * path="/api/myworkspaces",
     * operationId="All user pages",
     * tags={"All users pages"},
     * summary="All users pages",
     * description="All users pages here",
     *      @OA\Response(
     *          response=200,
     *          description="Reqeust   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function myWorkspace()
    {


        $data['business'] = Business::with(
            'businessProfile',
            'businessMembers:id,first_name,last_name',
            'businessMembers.profile',
            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            'businessFollowers.user:id,first_name,last_name',
            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')->whereUser_id(auth()->id())->orderByDesc('created_at')->paginate(30);

        $data['my_member_business_workspaces'] = Business::with(
            'businessProfile',
            //            'businessMembers:id,first_name,last_name',
            //            'businessMembers.profile',
            //            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            //            'businessFollowers.user:id,first_name,last_name',
            //            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('businessMembers', function ($query) {
            $query->where('user_id', '=', auth()->id());
        })->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();


        $data['projects'] = Project::with(
            'projectProfile',
            'projectMembers:id,first_name,last_name',
            'projectMembers.profile',
            'projectMembers.profession',
            //            'projectSponsors:id,first_name,last_name',
            //            'projectSponsors.profile:id,user_id,picture',
            'projectFollowers.user:id,first_name,last_name',
            'projectFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')->whereUser_id(auth()->id())->orderByDesc('created_at')->paginate(30);

        $data['my_member_project_workspaces'] = Project::with(
            'projectProfile',
            //            'businessMembers:id,first_name,last_name',
            //            'businessMembers.profile',
            //            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            //            'businessFollowers.user:id,first_name,last_name',
            //            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('projectMembers', function ($query) {
            $query->where('user_id', '=', auth()->id());
        })->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();


        $data['events'] = Event::with(
            'eventProfile',
            'eventMembers:id,first_name,last_name',
            'eventSponsors.profile',
            'eventSponsors.profession',
            //            'eventSponsors:id,first_name,last_name',
            //            'eventMembers.profile:id,user_id,picture',
            'eventFollowers.user:id,first_name,last_name',
            'eventFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')->whereUser_id(auth()->id())->orderByDesc('created_at')->paginate(30);


        $data['my_member_event_workspaces'] = Event::with(
            'eventProfile',
            //            'businessMembers:id,first_name,last_name',
            //            'businessMembers.profile',
            //            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            //            'businessFollowers.user:id,first_name,last_name',
            //            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('eventMembers', function ($query) {
            $query->where('user_id', '=', auth()->id());
        })->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/myworkspaces/business",
     * operationId="All users business pages",
     * tags={"All users business pages"},
     * summary="All users business pages",
     * description="All users business pages here",
     *      @OA\Response(
     *          response=200,
     *          description="Reqeust   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function myBusinessWorkspace()
    {


        $data['business'] = Business::with(
            'businessProfile',
            'businessMembers:id,first_name,last_name',
            'businessMembers.profile',
            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            'businessFollowers.user:id,first_name,last_name',
            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')->whereUser_id(auth()->id())->orderByDesc('created_at')->get();
        $data['my_member_business_workspaces'] = Business::with(
            'businessProfile',
            //            'businessMembers:id,first_name,last_name',
            //            'businessMembers.profile',
            //            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            //            'businessFollowers.user:id,first_name,last_name',
            //            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('businessMembers', function ($query) {
            $query->where('user_id', '=', auth()->id());
        })->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/myworkspaces/projects",
     * operationId="All users projects pages",
     * tags={"All users projects pages"},
     * summary="All users projects pages",
     * description="All users projects pages here",
     *      @OA\Response(
     *          response=200,
     *          description="Reqeust   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function myProjectWorkspace()
    {


        $data['projects'] = Project::with(
            'projectProfile',
            'projectMembers:id,first_name,last_name',
            'projectMembers.profile',
            'projectMembers.profession',
            //            'projectSponsors:id,first_name,last_name',
            //            'projectSponsors.profile:id,user_id,picture',
            'projectFollowers.user:id,first_name,last_name',
            'projectFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')
            ->whereUser_id(auth()->id())->orderByDesc('created_at')->get();

        $data['my_member_project_workspaces'] = Project::with(
            'projectProfile',
            //            'businessMembers:id,first_name,last_name',
            //            'businessMembers.profile',
            //            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            //            'businessFollowers.user:id,first_name,last_name',
            //            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('projectMembers', function ($query) {
            $query->where('user_id', '=', auth()->id());
        })->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/myworkspaces/events",
     * operationId="All users event pages",
     * tags={"All users event pages"},
     * summary="All users event pages",
     * description="All users event pages here",
     *      @OA\Response(
     *          response=200,
     *          description="Reqeust   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function myEventWorkspace()
    {

        $data['events'] = Event::with(
            'eventProfile',
            'eventMembers:id,first_name,last_name',
            'eventMembers.profile',
            'eventMembers.profession',
            //            'eventSponsors:id,first_name,last_name',
            //            'eventSponsors.profile:id,user_id,picture',
            'eventFollowers.user:id,first_name,last_name',
            'eventFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')
            ->whereUser_id(auth()->id())->orderByDesc('created_at')->paginate();

        $data['my_member_event_workspaces'] = Event::with(
            'eventProfile',
            //            'businessMembers:id,first_name,last_name',
            //            'businessMembers.profile',
            //            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            //            'businessFollowers.user:id,first_name,last_name',
            //            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('eventMembers', function ($query) {
            $query->where('user_id', '=', auth()->id());
        })->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();

        if ($data['events']->count() > 0) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/business/{id}",
     * operationId="All posts in business page",
     * tags={"All posts in business page"},
     * summary="All posts in business page",
     * description="All posts in business page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the business"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myBusinessWorkspacePost(Business $business)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'business',
            'business.businessProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereBusiness_id($business->id)
            ->orderByDesc('created_at')->paginate(30);

        //        return $data['posts'];

        $data['total_posts_count'] = Post::whereBusiness_id($business->id)->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/project/{id}",
     * operationId="All posts in project page",
     * tags={"All posts in project page"},
     * summary="All posts in project page",
     * description="All posts in project page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the project"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myProjectWorkspacePost(Project $project)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'project',
            'project.projectProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereProject_id($project->id)->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereProject_id($project->id)->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/event/{id}",
     * operationId="All posts in event page",
     * tags={"All posts in event page"},
     * summary="All posts in event page",
     * description="All posts in event page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the event"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myEventWorkspacePost(Event $event)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'event',
            'event.eventProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereEvent_id($event->id)->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereEvent_id($event->id)->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/business/images{id}",
     * operationId="All images posts in business page",
     * tags={"All images posts in business page"},
     * summary="All images posts in business page",
     * description="All images posts in business page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the business"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myBusinessWorkspaceImage(Business $business)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'business',
            'business.businessProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereBusiness_id($business->id)->whereType('image')->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereBusiness_id($business->id)->whereType('image')->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/project/images{id}",
     * operationId="All Images posts in project page",
     * tags={"All Images posts in project page"},
     * summary="All Images posts in project page",
     * description="All Images posts in project page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the project"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myProjectWorkspaceImage(Project $project)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'project',
            'project.projectProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereProject_id($project->id)->whereType('image')->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereProject_id($project->id)->whereType('image')->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/event/images/{id}",
     * operationId="All Images posts in event page",
     * tags={"All Images posts in event page"},
     * summary="All Images posts in event page",
     * description="All Images posts in event page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the event"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myEventWorkspaceImage(Event $event)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'event',
            'event.eventProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereEvent_id($event->id)->whereType('image')->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereEvent_id($event->id)->whereType('image')->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/business/videos/{id}",
     * operationId="All video posts in business page",
     * tags={"All video posts in business page"},
     * summary="All video posts in business page",
     * description="All video posts in business page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the business"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myBusinessWorkspaceVideo(Business $business)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'business',
            'business.businessProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereBusiness_id($business->id)->whereType('video')->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereBusiness_id($business->id)->whereType('video')->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/project/videos{id}",
     * operationId="All video  posts in project page",
     * tags={"All video posts in project page"},
     * summary="All video posts in project page",
     * description="All video posts in project page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the project"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myProjectWorkspaceVideo(Project $project)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'project',
            'project.projectProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereProject_id($project->id)->whereType('video')->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereProject_id($project->id)->whereType('video')->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }


    /**
     * @OA\GET(
     * path="/api/users/workspaces/event/videos/{id}",
     * operationId="All video posts in event page",
     * tags={"All video posts in event page"},
     * summary="All video posts in event page",
     * description="All video posts in event page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the event"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Request successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */

    public function myEventWorkspaceVideo(Event $event)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'event',
            'event.eventProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereEvent_id($event->id)->whereType('video')->orderByDesc('created_at')->paginate(30);

        $data['total_posts_count'] = Post::whereEvent_id($event->id)->whereType('video')->count();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }

    // /**
    //  * @OA\Post(
    //  * path="/api/delete/user",
    //  * operationId="THIS IS A TEST API. DO NOT USE IN YOUR CODE",
    //  * tags={"THIS IS A TEST API. DO NOT USE IN YOUR CODE -- DELETE ACCOUNT"},
    //  * summary="THIS IS A TEST API. DO NOT USE IN YOUR CODE ",
    //  * description="Delete Account here",
    //  *     @OA\RequestBody(
    //  *         @OA\JsonContent(),
    //  *         @OA\MediaType(
    //  *            mediaType="multipart/form-data",
    //  *            @OA\Schema(
    //  *               type="object",
    //  *               required={"email"},
    //  *               @OA\Property(property="email", type="string"),
    //  *            ),
    //  *        ),
    //  *    ),
    //  *      @OA\Response(
    //  *          response=200,
    //  *          description="Account deleted successfuly",
    //  *          @OA\JsonContent()
    //  *       ),
    //  *      @OA\Response(
    //  *          response=422,
    //  *          description="Unprocessable Entity",
    //  *          @OA\JsonContent()
    //  *       ),
    //  *      @OA\Response(response=400, description="Bad request"),
    //  *      @OA\Response(response=403, description="Error in input fields"),
    //  *      @OA\Response(response=404, description="Resource Not Found"),
    //  * )
    //  */
    public function destroyUser(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => ['required', 'email', 'indisposable']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }
            return DB::transaction(function () use ($request) {
                // Revoke the token that was used to authenticate the current request...
                $chk = User::whereEmail(trim(strtolower($request->email)))->count();
                if ($chk > 0) {
                    $user = User::whereEmail($request->email)->first();
                    return $user->delete() ? $this->statusCode(200, 'Account deleted successfully.') : $this->statusCode(422, "Error occured whiles processing your request");
                } else {
                    return $this->statusCode(404, 'User Not found');
                }
            });
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     * path="/api/users/find/{email}",
     * summary="Find user based on email",
     * description="Find user based on email",
     * tags={"Find user based on email"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="User found",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     * )
     */
    public function getUserByEmail($email)
    {
        try {
            $validate = Validator::make(
                ['email' => $email],
                [
                    'email' => ['required', 'email', 'indisposable']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }
            $email = trim(strtolower($email));

            $chk = User::whereEmail($email)->count();
            return $chk > 0 ? $this->statusCode(200, 'User found', ['user' => User::with('profile')->whereEmail($email)->first()]) : $this->statusCode(404, 'No user found');
        } catch (\Throwable $e) {
            return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Post(
     * path="/api/users/sendmail",
     * operationId="Send mail to user on the system",
     * tags={"Send mail to user on the system"},
     * summary="Send mail to user on the system ",
     * description="Send mail here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email", "message_body"},
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="message_body", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Mail sent successfuly",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function sendMail(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => ['required', 'email', 'indisposable'],
                    'message_body' => ['required']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            $email = $request->email;
            // check if user is sending mail to self
            if ($email == auth()->user()->email) {
                return $this->statusCode(422, "You cannot send mail to yourself");
            }

            $sender = auth()->user();
            $reciever = $email;
            Mail::to($email)->Send(new UserSendMail($sender, $reciever, $request->message_body));
            return $this->statusCode(200, "Mail sent successfully");
        } catch (\Throwable $e) {
            // return $this->statusCode(422, $e->getMessage());
            return $this->statusCode(422, "Error occured processing your request");
        }
    }

    public function PDF_MAKER($description, $customName = null)
    {
        $pdf = app('dompdf.wrapper');
        $context = stream_context_create(['ssl' => [
            'veryfy_peer' => false,
            'verify_peer_name' => false,
            'allow_self_sign' => true
        ]]);
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHTML5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);
        $pdf->getDomPDF()->setHttpContext($context);
        $pdf->loadView('pdf.makePDF', ['description' => $description]);
        $pdf->setPaper('A4', 'Portrait');
        return $pdf->download($customName ?? 'desc.pdf');
    }

    /**
     * @OA\Get(
     * path="/api/workspaces/download/business/{business_id}",
     * summary="Download business description as PDF",
     * description="Download business description as PDF",
     * tags={"Download business description as PDF"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="SUCCESSFUL",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     * )
     */
    public function downloadBusinessWorkSpaceDescription($business_id)
    {
        try {
            $business = Business::find($business_id);
            if ($business) {
                $user = $business->user()->first();
                $fn = strtoupper($business->name . "-" . $user->last_name . " " . $user->first_name);
                $fn .= ".pdf";
                return $this->PDF_MAKER($business->description, $fn);
            } else {
                return $this->statusCode(404, "No business found");
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(422, $e->getMessage());
            return $this->statusCode(422, "Error occured processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/workspaces/download/event/{event_id}",
     * summary="Download event description as PDF",
     * description="Download event description as PDF",
     * tags={"Download event description as PDF"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="SUCCESSFUL",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     * )
     */
    public function downloadEventWorkSpaceDescription($event_id)
    {
        try {
            $event = Event::find($event_id);
            if ($event) {
                $user = $event->user()->first();
                $fn = strtoupper($event->name . "-" . $user->last_name . " " . $user->first_name);
                $fn .= ".pdf";
                return $this->PDF_MAKER($event->description, $fn);
            } else {
                return $this->statusCode(404, "No event found");
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(422, $e->getMessage());
            return $this->statusCode(422, "Error occured processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/workspaces/download/project/{project_id}",
     * summary="Download project description as PDF",
     * description="Download project description as PDF",
     * tags={"Download project description as PDF"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="SUCCESSFUL",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     * )
     */
    public function downloadProjectWorkSpaceDescription($project_id)
    {
        try {
            $project = Project::find($project_id);
            if ($project) {
                $user = $project->user()->first();
                $fn = strtoupper($project->name . "-" . $user->last_name . " " . $user->first_name);
                $fn .= ".pdf";
                return $this->PDF_MAKER($project->description, $fn);
            } else {
                return $this->statusCode(404, "No project found");
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(422, $e->getMessage());
            return $this->statusCode(422, "Error occured processing your request");
        }
    }

    /**
     * @OA\Patch(
     * path="/api/user/password",
     * operationId="Save new user Password from Settings",
     * tags={"Save new user Password from Settings"},
     * summary="Save new user Password from Settings",
     * description="Change Password from Settings here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"new_password", "confirm_password"},
     *               @OA\Property(property="new_password", type="password"),
     *               @OA\Property(property="confirm_password", type="password"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Password changed successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Passwords mismatch"),
     *      @OA\Response(response=210, description="Error in input fields"),
     * )
     */

    public function changePasswordFromSetting(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'new_password' => ['required', 'max:50', 'min:7'],
                    'confirm_password' => ['required', 'max:50', 'min:7']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(210, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            if ($request->new_password != $request->confirm_password) {
                return $this->statusCode(401, 'Passwords mismatch');
            }

            $user = User::find(auth()->id());
            $user->password = Hash::make($request->password);
            return $user->save() ? $this->statusCode(200, "Password changed successfully") : $this->statusCode(422, "System issue. Try again later");
        } catch (\Throwable $e) {
            // return $this->statusCode(422, $e->getMessage());
            return $this->statusCode(422, "Error occured processing your request");
        }
    }
}
