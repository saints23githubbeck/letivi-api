<?php

namespace App\Http\Controllers;


use App\Models\Post;
use App\Models\Project;
use App\Models\WorkspaceCollaborator;
use App\Models\WorkspaceSponsor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    private $logo_path;
    private $media;

    public function __construct()
    {
        $this->logo_path = 'project/';
        $this->media = new MediaController();
    }
    //todo search user
    //    user::select('*',db:raw("CONCAT(users.colunm,' ',users.column) as full_name)where

    //Create event page

    /**
     * @OA\POST(
     * path="/api/projects",
     * operationId="Create project",
     * tags={"Create project"},
     * summary="Create project",
     * description="Create project here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name","specialize","description","logo","country"},
     *               @OA\Property(property="name", type="string"),
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
    public function create(Request $request)
    {
        $path = '';


        try {
            $request->validate([
                'name' => 'required|string',
                'specialize' => 'required|string',
                'industry_id' => 'sometimes|nullable|integer',
                'other_industry' => 'sometimes|nullable|string',
                'description' => 'required|string',
                'logo' => 'required|file',
                'country' => 'required|string',
                'collaborator_id' => 'nullable',
                'sponsor_id' => 'nullable',
            ]);
        } catch (\Throwable $e) {
            return $this->statusCode(403, $e->getMessage());
        }
        //        return $request;
        try {
            return DB::transaction(function () use ($request) {
                if ($request->hasFile('logo')) {
                    $response = $this->media->checkFileFormat($request, 'logo', 'image');

                    if (!$response['hasMatch']) {
                        return $this->statusCode(400, $response['msg']);
                    }

                    $this->logo_path .= auth()->id();
                    $path = $this->media->save($this->logo_path, $request->file('logo'));
                }

                $project = Project::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'industry_id' => $request->industry_id??null,
                    'other_industry' => $request->other_industry??null,
                    'specialize' => $request->specialize,
                    'country' => $request->country,
                    'user_id' => auth()->id(),
                ]);

                $project->projectWorkspaceSponsors()->create([
                    'sponsors' => $request->name,
                ]);

                $project->projectProfile()->create([
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
                    $project->projectMembers()->attach($member);
                    //
                }

                $data['project'] = Project::with('projectProfile')->whereId($project->id)->first();
                if ($data) {
                    return $this->statusCode(200, 'Request successful', ['data' => $data]);
                } else {
                    $this->media->destroyFile($path);
                    return $this->statusCode(400, 'Request unsuccessful');
                }
            });
        } catch (\Throwable $th) {
            $this->media->destroyFile($path);
            return $this->statusCode(500, $th->getMessage());
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }

    //Update  event page

    /**
     * @OA\PATCH(
     * path="/api/projects/{id}",
     * operationId="Update project by id",
     * tags={"Update project by id"},
     * summary="Update project by id",
     * description="Update project by id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id","name","specialize","description","logo","country"},
     *               @OA\Property(property="id", description="id of the project", type="number"),
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="industry_id", type="number"),
     *               @OA\Property(property="other_industry", type="number"),
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
    public function update(Request $request, Project $project)
    {

        //        return auth()->user();
        $path = null;
        try {
            $project_id = Project::whereId($project->id)->count();
            if ($project_id > 0) {

                return DB::transaction(function () use ($request, $project, $path) {
                    $project =  Project::find($project->id);
                    if ($request->hasFile('logo')) {
                        $response = $this->media->checkFileFormat($request, 'logo', 'image');

                        if (!$response['hasMatch']) {
                            return $this->statusCode(400, $response['msg']);
                        }


                        $this->logo_path .= auth()->id();
                        $path = $this->media->save($this->logo_path, $request->file('logo'));
                    }

                    $project->update([
                        'name' => $request->name ?? $project->name,
                        'description' => $request->description ?? $project->description,
                        'industry_id' => $request->industry_id ?? $project->industry_id,
                        'other_industry' => $request->other_industry ?? $project->other_industry,
                        'specialize' => $request->specialize ?? $project->specialize,
                        'country' => $request->country ?? $project->country,
                        'user_id' => $project->user_id,
                    ]);

                    $oldProjectProfile = $project->projectProfile()->first();

                    $project->projectProfile()->update([
                        'logo' => $path ?? $project->projectProfile->logo,
                        'linkedin' => $request->linkedin ?? $project->projectProfile->linkedin,
                        'twitter' => $request->twitter ?? $project->projectProfile->twitter,
                        'facebook' => $request->facebook ?? $project->projectProfile->facebook,
                        'instagram' => $request->instagram ?? $project->projectProfile->youtube,
                        'website' => $request->website ?? $project->projectProfile->website,
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
                        $project->projectMembers()->attach($member);
                        //
                    }

                    $data['project'] = Project::with('projectProfile')->whereId($project->id)->first();

                    if ($data) {
                        // delete old file
                        $path != null ? $this->media->destroyFile($oldProjectProfile->logo) : '';
                        //                        return $data;
                        return $this->statusCode(200, 'Request successful', ['data' => $data]);
                    } else {
                        // delete just uploaded file
                        $path != null ? $this->media->destroyFile($path) : '';
                        return $this->statusCode(400, 'Request unsuccessful');
                    }
                });
            }
        } catch (\Throwable $th) {
            $path != null ? $this->media->destroyFile($path) : '';
            return $this->statusCode(500, $th->getMessage());
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }

    // Delete event page

    /**
     * @OA\DELETE(
     * path="/api/projects/{id}",
     * operationId="Delete project by id",
     * tags={"Delete project by id"},
     * summary="Delete project by id",
     * description="Delete project by id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the project", type="number"),
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
    public function destroy(Project $project)
    {
        try {
            $project_id = Project::whereId($project->id)->count();
            if ($project_id > 0) {
                if (auth()->id() != $project->user_id) {
                    return $this->statusCode(403, "This workspace is not Yours");
                }
                DB::transaction(function () use ($project) {
                    $oldProjectProfile = $project->projectProfile()->first();
                    $project->delete();
                    $data['project'] = $project;
                    if ($data) {
                        $this->media->destroyFile($oldProjectProfile->logo);
                        return $this->statusCode(200, 'Request successful', ['data' => $data]);
                    } else {
                        return $this->statusCode(400, 'Request unsuccessful', ['data' => $data]);
                    }
                });
            }
        } catch (\Throwable $th) {
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/my/projects/workspaces",
     * operationId="Current login user project workspace",
     * tags={"Current login user project workspace"},
     * summary="Current login user project workspace",
     * description="Current login user project workspace",
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

    public function myProjectPage()
    {

        $chk =  Project::with('projectProfile')->whereUser_id(auth()->id())->count();
        $data = [];
        if ($chk > 0) {

            $data['project'] = Project::with(
                'industry',
                'albums',
                'projectProfile',
                'projectMembers:id,first_name,last_name',
                'projectMembers.profile',
                'projectMembers.profession',
                //                'projectSponsors:id,first_name,last_name',
                //                'projectSponsors.profile:id,user_id,picture',
                'projectFollowers.user:id,first_name,last_name',
                'projectFollowers.user.profile:id,user_id,picture'
            )
                ->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->whereUser_id(auth()->id())->get();
        }

        $data['my_member_project_workspaces'] = Project::with(
            'projectProfile',
            //            'businessMembers:id,first_name,last_name',
            //            'businessMembers.profile',
            //            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
            //            'businessFollowers.user:id,first_name,last_name',
            //            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('projectMembers', function ($query) {
            $query->where('user_id', '=', auth()->id());
        })->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }

    /**
     * @OA\Get(
     * path="/api/view/projects/workspaces/{id}",
     * operationId="View project workspace details",
     * tags={"View project workspace details"},
     * summary="View project workspace details",
     * description="View project workspace details",
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

    public function projectPageView(Project $project)
    {

        $chk =  Project::with('projectProfile')->whereId($project->id)->count();
        $data = [];
        if ($chk > 0) {

            $data['project'] = Project::with(
                'posts',
                'posts.medias',
                'industry',
                'albums',
                'projectProfile',
                'projectMembers:id,first_name,last_name',
                'projectMembers.profile:id,user_id,picture',
                //                'projectSponsors:id,first_name,last_name',
                //                'projectSponsors.profile:id,user_id,picture',
                'projectFollowers.user:id,first_name,last_name',
                'projectFollowers.user.profile:id,user_id,picture'
            )
                ->withCount('projectMembers', 'projectFollowers', 'albums', 'posts', 'myImages', 'myVideos')
                ->whereId($project->id)->get();
        }
        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }

    /**
     * @OA\GET(
     * path="/api/view/projects/workspaces/images{id}",
     * operationId="View All images posts in projects page",
     * tags={" View All images posts in projects page"},
     * summary=" view All images posts in projects page",
     * description="view All images posts in projects page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the projects"),
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

    public function projectPageViewImage(Project $project)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'project',
            'project.projectProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereProject_id($project->id)->whereType('image')->orderByDesc('created_at')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }

    /**
     * @OA\GET(
     * path="/api/view/projects/workspaces/videos{id}",
     * operationId="View All videos posts in projects page",
     * tags={" View All videos posts in projects page"},
     * summary=" view All videos posts in projects page",
     * description="view All videos posts in projects page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the projects"),
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

    public function projectPageViewVideos(Project $project)
    {


        $data['posts'] = Post::with(
            'medias',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count',
            'project',
            'project.projectProfile',
            'album'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereProject_id($project->id)->whereType('video')->orderByDesc('created_at')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }
}
