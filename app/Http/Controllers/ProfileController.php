<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use App\Models\Profession;
use App\Models\ProfessionalInfo;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserFollowing;
use App\Models\UserProject;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    // private $storage_path;
    private $path;
    private $media;
    public function __construct()
    {
        $this->path = 'profile/';
        $this->media = new MediaController();
    }

    /**
     * @OA\Get(
     * path="/api/user/profile/{id}",
     * summary="Get user profile",
     * description="Get user profile",
     * tags={"Get user profile"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     * )
     */
    public function view($user_id)
    {
        $chk = User::whereId($user_id)->count();
        if ($chk > 0) {
            $user = User::with(
                'myfollowers.myFollower:id,first_name,last_name',
                'myfollowers.myFollower.profile:id,user_id,picture',
                'amfollowing.amFollowing:id,first_name,last_name',
                'amfollowing.amFollowing.profile:id,user_id,picture',
                'profile',
                'profession',
                'professionalInfo',
                'userProjects',
                'industry:id,name'
            )
                ->withCount('myfollowers', 'amfollowing', 'userProjects', 'posts', 'myImages', 'myVideos', 'myPrivatePosts')
                ->whereId($user_id)->first();
            return $this->statusCode(200, "Profile found", ['user' => $user]);
        } else {
            return $this->statusCode(404, "No User Found");
        }
    }


    /**
     * @OA\PATCH(
     * path="/api/user/profileUpdate",
     * operationId="Update user Profile",
     * tags={"Edit Profile"},
     * summary="User Update Profile",
     * description="User Update Profile",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"first_name", "last_name"},
     *               @OA\Property(property="first_name", type="string"),
     *               @OA\Property(property="last_name", type="string"),
     *               @OA\Property(property="gender", type="string"),
     *               @OA\Property(property="date_of_birth", type="string"),
     *               @OA\Property(property="industry", type="integer"),
     *               @OA\Property(property="other_industry", type="integer"),
     *               @OA\Property(property="profession", type="string"),
     *               @OA\Property(property="phone", type="string"),
     *               @OA\Property(property="biography", type="string"),
     *               @OA\Property(property="books[]", type="string"),
     *               @OA\Property(property="articles[]", type="string"),
     *               @OA\Property(property="photography[]", type="string"),
     *               @OA\Property(property="films[]", type="string"),
     *               @OA\Property(property="exhibition[]", type="string"),
     *               @OA\Property(property="others[]", type="string"),
     *               @OA\Property(property="website", type="string"),
     *               @OA\Property(property="facebook", type="string"),
     *               @OA\Property(property="instagram", type="string"),
     *               @OA\Property(property="twitter", type="string"),
     *               @OA\Property(property="linkedin", type="string"),
     *               @OA\Property(property="youtube", type="string"),
     *               @OA\Property(property="organization[]", type="string"),
     *               @OA\Property(property="awards[]", type="string"),
     *               @OA\Property(property="nomination[]", type="string"),
     *               @OA\Property(property="qualification[]", type="string"),
     *               @OA\Property(property="work_experience", type="integer"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Profile Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */
    public function updateProfile(Request $request, ?int $hasId = null)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'first_name' => ['required', 'string'],
                    'last_name' => ['required', 'string'],
                    'gender' => ['nullable', 'string'],
                    'date_of_birth' => ['nullable'],
                    'industry' => ['nullable', 'integer'],
                    'other_industry' => ['sometimes', 'nullable', 'string'],
                    'profession' => ['nullable', 'string'],
                    'phone' => ['nullable', 'string'],
                    'biography' => ['nullable', 'string'],
                    // projects tab
                    'books' => ['sometimes', 'array'],
                    'articles' => ['sometimes', 'array'],
                    'photography' => ['sometimes', 'array'],
                    'films' => ['sometimes', 'array'],
                    'exhibition' => ['sometimes', 'array'],
                    'others' => ['sometimes', 'array'],
                    // additional info tab
                    'website' => ['nullable', 'string'],
                    'facebook' => ['nullable', 'string'],
                    'instagram' => ['nullable', 'string'],
                    'twitter' => ['nullable', 'string'],
                    'linkedin' => ['nullable', 'string'],
                    'youtube' => ['nullable', 'string'],
                    // professional info tab
                    'organization' => ['sometimes', 'array'],
                    'awards' => ['sometimes', 'array'],
                    'nomination' => ['sometimes', 'array'],
                    'qualification' => ['sometimes', 'array'],
                    'work_experience' => ['nullable', 'integer']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            $user = User::find($hasId ?? auth()->id());

            return DB::transaction(function () use ($request, $user, $hasId) {
                $maxLength = 255; // Maximum length of the VARCHAR column
                $oldUser = $user;
                $user->update([
                    'first_name' => trim($request->first_name),
                    'last_name' => trim($request->last_name),
                    'gender' => $request->gender ?? $oldUser->gender,
                    'date_of_birth' => $request->date_of_birth == null ? $oldUser->date_of_birth : Carbon::createFromFormat('Y-m-d', $request->date_of_birth)->format('Y-m-d'),
                    'industry_id' => $request->industry ?? $oldUser->industry_id,
                    'other_industry' => $request->filled('industry') ? null : $request->other_industry,
                ]);

                $oldProfile = $user->profile()->first();
                $user->profile()->update([
                    'phone_number' => $request->phone ?? $oldProfile->phone_number,
                    'bio' => $request->biography ?? $oldProfile->bio
                ]);

                $oldProfession = $user->profession()->first();
                $user->profession()->update([
                    'profession' => $request->profession ?? $oldProfession->profession,
                    'linkedin' => $request->linkedin,
                    'facebook' => $request->facebook,
                    'twitter' => $request->twitter,
                    'instagram' => $request->instagram,
                    'youtube' => $request->youtube,
                    'website' => $request->website
                ]);

                // PROJECTS
                if ($request->has('books')) {
                    $books = $request->books;

                    if (!empty($books)) {
                        $myArr = [];
                        $hasNulledValue = false;
                        foreach ($books as $book) {
                            if (strlen($book) > $maxLength) {
                                throw new Exception("Books input text too long", 1);
                            }

                            $myArr[] = ['name' => $book, 'type' => 'books', 'user_id' => $user->id];

                            ($book === null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            UserProject::whereUser_id($user->id)->whereType('books')->delete();
                            UserProject::insert($myArr);
                        } else {
                            throw new Exception("Cannot process empty Books field(s)", 1);
                        }
                    }
                }

                if ($request->has('articles')) {
                    $articles = $request->articles;

                    if (!empty($articles)) {
                        $myArr = [];
                        $hasNulledValue = false;
                        foreach ($articles as $article) {
                            if (strlen($article) > $maxLength) {
                                throw new Exception("Article input text too long", 1);
                            }

                            $myArr[] = ['name' => $article, 'type' => 'articles', 'user_id' => $user->id];

                            ($article === null) ? $hasNulledValue = true : '';
                        }
                        if (!$hasNulledValue) {
                            UserProject::whereUser_id($user->id)->whereType('articles')->delete();
                            UserProject::insert($myArr);
                        } else {
                            throw new Exception("Cannot process empty Articles field(s)", 1);
                        }
                    }
                }

                if ($request->has('photography')) {
                    $photography = $request->photography;

                    if (!empty($photography)) {
                        $myArr = [];
                        $hasNulledValue = false;
                        foreach ($photography as $item) {
                            if (strlen($item) > $maxLength) {
                                throw new Exception("Photography input text too long", 1);
                            }
                            $myArr[] = ['name' => $item, 'type' => 'photography', 'user_id' => $user->id];

                            ($item === null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            UserProject::whereUser_id($user->id)->whereType('photography')->delete();
                            UserProject::insert($myArr);
                        } else {
                            throw new Exception("Cannot process empty Photography field(s)", 1);
                        }
                    }
                }

                if ($request->has('films')) {
                    $films = $request->films;

                    if (!empty($films)) {
                        $myArr = [];
                        $hasNulledValue = false;
                        foreach ($films as $film) {
                            if (strlen($film) > $maxLength) {
                                throw new Exception("Films input text too long", 1);
                            }
                            $myArr[] = ['name' => $film, 'type' => 'films', 'user_id' => $user->id];

                            ($film === null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            UserProject::whereUser_id($user->id)->whereType('films')->delete();
                            UserProject::insert($myArr);
                        } else {
                            throw new Exception("Cannot process empty Films field(s)", 1);
                        }
                    }
                }

                if ($request->has('exhibition')) {
                    $exhibition = $request->exhibition;

                    if (!empty($exhibition)) {
                        $myArr = [];
                        $hasNulledValue = false;
                        foreach ($exhibition as $item) {
                            if (strlen($item) > $maxLength) {
                                throw new Exception("Exhibition input text too long", 1);
                            }
                            $myArr[] = ['name' => $item, 'type' => 'exhibition', 'user_id' => $user->id];

                            ($item === null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            UserProject::whereUser_id($user->id)->whereType('exhibition')->delete();
                            UserProject::insert($myArr);
                        } else {
                            throw new Exception("Cannot process empty Exhibition field(s)", 1);
                        }
                    }
                }

                if ($request->has('others')) {
                    $others = $request->others;

                    if (!empty($others)) {
                        $myArr = [];
                        $hasNulledValue = false;
                        foreach ($others as $item) {
                            if (strlen($item) > $maxLength) {
                                throw new Exception("Others input text too long", 1);
                            }
                            $myArr[] = ['name' => $item, 'type' => 'others', 'user_id' => $user->id];

                            ($item === null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            UserProject::whereUser_id($user->id)->whereType('others')->delete();
                            UserProject::insert($myArr);
                        } else {
                            throw new Exception("Cannot process empty Others field(s)", 1);
                        }
                    }
                }

                // professional Info
                if ($request->has('organization')) {
                    $organization = $request->organization;

                    if (!empty($organization)) {
                        $myArr = [];
                        $hasNulledValue = false;

                        foreach ($organization as $item) {
                            $orgName = $item['organization'];
                            $role = $item['role'];
                            $country = $item['country'];

                            if (strlen($orgName) > $maxLength) {
                                throw new Exception("Organization input text too long", 1);
                            }

                            if (strlen($role) > $maxLength) {
                                throw new Exception("Organization Role input text too long", 1);
                            }

                            if (strlen($country) > $maxLength) {
                                throw new Exception("Organization Country input text too long", 1);
                            }

                            if ($orgName === null || $role === null || $country === null) {
                                throw new Exception("Cannot process empty Organization field(s)", 1);
                            }

                            $myArr[] = [
                                'organization' => $orgName,
                                'role' => $role,
                                'country' => $country,
                                'user_id' => $user->id
                            ];
                        }

                        ProfessionalInfo::whereUser_id($user->id)
                            ->whereNotNull('organization')
                            ->whereNotNull('role')
                            ->whereNotNull('country')
                            ->delete();

                        ProfessionalInfo::insert($myArr);
                    }
                }

                if ($request->has('awards')) {
                    $awards = $request->awards;

                    if (!empty($awards)) {
                        $myArr = [];
                        $hasNulledValue = false;

                        foreach ($awards as $award) {
                            $awardName = $award['awards'];
                            $country = $award['country'];

                            if (strlen($awardName) > $maxLength) {
                                throw new Exception("Award input text too long", 1);
                            }

                            if (strlen($country) > $maxLength) {
                                throw new Exception("Award Country input text too long", 1);
                            }

                            if ($awardName === null || $country === null) {
                                throw new Exception("Cannot process empty Award field(s)", 1);
                            }

                            $myArr[] = [
                                'awards' => $awardName,
                                'country' => $country,
                                'user_id' => $user->id
                            ];
                        }

                        ProfessionalInfo::whereUser_id($user->id)
                            ->whereNotNull('awards')
                            ->whereNotNull('country')
                            ->delete();

                        ProfessionalInfo::insert($myArr);
                    }
                }

                if ($request->has('nomination')) {
                    $nominations = $request->nomination;

                    if (!empty($nominations)) {
                        $myArr = [];
                        $hasNulledValue = false;

                        foreach ($nominations as $nomination) {
                            $nominationName = $nomination['nomination'];
                            $country = $nomination['country'];

                            if (strlen($nominationName) > $maxLength) {
                                throw new Exception("Nomination input text too long", 1);
                            }

                            if (strlen($country) > $maxLength) {
                                throw new Exception("Nomination Country input text too long", 1);
                            }

                            if ($nominationName === null || $country === null) {
                                throw new Exception("Cannot process empty Nomination field(s)", 1);
                            }

                            $myArr[] = [
                                'nomination' => $nominationName,
                                'country' => $country,
                                'user_id' => $user->id
                            ];
                        }

                        ProfessionalInfo::whereUser_id(auth()->id())
                            ->whereNotNull('nomination')
                            ->whereNotNull('country')
                            ->delete();

                        ProfessionalInfo::insert($myArr);
                    }
                }

                if ($request->has('qualification')) {
                    $qualifications = $request->qualification;

                    if (!empty($qualifications)) {
                        $myArr = [];
                        $hasNulledValue = false;

                        foreach ($qualifications as $qualification) {
                            $qualificationName = $qualification['qualification'];
                            $education = $qualification['education'];

                            if (strlen($qualificationName) > $maxLength) {
                                throw new Exception("Qualification input text too long", 1);
                            }

                            if (strlen($education) > $maxLength) {
                                throw new Exception("Qualification Education input text too long", 1);
                            }

                            if ($qualificationName === null || $education === null) {
                                throw new Exception("Cannot process empty Qualification field(s)", 1);
                            }

                            $myArr[] = [
                                'qualification' => $qualificationName,
                                'education' => $education,
                                'user_id' => $user->id
                            ];
                        }

                        ProfessionalInfo::whereUser_id($user->id)
                            ->whereNotNull('qualification')
                            ->whereNotNull('education')
                            ->delete();

                        ProfessionalInfo::insert($myArr);
                    }
                }

                if ($request->has('work_experience')) {
                    $workExperience = $request->work_experience;

                    ProfessionalInfo::updateOrCreate(
                        ['user_id' => $user->id],
                        ['work_experience' => $workExperience]
                    );
                }

                $data = $this->profileInfo($hasId);
                return $this->statusCode(200, "User Profile Updated successfully", $data);
            });
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
        }
    }

    /**
     * @OA\PATCH(
     * path="/api/user/updatePicture",
     * operationId="Update user Profile Picture",
     * tags={"Edit Profile Picture"},
     * summary="User Update Profile Picture",
     * description="User Update Profile Picture",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"media"},
     *               @OA\Property(property="media", type="file"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Profile Picture Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile Picture Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */
    public function updateProfilePicture(Request $request)
    {
        $path = null;
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'media' => ['required', 'file'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            $response = $this->media->checkFileFormat($request, null, 'image');

            if (!$response['hasMatch']) {
                return $this->statusCode(400, $response['msg']);
            }

            $this->path .= auth()->id() . '/';
            $thumb = $this->media->createLargeThumbnail($request, $this->path, 'media');
            $path = $thumb['large'];

            return DB::transaction(function () use ($path) {
                $profile = Profile::whereUser_id(auth()->id())->first();
                $oldImage = $profile->picture;
                $profile->picture = $path;
                if ($profile->save()) {
                    $this->media->destroyFile($oldImage);
                    return $this->statusCode(200, 'Profile picture updated successfully', ['profile' => $profile]);
                } else {
                    $this->media->destroyFile($path);
                    return $this->statusCode(500, 'Profile update failed');
                }
            });
        } catch (\Throwable $e) {
            $path != null ? $this->media->destroyFile($path) : '';
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/user/myProfile",
     * summary="Get login user profile information for update",
     * description="Get login user profile information for update",
     * tags={"Get login user profile information for update"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     * )
     */
    public function getMyProfileInfo()
    {

        try {
            $data = $this->profileInfo();
            return $this->statusCode(200, 'Record available', $data);
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
        }
    }

    private function profileInfo(?int $hasId = null)
    {
        $data = [];
        try {
            $user = User::select('id', 'first_name', 'last_name', 'private', 'gender', 'date_of_birth', 'industry_id', 'other_industry')->with(
                'profession:id,user_id,profession',
                'profile:id,user_id,picture,country,phone_number,bio'
            )->whereId($hasId ?? auth()->id())->first();
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
            return ['data' => $data];
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage(), 1);
        }
    }

    /**
     * @OA\PATCH(
     * path="/api/user/visibility",
     * operationId="Toggle User Privacy status",
     * tags={"Edit User Privacy status"},
     * summary="User Update User Privacy status",
     * description="User Update Privacy status",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={""},
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="User Privacy status Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="User Privacy status Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     * )
     */
    public function changePrivacy()
    {
        try {
            // check user status and update
            auth()->user()->togglePrivacyStatus();

            // get visibility state after change
            $status = auth()->user()->private === 1 ? "Account status changed to Private" : "Account status changed to Public";
            return $this->statusCode(200, $status, ['user' => auth()->user()]);
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }
}
