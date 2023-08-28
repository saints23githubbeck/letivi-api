<?php

namespace App\Http\Controllers;

use App\Mail\NonUserInviteMail;
use App\Mail\PageInviteMail;
use App\Mail\ProfileInviteMail;
use App\Models\Album;
use App\Models\Business;
use App\Models\Event;
use App\Models\Invite;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\returnSelf;

class InviteController extends Controller
{
    /**
     * @OA\POST(
     * path="/api/page/invite",
     * operationId="Send Page Invite",
     * tags={"Send Page Invite"},
     * summary="Send Page Invite",
     * description="Send Page Invite here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email", "page_source", "page_id"},
     *               @OA\Property(property="email", type="email"),
     *               @OA\Property(property="page_source", type="string"),
     *               @OA\Property(property="page_id", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Page Invite link sent successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function sendPageInvite(Request $request)
    {
        // check if user is logged in
        if (request()->user('sanctum')) {
            // run validations
            try {
                // VALIDATE INPUT FIELDS
                $validate = Validator::make(
                    $request->all(),
                    [
                        'email' => ['required', 'email', 'indisposable'],
                        'page_source' => ['required', 'string'],
                        'page_id' => ['required', 'integer']
                    ]
                );

                if ($validate->fails()) {
                    return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
                }

                // check if source falls within acceptable names
                $acceptTypes = ['album', 'business', 'event', 'project'];
                $hasMatch = false;
                foreach ($acceptTypes as $types) {
                    trim(strtolower($request->page_source)) == $types ? $hasMatch = true : '';
                }

                if ($hasMatch == false) {
                    return $this->statusCode(404, 'Acceptable page types must fall within this filter [' . implode(', ', $acceptTypes) . ']');
                }

                // check if page id exits within the appropriate filter and belongs to current login user
                switch (trim(strtolower($request->page_source))) {
                    case 'album':
                        $chkPG = Album::whereId($request->page_id)->whereUser_id(auth()->id())->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                    case 'business':
                        $chkPG = Business::whereId($request->page_id)->whereUser_id(auth()->id())->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                    case 'event':
                        $chkPG = Event::whereId($request->page_id)->whereUser_id(auth()->id())->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                    case 'project':
                        $chkPG = Project::whereId($request->page_id)->whereUser_id(auth()->id())->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                }

                // check if email belongs to user on the system
                $chkMail = User::whereEmail(trim(strtolower($request->email)))->count();
                if ($chkMail == 0) {
                    return $this->statusCode(404, "No user found with this mail");
                }

                $invite = Invite::create([
                    'email' => trim(strtolower($request->email)),
                    'user_id' => auth()->id()
                ]);

                $owner = Profile::whereUser_id(auth()->id())->pluck('token');
                // return $owner[0];
                Mail::to($request->email)->Send(new PageInviteMail($owner[0], $request->email, $request->page_source, $request->page_id));

                return $this->statusCode(200, "Page invite sent successfully", ['invite' => $invite]);
            } catch (\Throwable $e) {
                return $this->statusCode(500, $e->getMessage());
                return $this->statusCode(500, "Error occured while processing your request");
            }
        } else {
            return $this->statusCode(407, 'Please Login First');
        }
    }

    /**
     * @OA\POST(
     * path="/api/invite",
     * operationId="Send Profile Invite",
     * tags={"Send Profile Invite"},
     * summary="Send Profile Invite",
     * description="Send Profile Invite here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email"},
     *               @OA\Property(property="email", type="email"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile Invite link sent successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function profileInvite(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => ['required', 'email', 'indisposable'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            // check if user is logged in
            if (request()->user('sanctum')) {
                $email = trim(strtolower($request->email));
                $user = User::find(auth()->id());
                // check if user is not sending invite to self
                $chk = User::whereEmail($email)->whereId(auth()->id())->count();
                if ($chk > 0) {
                    return $this->statusCode(400, 'You cannot send invite to self.');
                }
                $invite = Invite::create([
                    'email' => $email,
                    'user_id' => auth()->id()
                ]);

                Mail::to($email)->Send(new ProfileInviteMail($user, $email));

                return $this->statusCode(200, "Profile Invite link sent successfully", ['invite' => $invite]);
            } else {
                return $this->statusCode(407, 'Please Login or signup with same email to continue');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            // return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/accept",
     * summary="Accept Profile Invite Request",
     * description="Accept Profile Invite Request. Kindly pass token and recepient as parametes. Note that this will be sent to the recepient email so no need to stress",
     * tags={"Accept Profile Invite Request"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Invite accepted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login or signup with same email to continue"),
     * )
     * )
     */
    public function acceptProfileInvite(Request $request)
    {
        // check if user is logged in
        if (request()->user('sanctum')) {
            $id = request()->user('sanctum')->id;
            // run validations
            $validate = Validator::make(
                $request->all(),
                [
                    'recepient' => ['required', 'email', 'indisposable'],
                    'token' => ['required'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }
            // check if token exists
            $chkOwner = User::whereToken($request->token)->count();
            if ($chkOwner == 0) {
                return $this->statusCode(404, 'No account found. Token invalid');
            }
            // get owner details
            $owner = User::whereToken($request->token)->first();
            // check if email and current login user are same

            $chkUser = User::whereEmail($request->recepient)->whereId($id)->count();
            if ($chkUser == 0) {
                return $this->statusCode(404, 'Sorry this email does not belong to current user');
            }
            // check if it's same user that link was sent to
            $receiver = User::find($id);

            $isTrue = Invite::whereEmail($receiver->email)->whereUser_id($owner->id)->whereInvite(0)->count();

            if ($isTrue == 0) {
                return $this->statusCode(404, 'Sorry your email does not match our records');
            }

            $invite = Invite::whereEmail($receiver->email)->whereUser_id($owner->id)->whereInvite(0)->first();
            $invite->invite = 1;
            if ($invite->save()) {
                $data = User::whereId($owner->id)->with('profile:id,country,phone_number,invite_link,bio,user_id', 'profession:profession,work_experience,linkedin,facebook,youtube,twitter,instagram,website,user_id')->first();
                return $this->statusCode(200, 'Invite accepted successfully', ['user' => $data]);
            } else {
                return $this->statusCode(500, 'Error occured whiles processing your request');
            }
        } else {
            return $this->statusCode(407, 'Please Login or signup with same email to continue');
        }
    }

    /**
     * @OA\Get(
     * path="/api/profile/{token}",
     * summary="View user's profile using profile link",
     * description="View user's profile using profile link",
     * tags={"View user's profile using profile link"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="User available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="User Not Found"),
     *      @OA\Response(response=407, description="Please Login or signup with same email to continue"),
     * )
     * )
     */
    public function viewProfileUsingProfileLink($token)
    {
        // check if user is logged in
        if (request()->user('sanctum')) {
            $id = request()->user('sanctum')->id;
            // run validations

            // check if token exists
            $chkOwner = Profile::whereToken($token)->count();
            if ($chkOwner == 0) {
                return $this->statusCode(404, 'No account found');
            }
            // get owner details
            $owner = Profile::whereToken($token)->pluck('id');

            $data = User::whereId($owner)->with('profile:id,country,phone_number,invite_link,bio,user_id', 'profession:profession,work_experience,linkedin,facebook,youtube,twitter,instagram,website,user_id')->first();
            return $this->statusCode(200, 'Invite accepted successfully', ['user' => $data]);
        } else {
            return $this->statusCode(407, 'Please Login or signup to view profile');
        }
    }

    /**
     * @OA\Get(
     * path="/api/invite/page",
     * summary="Accept Page Invite Request",
     * description="Accept Page Invite Request. Kindly pass token, recepient, pg_src and pg as parametes. Note that this will be sent to the recepient email so no need to stress",
     * tags={"Accept Page Invite Request"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Invite accepted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login or signup with same email to continue"),
     * )
     * )
     */


    public function acceptPageInvite(Request $request)
    {

        try {
            // check if user is logged in
            if (request()->user('sanctum')) {
                $id = request()->user('sanctum')->id;
                // run validations
                $validate = Validator::make(
                    $request->all(),
                    [
                        'recepient' => ['required', 'email', 'indisposable'],
                        'token' => ['required'],
                        'pg_src' => ['required', 'string'],
                        'pg' => ['required', 'integer'],
                    ]
                );

                if ($validate->fails()) {
                    return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
                }

                // check if token exists
                $chkOwner = Profile::whereToken($request->token)->count();
                if ($chkOwner == 0) {
                    return $this->statusCode(404, 'No account found. Token invalid');
                }
                // pluck user id from profile
                $user_id = Profile::whereToken($request->token)->pluck('user_id');

                // get owner details
                $owner = User::find($user_id[0]);
                // check if source falls within acceptable names
                $acceptTypes = ['album', 'business', 'event', 'project'];
                $hasMatch = false;
                foreach ($acceptTypes as $types) {
                    trim(strtolower($request->pg_src)) == $types ? $hasMatch = true : '';
                }

                if ($hasMatch == false) {
                    return $this->statusCode(404, 'Acceptable page types must fall within this filter [' . implode(', ', $acceptTypes) . ']');
                }

                // check if page id exits within the appropriate filter and belongs to sender login user
                switch (trim(strtolower($request->pg_src))) {
                    case 'album':
                        $chkPG = Album::whereId($request->pg)->whereUser_id($owner->id)->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                    case 'business':
                        $chkPG = Business::whereId($request->pg)->whereUser_id($owner->id)->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                    case 'event':
                        $chkPG = Event::whereId($request->pg)->whereUser_id($owner->id)->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                    case 'project':
                        $chkPG = Project::whereId($request->pg)->whereUser_id($owner->id)->count();
                        if ($chkPG == 0) {
                            return $this->statusCode(404, "Page not found");
                        }
                        break;
                }

                // check if email and current login user are same

                $chkUser = User::whereEmail($request->recepient)->whereId($id)->count();
                if ($chkUser == 0) {
                    return $this->statusCode(404, 'Sorry this email does not belong to current user');
                }
                // check if it's same user that link was sent to
                $receiver = User::find($id);

                $isTrue = Invite::whereEmail($receiver->email)->whereUser_id($owner->id)->whereInvite(0)->count();

                if ($isTrue == 0) {
                    return $this->statusCode(404, 'Sorry your email does not match our records');
                }

                $invite = Invite::whereEmail($receiver->email)->whereUser_id($owner->id)->whereInvite(0)->first();
                $invite->invite = 1;
                if ($invite->save()) {
                    $data = User::whereId($owner->id)->with('profile:id,country,phone_number,invite_link,bio,user_id', 'profession:profession,work_experience,linkedin,facebook,youtube,twitter,instagram,website,user_id')->first();
                    return $this->statusCode(200, 'Invite accepted successfully', ['user' => $data]);
                } else {
                    return $this->statusCode(500, 'Error occured whiles processing your request');
                }
            } else {
                return $this->statusCode(407, 'Please Login or signup with same email to continue');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, "Error occured while processing your request");
        }
    }

    public function inviteNonUSer(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => ['required', 'email', 'indisposable'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            // check if email belongs to user on the system
            $chkMail = User::whereEmail(trim(strtolower($request->email)))->exists();
            if ($chkMail) {
                return $this->statusCode(409, "Email is a user");
            }

            Mail::to($request->email)->Send(new NonUserInviteMail($request->email));

            return $this->statusCode(200, "Invite sent successfully");
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }
}
