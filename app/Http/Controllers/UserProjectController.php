<?php

namespace App\Http\Controllers;

use App\Models\UserProject;
use Illuminate\Http\Request;

class UserProjectController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/userProject",
     * summary="Get user's projects",
     * description="Get user's projects",
     * tags={"Get user's projects"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Data available",
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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $chk = UserProject::whereUser_id(auth()->id())->count();
        if ($chk > 0) {
            $userProject = [
                'books' => UserProject::whereUser_id(auth()->id())->whereType('books')->paginate(30),
                'articles' => UserProject::whereUser_id(auth()->id())->whereType('articles')->paginate(30),
                'photography' => UserProject::whereUser_id(auth()->id())->whereType('photography')->paginate(30),
                'films' => UserProject::whereUser_id(auth()->id())->whereType('films')->paginate(30),
                'exhibition' => UserProject::whereUser_id(auth()->id())->whereType('exhibition')->paginate(30),
                'others' => UserProject::whereUser_id(auth()->id())->whereType('others')->paginate(30),
            ];
            return $this->statusCode(200, 'success', ['userProject' => $userProject]);
        }
        return $this->statusCode(404, 'No resource available');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Get(
     * path="/api/userProject/{id}",
     * summary="Get user's project Info based on id",
     * description="Get user's project Info based on id",
     * tags={"Get user's project Info based on id"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Data available",
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
    public function show(UserProject $userProject)
    {
        try {
            return ($userProject) ? $this->statusCode(200, 'Resource found', ['user$userProject' => $userProject]) : $this->statusCode(404, 'Resource not found');
        } catch (\Throwable $e) {
            return $this->statusCode(500, 'Error occured while processing your request');
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserProject $userProject)
    {
        //
    }

    /**
     * @OA\Delete(
     * path="/api/userProject/delete/{id}",
     * summary="Delete user's Project",
     * description="Delete user's Project",
     * tags={"Delete user's Project"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Project deleted successfully",
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
    public function destroy(UserProject $userProject)
    {
        try {
            if ($userProject) {
                return ($userProject->delete()) ? $this->statusCode(200, 'Resurce deleted successfully', ['user$userProject' => $userProject]) : $this->statusCode(500, "Error occured whiles processing your request. Try again later");
            } else {
                return $this->statusCode(404, 'Resource not found');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, 'Error occured while processing your request');
        }
    }
}
