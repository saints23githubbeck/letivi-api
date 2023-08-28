<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\UserFollowing;
use Illuminate\Http\Request;


class UserFollowingController extends Controller
{

    // Follow user

    /**
     * @OA\POST(
     * path="/api/follow/user/{id}",
     * operationId="Follow user by user id",
     * tags={"Follow user by user  id"},
     * summary="Follow user by user id",
     * description="Follow user by user id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successfully",
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
    public function follow(User $user)
    {

        try {
            if (request()->user('sanctum')) {

                // check if current user following this business
                $follow_id = User::whereId($user->id)->count();

                if ($follow_id > 0) {

                    if (auth()->id() === $user->id) {

                        return $this->statusCode(200, 'You cannot follow yourself');
                    }

                    // check if user has already followed this person
                    $chk = UserFollowing::whereUser_id(auth()->id())->whereFollowing_id($user->id)->count();
                    if ($chk == 0) {
                        $data['follow'] = UserFollowing::create([
                            "user_id" => auth()->id(),
                            "following_id" => $user->id,
                        ]);
                        return $this->statusCode(200, 'You following this user', ['data' => $data]);
                    } else {
                        return $this->statusCode(422, 'You are already following this user');
                    }
                }
            } else {
                return $this->statusCode(407, 'Please login');
            }
        } catch (\Throwable $th) {
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }

    //Unfollow user

    /**
     * @OA\DELETE(
     * path="/api/unfollow/user/{id}",
     * operationId="Unfollow user by user id",
     * tags={"Unfollow user by user  id"},
     * summary="Unfollow user by user id",
     * description="Unfollow user by user id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the user", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successfully",
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
    public function unfollow(User $user)
    {

        try {
            if (request()->user('sanctum')) {

                // check if current user following this business
                $follow_id = UserFollowing::whereFollowing_id($user->id)->whereUser_id(auth()->id())->count();

                if ($follow_id > 0) {
                    $follow_user = UserFollowing::whereFollowing_id($user->id)->whereUser_id(auth()->id())->first();
                    //                    return $follow_user;
                    $deleteFollow = UserFollowing::find($follow_user->id);
                    $deleteFollow->delete();
                }

                return $this->statusCode(200, 'You are no longer following this user');
            } else {

                return $this->statusCode(407, 'Please login');
            }
        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);
        }
    }

    /**
     * @OA\GET(
     * path="/api/myfollowers",
     * operationId="users followers",
     * tags={"All users followers"},
     * summary="All users followers",
     * description="All users followers here",
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

    public function myFollowers()
    {

        $data['my_followers_count'] = UserFollowing::with('myFollower')->whereFollowing_id(auth()->id())->count();

        $data['followers'] = UserFollowing::with(
            [
                'myFollower:id,first_name,last_name',
                'myFollower.profile',
                'myFollower.profession',
                'myFollower.posts' => function ($query) {
                    $query->selectRaw('user_id, count(*) as post_count')->groupBy('user_id');
                },
                'myFollower.myImages' => function ($query) {
                    $query->selectRaw('user_id, count(*) as image_post_count')->groupBy('user_id');
                },
                'myFollower.myVideos' => function ($query) {
                    $query->selectRaw('user_id, count(*) as video_post_count')->groupBy('user_id');
                },
                'myFollower.myVideos' => function ($query) {
                    $query->selectRaw('user_id, count(*) as video_post_count')->groupBy('user_id');
                },
                'myFollower.amFollowing' => function ($query) {
                    $query->selectRaw('user_id, count(*) as amFollowing_count')->groupBy('user_id');
                },
                'myFollower.myFollowers' => function ($query) {
                    $query->selectRaw('following_id, count(*) as myFollowers_count')->groupBy('following_id');
                },
            ]
        )
            ->whereFollowing_id(auth()->id())->orderBy('created_at', 'DESC')->paginate(30);

        if ($data['followers']->count() > 0) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'No one following you');
        }
    }


    /**
     * @OA\GET(
     * path="/api/amfollowing",
     * operationId=" users i follow",
     * tags={"All users i follow"},
     * summary="All users i follow",
     * description="All users i follow here",
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

    public function amFollowing()
    {

        $data['my_followings_count'] = UserFollowing::with('amFollowing')->whereUser_id(auth()->id())->count();

        $data['following'] = UserFollowing::with(
            [
                'amFollowing:id,first_name,last_name',
                'amFollowing.profile:*',
                'amFollowing.profession',
                'amFollowing.posts' => function ($query) {
                    $query->selectRaw('user_id, count(*) as post_count')->groupBy('user_id');
                },
                'amFollowing.myImages' => function ($query) {
                    $query->selectRaw('user_id, count(*) as image_post_count')->groupBy('user_id');
                },
                'amFollowing.myVideos' => function ($query) {
                    $query->selectRaw('user_id, count(*) as video_post_count')->groupBy('user_id');
                },
                'amFollowing.myVideos' => function ($query) {
                    $query->selectRaw('user_id, count(*) as video_post_count')->groupBy('user_id');
                },
                'amFollowing.amFollowing' => function ($query) {
                    $query->selectRaw('user_id, count(*) as amFollowing_count')->groupBy('user_id');
                },
                'amFollowing.myFollowers' => function ($query) {
                    $query->selectRaw('following_id, count(*) as myFollowers_count')->groupBy('following_id');
                },
            ]
        )
            ->whereUser_id(auth()->id())->orderBy('created_at', 'DESC')->paginate(30);

        if ($data['following']->count() > 0) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Currently has no one following');
        }
    }

    /**
     * @OA\GET(
     * path="/api/myfollowing/workspace",
     * operationId="users workspace following",
     * tags={"All users workspace following"},
     * summary="All users workspace following",
     * description="All users workspace following here",
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
    public function myFollowingWorkspace()
    {


        $data['workspacefollowing'] = User::select('id', 'first_name', 'last_name')
            ->with(
                'followbusiness.business:id,name,description,country,industry_id',
                'followbusiness.business.businessProfile',
                'followbusiness.business.industry:id,name',
                'followevent.event:id,name,description,industry_id,location',
                'followevent.event.eventProfile',
                'followevent.event.industry:id,name',
                'followproject.project:id,name,description,industry_id',
                'followproject.project.projectProfile',
                'followproject.project.industry:id,name'
            )
            ->whereId(auth()->id())->orderBy('created_at', 'DESC')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }
}
