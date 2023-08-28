<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\View;
use Illuminate\Http\Request;

class ViewController extends Controller
{
    // Like post

    /**
     * @OA\POST(
     * path="/api/views/{id}",
     * operationId="View post by post  id",
     * tags={"View post by post  id"},
     * summary="View post by post  id",
     * description="View post by post  id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the post", type="number"),
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
    public function view(Post $post)
    {

        try {
            if (request()->user('sanctum')) {

                // check if current user following this business
                $post_id = Post::whereId($post->id)->count();

                if ($post_id > 0) {

                    $data['view'] = View::create([
                        "user_id" => auth()->id(),
                        "post_id" => $post->id,
                        "viewed" => true,
                    ]);
                }


                return $this->statusCode(200, 'You viewed this post',['data'=>$data]);
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
