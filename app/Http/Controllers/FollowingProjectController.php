<?php

namespace App\Http\Controllers;

use App\Models\FollowingProject;
use App\Models\Project;
use Illuminate\Http\Request;

class FollowingProjectController extends Controller
{


    // Follow project page

    /**
     * @OA\POST(
     * path="/api/follow/project/{id}",
     * operationId="Follow project  by project  id",
     * tags={"Follow project  by project  id"},
     * summary="Follow project  by project   id",
     * description="Follow project  by project   id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the project ", type="number"),
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
    public function follow(Project $project)
    {

        try {
            if (request()->user('sanctum')) {

                // check if current user following this business
                $follow_id = Project::whereId($project->id)->count();

                if ($follow_id > 0) {

                    if (auth()->id() === $project->user_id) {


                        return $this->statusCode(422, 'You cannot follow Your own project page');
                    }

                    FollowingProject::create([
                        "user_id" => auth()->id(),
                        "project_id" => $project->id,
                    ]);
                }
                $data['followingCount'] = FollowingProject::whereProject_id($project->id)->count();

                return $this->statusCode(200, 'You are following this project',['data'=>$data]);
            } else {

                return $this->statusCode(407, 'You must login to perform this act');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    //Unfollow project page

    /**
     * @OA\DELETE(
     * path="/api/unfollow/project/{id}",
     * operationId="Unfollow project  by project  id",
     * tags={"Unfollow project  by project  id"},
     * summary="Unfollow project  by project   id",
     * description="Unfollow project  by project   id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the project ", type="number"),
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
    public function unfollow(Project $project)
    {

        try {


            if (request()->user('sanctum')) {

                // check if current user following this business
                $follow_id = FollowingProject::whereProject_id($project->id)->whereUser_id(auth()->id())->count();

                if ($follow_id > 0) {
                    $project_follow = FollowingProject::whereProject_id($project->id)->whereUser_id(auth()->id())->first();
                    $deleteFollow = FollowingProject::find($project_follow->id);
                    $deleteFollow->delete();
                }
                $data['followingCount'] = FollowingProject::whereProject_id($project->id)->count();
                return $this->statusCode(200, 'You are no longer following this project',['data'=>$data]);
            } else {


                return $this->statusCode(407, 'You must login to perform this act');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }
}
