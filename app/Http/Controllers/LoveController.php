<?php

namespace App\Http\Controllers;

use App\Models\Love;
use App\Models\Post;
use Illuminate\Http\Request;

class LoveController extends Controller
{
    // Love post

    /**
     * @OA\POST(
     * path="/api/loves/{id}",
     * operationId="Love post by post  id",
     * tags={"Love post by post  id"},
     * summary="Love post by post  id",
     * description="Love post by post  id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the post ", type="number"),
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
    public function love(Post $post)
    {

        try {
            if (request()->user('sanctum')) {


                $post_id = Post::whereId($post->id)->count();

                if ($post_id > 0) {

                    $data['love'] = Love::create([
                        "user_id" => auth()->id(),
                        "post_id" => $post->id,
                        "loved" => true,
                    ]);
                }


                return $this->statusCode(200, 'You loved this post',['data'=>$data]);
            } else {

                return $this->statusCode(407, 'Please login');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    //Unlove post

    /**
     * @OA\DELETE(
     * path="/api/loves/{id}",
     * operationId="Unlove post by post  id",
     * tags={"Unlove post by post  id"},
     * summary="Unlove post by post  id",
     * description="Unlove post by post  id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the post ", type="number"),
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
    public function unlove(Post $post)
    {

        try {


            if (request()->user('sanctum')) {

                // check if current user following this business
                $post_id = Love::wherePost_id($post->id)->whereUser_id(auth()->id())->count();

                if ($post_id > 0) {
                    $post_love = Love::wherePost_id($post->id)->whereUser_id(auth()->id())->first();

                    $deleteLove = Love::find($post_love->id);
                    $deleteLove->delete();
                }

                return $this->statusCode(200, 'You have unloved this post');
            } else {

                return $this->statusCode(200, 'Please login');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }
}
