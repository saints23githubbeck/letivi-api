<?php

namespace App\Http\Controllers;

use App\Models\Clap;
use App\Models\Post;
use Illuminate\Http\Request;

class ClapController extends Controller
{
    // Clap post

    /**
     * @OA\Post(
     * path="/api/clap/{id}",
     * operationId="Clap post by post id",
     * tags={"Clap post by post id"},
     * summary="Clap post by post id",
     * description="Clap post by post id here",
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
     *          description="Claped  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Claped  Successfully",
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

    public function clap(Post $post)
    {

        try {
            if (request()->user('sanctum')) {

                // check if current user following this business
                $post_id = Post::whereId($post->id)->count();

                if ($post_id > 0) {

                    $data['clap'] = Clap::create([
                        "user_id" => auth()->id(),
                        "post_id" => $post->id,
                        "claped" => true,
                    ]);
                }
                return $this->statusCode(200, 'Request  successful',['data'=> $data]);
            } else {
                return $this->statusCode(400, 'You must login to perform this act');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    //Unclap post

    /**
     * @OA\DELETE(
     * path="/api/unclap/{id}",
     * operationId="Unclap post by post id",
     * tags={"Unclap post by post id"},
     * summary="Unclap post by post id",
     * description="Unclap post by post id here",
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
     *          description="Unclaped  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Unclaped  Successfully",
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
    public function unclap(Post $post)
    {

        try {


            if (request()->user('sanctum')) {

                // check if current user following this business
                $post_id = Clap::wherePost_id($post->id)->whereUser_id(auth()->id())->count();

                if ($post_id > 0) {
                    $clap = Clap::wherePost_id($post->id)->whereUser_id(auth()->id())->first();
                    $deleteClap = Clap::find($clap->id);
                    $deleteClap->delete();
                }

                return $this->statusCode(200, 'Request  successful');
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
