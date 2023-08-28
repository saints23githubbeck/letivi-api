<?php

namespace App\Http\Controllers;


use App\Models\Business;
use App\Models\Post;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{

    private $logo_path;
    private $media;

    public function __construct()
    {
        $this->logo_path = 'logo/';
        $this->media = new MediaController();
    }
    //todo search user
    //    user::select('*',db:raw("CONCAT(users.colunm,' ',users.column) as full_name)where

    //Create business page


    /**
     * @OA\Post(
     * path="/api/business",
     * operationId="Create Business",
     * tags={"Create Business "},
     * summary="Create Business",
     * description="create Business here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name","tagline","specialize","description","logo","country"},
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="tagline", type="string"),
     *               @OA\Property(property="industry_id", type="number"),
     *               @OA\Property(property="other_industry", type="string"),
     *               @OA\Property(property="specialize", type="string"),
     *               @OA\Property(property="logo", type="file"),
     *               @OA\Property(property="country", type="string"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="collaborator_id", type="number"),
     *               @OA\Property(property="sponsor_id", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Business Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Business Created  Successfully",
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
     *      @OA\Response(response=407, description="Not authenticated"),
     * )
     */

    public function create(Request $request)
    {
        $path = '';

        try {
            $request->validate([
                'name' => 'required|string',
                'tagline' => 'required|string',
                'specialize' => 'required|string',
                'industry_id' => 'nullable|integer',
                'description' => 'required|string',
                'logo' => 'nullable|file',
                'country' => 'required|string',
                'collaborator_id' => 'nullable',
                'other_industry' => 'sometimes|nullable|string',
            ]);
        } catch (\Throwable $e) {
            return $this->statusCode(403, $e->getMessage());
        }

        try {
            return DB::transaction(function () use ($request, $path) {
                if ($request->hasFile('logo')) {
                    $response = $this->media->checkFileFormat($request, 'logo', 'image');

                    if (!$response['hasMatch']) {
                        return $this->statusCode(400, $response['msg']);
                    }

                    $this->logo_path .= auth()->id();
                    $path = $this->media->save($this->logo_path, $request->file('logo'));
                }

                $business = Business::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'tagline' => $request->tagline,
                    'industry_id' => $request->industry_id,
                    'other_industry' => $request->other_industry,
                    'specialize' => $request->specialize,
                    'country' => $request->country,
                    'user_id' => auth()->id(),
                ]);

                $business->businessProfile()->create([
                    'logo' => $path,
                    'linkedin' => $request->linkedin,
                    'twitter' => $request->twitter,
                    'facebook' => $request->facebook,
                    'instagram' => $request->instagram,
                    'youtube' => $request->youtube,
                    'website' => $request->website,
                ]);

                if ($request->input('collaborator_id')) {

                    $collaborator = [];
                    if (gettype($request->collaborator_id) == 'string') {
                        $myString = trim($request->collaborator_id, '[]');
                        $member = explode(',', $myString);
                    } else {
                        $member = $request->collaborator_id;
                    }
                    //
                    $business->businessMembers()->attach($member);
                    //
                }

                $data['business'] = Business::with('businessProfile')->whereId($business->id)->first();

                if ($data) {
                    return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                } else {
                    $this->media->destroyFile($path);
                    return $this->statusCode(422, 'Request  unsuccessful');
                }
            });
        } catch (\Throwable $th) {
            $this->media->destroyFile($path);
            return $this->statusCode(422, $th->getMessage());
            return $this->statusCode(422, "We couldn't process your request, please try again.");
        }
    }


    /**
     * @OA\PATCH(
     * path="/api/business/{id}",
     * operationId="Update Business",
     * tags={"Update Business by id"},
     * summary="Update Business by id",
     * description="Update Business here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id","name","tagline","specialize","description","logo","country"},
     *               @OA\Property(property="id", description="id of the business", type="string"),
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="tagline", type="string"),
     *               @OA\Property(property="industry_id", type="number"),
     *               @OA\Property(property="other_industry", type="string"),
     *               @OA\Property(property="specialize", type="string"),
     *               @OA\Property(property="logo", type="file"),
     *               @OA\Property(property="country", type="string"),
     *               @OA\Property(property="description", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Business Updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Business updated  Successfully",
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
     *      @OA\Response(response=407, description="Not authenticated"),
     * )
     */

    //Update  business page
    public function update(Request $request, Business $business)
    {

        //        return $request;
        $path = null;
        try {
            $business_id = Business::whereId($business->id)->count();
            if ($business_id > 0) {
                //                if (auth()->id() != $business->user_id){
                //                    return $this->statusCode(403, "This workspace is not Yours");
                //
                //                }
                return DB::transaction(function () use ($request, $business, $path) {
                    $business =  Business::find($business->id);
                    if ($request->hasFile('logo')) {
                        $response = $this->media->checkFileFormat($request, 'logo', 'image');

                        if (!$response['hasMatch']) {
                            return $this->statusCode(400, $response['msg']);
                        }

                        $this->logo_path .= auth()->id();
                        $path = $this->media->save($this->logo_path, $request->file('logo'));
                    }

                    $business->update([
                        'name' => $request->name ?? $business->name,
                        'description' => $request->description ?? $business->description,
                        'tagline' => $request->tagline ?? $business->tagline,
                        'industry_id' => $request->industry_id ?? $business->industry_id,
                        'other_industry' => $request->other_industry ?? $business->other_industry,
                        'specialize' => $request->specialize ?? $business->specialize,
                        'country' => $request->country ?? $business->country,
                        'user_id' => $business->user_id,
                    ]);


                    $business->businessProfile()->update([
                        'logo' => $path ?? $business->businessProfile->logo,
                        'linkedin' => $request->linkedin ?? $business->businessProfile->linkedin,
                        'twitter' => $request->twitter ?? $business->businessProfile->twitter,
                        'facebook' => $request->facebook ?? $business->businessProfile->facebook,
                        'instagram' => $request->instagram ?? $business->businessProfile->instagram,
                        'youtube' => $request->youtube ?? $business->businessProfile->youtube,
                        'website' => $request->website ?? $business->businessProfile->website,
                    ]);

                    if ($request->input('collaborator_id')) {
                        $collaborator = [];
                        if (gettype($request->collaborator_id) == 'string') {
                            $myString = trim($request->collaborator_id, '[]');
                            $member = explode(',', $myString);
                        } else {
                            $member = $request->collaborator_id;
                        }
                        //
                        $business->businessMembers()->attach($member);
                        //
                    }

                    $data['business'] = Business::with('businessProfile')->whereId($business->id)->first();
                    if ($data) {
                        return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                    } else {
                        $this->media->destroyFile($path);
                        return $this->statusCode(400, 'Request  unsuccessful');
                    }
                });
            }
        } catch (\Throwable $th) {
            $this->media->destroyFile($path);
            return $this->statusCode(500, $th->getMessage());
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }

    // Delete business page


    /**
     * @OA\Delete(
     * path="/api/business/{id}",
     * operationId="Delete Business by id",
     * tags={"Delete Business by id"},
     * summary="Delete Business by id",
     * description="Delete Business by id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the business", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Business Deleted  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Business Deleted  Successfully",
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
     *      @OA\Response(response=407, description="Not authenticated"),
     * )
     */
    public function destroy(Business $business)
    {
        try {
            $business_id = Business::whereId($business->id)->count();
            if ($business_id > 0) {
                DB::transaction(function () use ($business) {
                    if (auth()->id() != $business->user_id) {
                        return $this->statusCode(403, "This workspace is not Yours");
                    }
                    $businessProfile = $business->businessProfile()->first();
                    $business->delete();
                    $data['business'] = $business;
                    if ($data) {
                        $this->media->destroyFile($businessProfile->logo);
                        return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                    } else {
                        return $this->statusCode(400, 'Request  unsuccessful');
                    }
                });
            }
        } catch (\Throwable $th) {
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/my/business/workspaces",
     * operationId="Current login user business workspace",
     * tags={"Current login user business workspace"},
     * summary="Current login user business workspace",
     * description="Current login user business workspace",
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

    public function myBusinessPage()
    {
        //         return auth()->user();
        $chk =  Business::with('businessProfile')->whereUser_id(auth()->id())->count();
        $data = [];
        if ($chk > 0) {

            $data['business'] = Business::with(
                'albums',
                'businessMembers:id,first_name,last_name',
                'businessMembers.profile',
                'businessMembers.profession',
                //                 'businessSponsors:id,first_name,last_name',
                //                 'businessSponsors.profile:id,user_id,picture',
                'businessFollowers.user:id,first_name,last_name',
                'businessFollowers.user.profile:id,user_id,picture',
                'businessProfile'
            )
                ->withCount('businessMembers', 'businessFollowers', 'industry', 'albums', 'posts', 'myImages', 'myVideos')
                ->whereUser_id(auth()->id())->get();
        }
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
     * @OA\Get(
     * path="/api/view/business/workspaces/{id}",
     * operationId="View business workspace details",
     * tags={"View business workspace details"},
     * summary="View business workspace details",
     * description="CView business workspace details",
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

    public function businessPageView(Business $business)
    {

        $chk =  Business::with('businessProfile')->whereId($business->id)->count();
        $data = [];
        if ($chk > 0) {

            $data['business'] = Business::with(
                'albums',
                'posts',
                'posts.medias',
                'businessMembers:id,first_name,last_name',
                'businessMembers.profile',
                'businessMembers.profession',
                //                'businessSponsors:id,first_name,last_name',
                //                'businessSponsors.profile:id,user_id,picture',
                'businessFollowers.user:id,first_name,last_name',
                'businessFollowers.user.profile:id,user_id,picture',
                'businessProfile',
                'industry'
            )
                ->withCount('businessMembers', 'businessFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->whereId($business->id)->first();
        }
        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }

    /**
     * @OA\GET(
     * path="/api/view/business/workspaces/images{id}",
     * operationId="View All images posts in business page",
     * tags={" View All images posts in business page"},
     * summary=" view All images posts in business page",
     * description="view All images posts in business page here",
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

    public function businessPageViewImage(Business $business)
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
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereBusiness_id($business->id)->whereType('image')->orderByDesc('created_at')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }

    /**
     * @OA\GET(
     * path="/api/view/business/workspaces/videos{id}",
     * operationId="View All videos posts in business page",
     * tags={" View All videos posts in business page"},
     * summary=" view All videos posts in business page",
     * description="view All videos posts in business page here",
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

    public function businessPageViewVideos(Business $business)
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

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }
}
