<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    // Like post

    /**
     * @OA\POST(
     * path="/api/likes/{id}",
     * operationId="Like post by post  id",
     * tags={"Like post by post  id"},
     * summary="Like post by post  id",
     * description="Like post by post  id here",
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

    public function like(Post $post)
    {

        try {
            if (request()->user('sanctum')) {

                // check if current user following this business
                $post_id = Post::whereId($post->id)->count();

                if ($post_id > 0) {

                    $data['like'] = Like::create([
                        "user_id" => auth()->id(),
                        "post_id" => $post->id,
                        "liked" => true,
                    ]);
                }

//                return $post->id;
                return $this->statusCode(200, 'You like this post',['data'=>$data]);
            } else {

                return $this->statusCode(407, 'Please login');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    //Unlike post

    /**
     * @OA\DELETE(
     * path="/api/unlikes/{id}",
     * operationId="Unlike post by post  id",
     * tags={"Unlike post by post  id"},
     * summary="Unlike post by post  id",
     * description="Unlike post by post  id here",
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
    public function unlike(Post $post)
    {

        try {


            if (request()->user('sanctum')) {

                // check if current user following this business
                $post_id = Like::wherePost_id($post->id)->whereUser_id(auth()->id())->count();

                if ($post_id > 0) {
                    $post_like = Like::wherePost_id($post->id)->whereUser_id(auth()->id())->first();

                    $deleteLike = Like::find($post_like->id);
                    $deleteLike->delete();
                }

                return $this->statusCode(200, 'You have dislike this post');
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
