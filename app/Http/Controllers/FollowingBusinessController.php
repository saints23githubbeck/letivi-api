<?php

namespace App\Http\Controllers;


use App\Models\Business;
use App\Models\FollowingBusiness;

use Illuminate\Http\Request;

class FollowingBusinessController extends Controller
{


    //Follow business
    /**
     * @OA\POST(
     * path="/api/follow/business/{id}",
     * operationId="Follow business  by business  id",
     * tags={"Follow business  by business  id"},
     * summary="Follow business  by business  id",
     * description="Follow business  by business  id here",
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
    public function follow(Business $business)
    {



        try {
                if (request()->user('sanctum')) {

                    // check if current user following this business
                    $follow_id = Business::whereId($business->id)->count();
//                        return $follow_id;
                    if ($follow_id > 0) {

                        if (auth()->id() === $business->user_id) {

                            return $this->statusCode(422, 'You cannot follow Your own business page');
                        }

                         FollowingBusiness::create([
                            "user_id" => auth()->id(),
                            "business_id" => $business->id,
                        ]);
                    }
                    $data['followingCount'] = FollowingBusiness::whereBusiness_id($business->id)->count();

                    return $this->statusCode(200, 'You are following this business',['data'=>$data]);
                } else {
                    return $this->statusCode(407, 'Please login');
                }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    //Unfollow business

    /**
     * @OA\DELETE(
     * path="/api/unfollow/business/{id}",
     * operationId="Unfollow business  by business  id",
     * tags={"Unfollow business  by business  id"},
     * summary="Unfollow business  by business  id",
     * description="Unfollow business  by business  id here",
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

    public function unfollow(Business $business)
    {

        try {


                if (request()->user('sanctum')) {

                    // check if current user following this business
                    $follow_id = FollowingBusiness::whereBusiness_id($business->id)->whereUser_id(auth()->id())->count();

                    if ($follow_id > 0) {
                        $business_follow = FollowingBusiness::whereBusiness_id($business->id)->whereUser_id(auth()->id())->first();
//                        return $business_follow;
                            $deleteFollow = FollowingBusiness::find($business_follow->id);
                            $deleteFollow->delete();
                    }
                    $data['followingCount'] = FollowingBusiness::whereBusiness_id($business->id)->count();
                    return $this->statusCode(200, 'You are no longer following this business',['data'=>$data]);
                } else {
                    return $this->statusCode(407, 'Please login');
                }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

}
