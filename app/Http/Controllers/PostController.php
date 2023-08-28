<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Business;
use App\Models\Category;
use App\Models\Event;
use App\Models\Media;
use App\Models\Post;
use App\Models\Project;
use App\Models\SaveFromPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    // private $storage_path;
    private $path;
    private $media;
    public function __construct()
    {
        // $this->storage_path = public_path('posts/');
        $this->path = 'posts/';
        $this->media = new MediaController();
    }

    /**
     * @OA\POST(
     * path="/api/post/save",
     * operationId="Create New Post",
     * tags={"Post Creation"},
     * summary="User Create New Post",
     * description="User Create New Post here. Assign 'featured' to 'tag' field to denote if a post is a featured post.",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"media", "private", "category_id"},
     *               @OA\Property(property="title", type="string"),
     *               @OA\Property(property="media", type="file"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="album_id", type="integer"),
     *               @OA\Property(property="event_id", type="integer"),
     *               @OA\Property(property="business_id", type="integer"),
     *               @OA\Property(property="project_id", type="integer"),
     *               @OA\Property(property="tag", type="string"),
     *               @OA\Property(property="category_id", type="number"),
     *               @OA\Property(property="private", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Post Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Post Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */
    public function create(Request $request)
    {
        $post = $originalImage = '';
        $source = null;
        try {
            if (request()->user('sanctum')) {
                // VALIDATE INPUT FIELDS
                $validate = Validator::make(
                    $request->all(),
                    [
                        'title' => ['nullable'],
                        'media' => ['required', 'file'],
                        'description' => ['nullable'],
                        'private' => ['required', "integer"],
                        'album_id' => ['nullable', "integer"],
                        'business_id' => ['nullable', "integer"],
                        'event_id' => ['nullable', "integer"],
                        'project_id' => ['nullable', "integer"],
                        'category_id' => ['required', 'integer'],
                    ]
                );

                if ($validate->fails()) {
                    return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
                }

                $chk = Category::whereId($request->category_id)->count();
                if ($chk == 0) {
                    return $this->statusCode(404, 'Category id invalid');
                }

                if ($request->album_id != null) {
                    // check if id exists for the selected user
                    $chk = Album::whereId($request->album_id)->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Album id invalid');
                    }
                    $source = 'album';
                }

                if ($request->event_id != null) {
                    // check if id exists for the selected user
                    $chk = Event::whereId($request->event_id)->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Event id invalid');
                    }
                    // check if event came with album
                    if ($request->album_id != null) {
                        // check if album id for event is added
                        $chk = Album::whereId($request->album_id)->whereEvent_id($request->event_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Event Album id invalid');
                        }
                    }
                    $source = 'event';
                }

                if ($request->project_id != null) {
                    // check if id exists for the selected user
                    $chk = Project::whereId($request->project_id)->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Project id invalid');
                    }
                    // check if project came with album
                    if ($request->album_id != null) {
                        // check if album id for project is added
                        $chk = Album::whereId($request->album_id)->whereProject_id($request->project_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Project Album id invalid');
                        }
                    }
                    $source = 'project';
                }

                if ($request->business_id != null) {
                    // check if id exists for the selected user
                    $chk = Business::whereId($request->business_id)->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Business id invalid');
                    }
                    // check if business came with album
                    if ($request->album_id != null) {
                        // check if album id for business is added
                        $chk = Album::whereId($request->album_id)->whereBusiness_id($request->business_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Business Album id invalid');
                        }
                    }
                    $source = 'business';
                }

                $response = $this->media->checkFileFormat($request);

                if (!$response['hasMatch']) {
                    return $this->statusCode(400, $response['msg']);
                }

                $path = $this->path . auth()->id();
                // upload original media file
                $originalImage = $this->media->save($path, $request->file('media'));
                $thumbnail = [];
                if ($response['isTrueImage']) {
                    $path .= '/thumbnail/';
                    //create thumbnail;
                    $thumbnail = $this->media->createThumbnail($request, $path, 'media');
                }

                $trans = DB::transaction(function () use ($post, $request, $originalImage, $response, $source, $thumbnail) {
                    $post = new Post();
                    $post->user_id = auth()->id();
                    $post->title = $request->title;
                    $post->type = $response['type'];
                    $post->description = trim($request->description);
                    $post->private = $request->private;
                    $post->category_id = $request->category_id;
                    $post->tag = trim($request->tag) == '' ? null : trim(strtolower($request->tag));
                    $post->source = $source;
                    $post->album_id = $request->album_id;
                    $post->event_id = $request->event_id;
                    $post->project_id = $request->project_id;
                    $post->business_id = $request->business_id;
                    if ($post->save()) {
                        $media = $post->medias()->create([
                            'file_download' => null,
                            'file_view' => null,
                            'file_grid' => null,
                            'type' => $response['type'],
                            'path' => $originalImage,
                            'mime_type' => $response['mimeType'],
                            'small_thumbnail' => (count($thumbnail) > 0 && array_key_exists('small', $thumbnail)) ? $thumbnail['small'] : null,
                            'medium_thumbnail' => (count($thumbnail) > 0 && array_key_exists('medium', $thumbnail)) ? $thumbnail['medium'] : null,
                            'large_thumbnail' => (count($thumbnail) > 0 && array_key_exists('large', $thumbnail)) ? $thumbnail['large'] : null
                        ]);

                        $post = Post::with(
                            'postCount:id,post_id,downloads_count',
                            'user:id,first_name,last_name,email',
                            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                            'likes:id,post_id,user_id',
                            'claps:id,post_id,user_id',
                            'loves:id,post_id,user_id',
                            'postShares:id,post_id,share_count'
                        )
                            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
                            ->whereId($post->id)->first();

                        return $this->statusCode(200, "Post saved successfully", ['post' => $post, 'media' => $media]);
                    } else {
                        $this->media->destroyFile($originalImage);
                        return $this->statusCode(500, "Error occured whiles processing your request");
                    }
                });
                return $trans;
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            $this->media->destroyFile($originalImage);
            return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\PATCH(
     * path="/api/post/edit/{id}",
     * operationId="Update user Post",
     * tags={"Edit Post"},
     * summary="User Update Post",
     * description="User Update Post here. Assign 'featured' to 'tag' field to denote if a post is a featured post.",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"title", "description", "private", "category_id"},
     *               @OA\Property(property="title", type="string"),
     *               @OA\Property(property="media", type="file"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="album_id", type="integer"),
     *               @OA\Property(property="event_id", type="integer"),
     *               @OA\Property(property="business_id", type="integer"),
     *               @OA\Property(property="project_id", type="integer"),
     *               @OA\Property(property="category_id", type="number"),
     *               @OA\Property(property="private", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Post Updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Post Updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */

    public function update(Request $request, $id)
    {

        try {
            if (request()->user('sanctum')) {
                // VALIDATE INPUT FIELDS
                $validate = Validator::make(
                    $request->all(),
                    [
                        'title' => ['nullable'],
                        'description' => ['nullable'],
                        'private' => ['nullable'],
                        'album_id' => ['nullable', "integer"],
                        'business_id' => ['nullable', "integer"],
                        'event_id' => ['nullable', "integer"],
                        'project_id' => ['nullable', "integer"],
                        'category_id' => ['nullable'],
                        'media' => ['nullable', 'file'],
                    ]
                );

                if ($validate->fails()) {
                    return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
                }


                return DB::transaction(function () use ($request, $id) {
                    $thumbnail = [];
                    $path = $source = $originalImage = null;

                    // check if post id belongs to user
                    $chk = Post::whereId($id)->exists();
                    if (!$chk) {
                        return $this->statusCode(404, "Post not found");
                    }

                    $post = Post::whereId($id)->first();
                    $old_post = Post::whereId($id)->first();

                    if ($request->album_id != null) {
                        // check if id exists for the selected user
                        $chk = Album::whereId($request->album_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Album id invalid');
                        }
                        $source = 'album';
                    }

                    if ($request->event_id != null) {
                        // check if id exists for the selected user
                        $chk = Event::whereId($request->event_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Event id invalid');
                        }
                        // check if event came with album
                        if ($request->album_id != null) {
                            // check if album id for event is added
                            $chk = Album::whereId($request->album_id)->whereEvent_id($request->event_id)->count();
                            if ($chk == 0) {
                                return $this->statusCode(404, 'Event Album id invalid');
                            }
                        }
                        $source = 'event';
                    }

                    if ($request->project_id != null) {
                        // check if id exists for the selected user
                        $chk = Project::whereId($request->project_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Project id invalid');
                        }
                        // check if project came with album
                        if ($request->album_id != null) {
                            // check if album id for project is added
                            $chk = Album::whereId($request->album_id)->whereProject_id($request->project_id)->count();
                            if ($chk == 0) {
                                return $this->statusCode(404, 'Project Album id invalid');
                            }
                        }
                        $source = 'project';
                    }

                    if ($request->business_id != null) {
                        // check if id exists for the selected user
                        $chk = Business::whereId($request->business_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Business id invalid');
                        }
                        // check if business came with album
                        if ($request->album_id != null) {
                            // check if album id for business is added
                            $chk = Album::whereId($request->album_id)->whereBusiness_id($request->business_id)->count();
                            if ($chk == 0) {
                                return $this->statusCode(404, 'Business Album id invalid');
                            }
                        }
                        $source = 'business';
                    }

                    // check if request has file
                    if ($request->hasFile('media')) {
                        $response = $this->media->checkFileFormat($request);

                        if (!$response['hasMatch']) {
                            return $this->statusCode(400, $response['msg']);
                        }

                        $path = $this->path . auth()->id();
                        // upload original media file
                        $originalImage = $this->media->save($path, $request->file('media'));

                        if ($response['isTrueImage']) {
                            $path .= '/thumbnail/';
                            //create thumbnail;
                            $thumbnail = $this->media->createThumbnail($request, $path, 'media');
                        }
                    }
                    //
                    $post->title = trim($request->title);
                    $post->type = $response['type'] ?? $old_post->type;
                    $post->description = $request->description ?? $old_post->description;
                    $post->private = $request->private ?? $old_post->private;
                    $post->category_id = $request->category_id ?? $old_post->category_id;
                    $post->tag = trim($request->tag) == '' ? $old_post->tag : trim(strtolower($request->tag));
                    $post->source = $source ?? $old_post->source;
                    $post->album_id = $request->album_id ?? $old_post->album_id;
                    $post->event_id = $request->event_id ?? $old_post->event_id;
                    $post->project_id = $request->project_id ?? $old_post->project_id;
                    $post->business_id = $request->business_id ?? $old_post->business_id;
                    $post->user_id =  $old_post->user_id;

                    if ($post->save()) {
                        if ($originalImage != null) {
                            $media = Media::wherePost_id($post->id)->first();
                            $oldMedia = Media::wherePost_id($post->id)->first();

                            $media->path = $originalImage ?? $oldMedia->path;
                            $media->type = $response['type'] ?? $oldMedia->type;
                            $media->mime_type = $response['mimeType'] ?? $oldMedia->mime_type;
                            if ($response['isTrueImage']) {
                                $media->small_thumbnail = (count($thumbnail) > 0 && array_key_exists('small', $thumbnail)) ? $thumbnail['small'] : $oldMedia->small_thumbnail;
                                $media->medium_thumbnail = (count($thumbnail) > 0 && array_key_exists('medium', $thumbnail)) ? $thumbnail['medium'] : $oldMedia->medium_thumbnail;
                                $media->large_thumbnail = (count($thumbnail) > 0 && array_key_exists('large', $thumbnail)) ? $thumbnail['large'] : $oldMedia->large_thumbnail;
                            } else {
                                $media->small_thumbnail = null;
                                $media->medium_thumbnail = null;
                                $media->large_thumbnail = null;
                            }
                            $media->save();

                            // delete old media if new has been inserted
                            $originalImage != null ? $this->media->destroyFile($oldMedia->path) : '';

                            (count($thumbnail) > 0 && array_key_exists('small', $thumbnail)) ? $this->media->destroyFile($oldMedia->small_thumbnail) : '';

                            (count($thumbnail) > 0 && array_key_exists('medium', $thumbnail)) ? $this->media->destroyFile($oldMedia->medium_thumbnail) :  '';

                            (count($thumbnail) > 0 && array_key_exists('large', $thumbnail)) ? $this->media->destroyFile($oldMedia->large_thumbnail) : '';
                        }

                        $data = Post::with(
                            'postCount:id,post_id,downloads_count',
                            'user:id,first_name,last_name,email',
                            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                            'likes:id,post_id,user_id',
                            'claps:id,post_id,user_id',
                            'loves:id,post_id,user_id',
                            'postShares:id,post_id,share_count'
                        )
                            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
                            ->whereId($post->id)->first();

                        $newMedia = Media::wherePost_id($post->id)->first();

                        return $this->statusCode(200, "Post updated successfully", ['post' => $data, 'media' => $newMedia]);
                    } else {
                        return $this->statusCode(500, "Error occured whiles processing your request");
                    }
                });
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Delete(
     * path="/api/post/delete/{id}",
     * summary="Delete single user's post",
     * description="Delete single user's post",
     * tags={"Delete single user's post"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post deleted successfully",
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
    public function delete($id)
    {
        try {
            if (request()->user('sanctum')) {
                return DB::transaction(function () use ($id) {
                    $chk = Post::whereId($id)->whereUser_id(auth()->id())->count();
                    if ($chk > 0) {
                        $post = Post::whereId($id)->whereUser_id(auth()->id())->first();
                        // get media
                        $medias = $post->medias()->get()->toArray();
                        if ($post->delete()) {
                            if (count($medias) > 0) {
                                $this->media->destroyFile($medias[0]['path']);
                                $this->media->destroyFile($medias[0]['small_thumbnail']);
                                $this->media->destroyFile($medias[0]['medium_thumbnail']);
                                $this->media->destroyFile($medias[0]['large_thumbnail']);
                            }
                            return $this->statusCode(200, "Post deleted successfully");
                        }
                        return $this->statusCode(500, "Error occured whiles processing your request");
                    } else {
                        return $this->statusCode(404, "Post not found");
                    }
                });
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/post/user/{id}",
     * summary="Get single user's posts",
     * description="Get single user's posts",
     * tags={"Get single user's posts"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
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
    public function userPosts($id)
    {
        // check
        $chk = Post::whereUser_id($id)->exists();
        if (!$chk) {
            return $this->statusCode(404, "No post available for this user");
        }

        $post = Post::whereUser_id($id)->with(
            'postCount:id,post_id,downloads_count',
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
            'album',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postShares:id,post_id,share_count'
        )
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->wherePrivate(false)
            ->paginate(15);

        return $this->statusCode(200, "Posts available", ['post' => $post]);
    }

    /**
     * @OA\Get(
     * path="/api/post",
     * summary="Get all posts",
     * description="Get all posts",
     * tags={"Get all posts"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
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
    public function allPosts()
    {
        $post = null;

        try {

            //          $user = User::whereId(request()->user('sanctum')->id)->get();
            //          return $user;
            if (!auth()->id()){
                $post = Post::inRandomOrder()->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count',
                    'category:id,name',
                    'project:id,name,description,specialize',
                    'project.projectProfile:id,project_id,logo',
                    'event:id,name,description,specialize',
                    'event.eventProfile:id,event_id,logo',
                    'business:id,name,description,specialize',
                    'business.businessProfile:id,business_id,logo'
                )
                    ->whereDoesntHave('blocks', function (Builder $query) {
                        $query->where('user_id', '=', request()->user('sanctum')->id ?? null);
                    })
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps', 'postSave', 'downloads')
                    ->wherePrivate(false)
                    ->paginate(30);
            }else{
                if (request()->user('sanctum')->private == true){
                    $post = Post::inRandomOrder()->with(
                        'postCount:id,post_id,downloads_count',
                        'user:id,first_name,last_name,email',
                        'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                        'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                        'likes:id,post_id,user_id',
                        'claps:id,post_id,user_id',
                        'loves:id,post_id,user_id',
                        'postShares:id,post_id,share_count',
                        'category:id,name',
                        'project:id,name,description,specialize',
                        'project.projectProfile:id,project_id,logo',
                        'event:id,name,description,specialize',
                        'event.eventProfile:id,event_id,logo',
                        'business:id,name,description,specialize',
                        'business.businessProfile:id,business_id,logo'
                    )->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps', 'postSave', 'downloads')
                        ->where('user_id','=',request()->user('sanctum')->id)
                        ->whereNull('business_id')
                        ->whereNull('event_id')
                        ->whereNull('project_id')
                        ->whereNull('album_id')
                        ->paginate(30);
                }
                else{
                $post = Post::inRandomOrder()->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count',
                    'category:id,name',
                    'project:id,name,description,specialize',
                    'project.projectProfile:id,project_id,logo',
                    'event:id,name,description,specialize',
                    'event.eventProfile:id,event_id,logo',
                    'business:id,name,description,specialize',
                    'business.businessProfile:id,business_id,logo'
                )
                    ->whereDoesntHave('blocks', function (Builder $query) {
                        $query->where('user_id', '=', request()->user('sanctum')->id ?? null);
                    })
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps', 'postSave', 'downloads')
                    ->wherePrivate(false)
                    ->paginate(30);
            }
            }
            return $this->statusCode(200, "Posts available", ['post' => $post]);
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/post/info/{id}",
     * summary="Get post information",
     * description="Get post information",
     * tags={"Get post information"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
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

    public function postInfo($id)
    {

        try {
            // check if user has posts
            $chk = Post::whereId($id)->exists();

            if ($chk) {
                $post = Post::whereId($id)->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count'
                )
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->first();
                return $this->statusCode(200, "Post available", ['post' => $post]);
            } else {
                return $this->statusCode(404, 'No post available');
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/post/featured",
     * summary="Get featured posts",
     * description="Get featured posts",
     * tags={"Get featured posts"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     * )
     */

    public function getFeaturedPost()
    {
        try {
            // check if there is data in the table
            $chk = Post::whereTag('featured')->count();
            if ($chk > 0) {
                $post = Post::latest()->inRandomOrder()->whereTag('featured')->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count'
                )->whereDoesntHave('blocks', function (Builder $query) {
                    $query->where('user_id', '=', request()->user('sanctum')->id ?? null);
                })
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
                    ->wherePrivate(false)->paginate(15);
                return $this->statusCode(200, "Posts available", ['post' => $post]);
            } else {
                return $this->statusCode(404, "No data available");
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }



    /**
     * @OA\Get(
     * path="/api/keyword/search",
     * operationId="Auto Complete key word  Search ",
     * tags={"Auto Complete key word  Search"},
     * summary="Auto Complete key word  Search.]",
     * description="Auto Complete key word  Search here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"keyword"},
     *               @OA\Property(property="keyword", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Record Found ",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function fullKeywordSearch(Request $request)
    {
        $query = $request->keyword; // <-- Change the query for testing.

        $data['posts'] = Post::select('id', 'description', 'type', 'user_id', 'title')
            ->with(
                'medias',
                'likes:id,post_id,user_id',
                'claps:id,post_id,user_id',
                'loves:id,post_id,user_id',
                'postCount:id,post_id,downloads_count',
                'postShares:id,post_id,share_count'
            )
            ->whereDoesntHave('blocks', function (Builder $query) {
                $query->where('user_id', '=', request()->user('sanctum')->id ?? null);
            })
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps', 'postSave')
            ->wherePrivate(false)
            ->where('description', 'like', '%' . $query . '%')
            ->orWhere('type', 'like', '%' . $query . '%')
            ->orWhere('title', 'like', '%' . $query . '%')
            ->limit(5)->get();


        $data['users'] = User::join('profiles', 'users.id', '=', 'profiles.user_id')
            ->join('professions', 'users.id', '=', 'professions.user_id')
            ->join('industries', 'users.industry_id', '=', 'industries.id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'profiles.picture')->with('profession', 'profile')
            ->where('users.last_name', 'like', '%' . $query . '%')
//            ->where('users.private' ,'=',false)
            ->orWhere('users.first_name', 'like', '%' . $query . '%')
            ->orWhere('professions.profession', 'like', '%' . $query . '%')
            ->orWhere('industries.name', 'like', '%' . $query . '%')
            ->orWhere('profiles.country', 'like', '%' . $query . '%')
            ->limit(5)->get();

        $data['categories'] = Category::select('id', 'name')->with('posts', 'posts.medias')
            ->where('name', 'like', '%' . $query . '%')->get();

        $data['business'] = Business::with('industry', 'businessProfile')
            ->where('name', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->orWhere('country', 'like', '%' . $query . '%')
            ->get();

        $data['events'] = Event::with('industry', 'eventProfile')
            ->where('name', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->orWhere('location', 'like', '%' . $query . '%')
            ->orWhere('country', 'like', '%' . $query . '%')
            ->get();

        $data['projects'] = Project::with('industry', 'projectProfile')
            ->where('name', 'like', '%' . $query . '%')
            ->where('description', 'like', '%' . $query . '%')
            ->orWhere('country', 'like', '%' . $query . '%')
            ->get();

        if ($data) {
            return $this->statusCode(200, "Posts available", ['data' => $data]);
        } else {
            return $this->statusCode(404, "No data available");
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/posts",
     * summary="Get  user's posts",
     * description="Get  user's posts",
     * tags={"Get  user's posts"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
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
    public function myPosts()
    {
        try {
            if (request()->user('sanctum')) {
                $post = Post::whereUser_id(auth()->id())->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count'
                )->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->paginate(15);
                return $this->statusCode(200, "Posts available", ['post' => $post]);
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/images/posts",
     * summary="Get  user's images posts",
     * description="Get  user's images posts",
     * tags={"Get  user's images posts"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
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
    public function myImagePosts()
    {
        try {
            if (request()->user('sanctum')) {
                $post = Post::whereUser_id(auth()->id())->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count'
                )
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereType('image')->paginate(15);
                return $this->statusCode(200, "Posts available", ['post' => $post]);
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/videos/posts",
     * summary="Get  user's video posts",
     * description="Get  user's video posts",
     * tags={"Get  user's video posts"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Post available",
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

    public function myVideoPosts()
    {
        try {
            if (request()->user('sanctum')) {
                $post = Post::whereUser_id(auth()->id())->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count'
                )
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereType('video')->paginate(15);
                return $this->statusCode(200, "Posts available", ['post' => $post]);
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }


    //    /**
    //     * @OA\POST(
    //     * path="/api/user/messagings/medias/saves",
    //     * operationId="Save image or video from my messaging ",
    //     * tags={"Save image or video from my messaging"},
    //     * summary="Save image or video from my messaging",
    //     * description="User Save image or video from my messaging",
    //     *     @OA\RequestBody(
    //     *         @OA\JsonContent(),
    //     *         @OA\MediaType(
    //     *            mediaType="multipart/form-data",
    //     *            @OA\Schema(
    //     *               type="object",
    //     *               required={"media"},
    //     *               @OA\Property(property="media", type="file"),
    //     *            ),
    //     *        ),
    //     *    ),
    //     *      @OA\Response(
    //     *          response=201,
    //     *          description="Created  Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Created  Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=401, description="Error occured while processing request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=407, description="Please Login First"),
    //     * )
    //     */
    //    public function mySave(Request $request)
    //    {
    //       $type = $path = '';
    //        try {
    //            if (request()->user('sanctum')) {
    //                // VALIDATE INPUT FIELDS
    //                $validate = Validator::make(
    //                    $request->all(),
    //                    [
    //                        'media' => ['required'], //not more than 20mb
    //                    ]
    //                );
    //
    //                if ($validate->fails()) {
    //                    return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
    //                }
    //                // validate files
    //                // check if file is within acceptable formats
    //                $acceptTypes = ['mp4', 'MP4', 'MKV', 'mkv', 'jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG'];
    //                $imageTypes = ['jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG'];
    //                $videoTypes = ['mp4', 'MP4', 'MKV', 'mkv'];
    //                $file = $request->file('media');
    //                $file_type = $file->getClientOriginalExtension();
    //                $hasMatch = false;
    //
    //                // check if video type
    //                foreach ($videoTypes as $types) {
    //                    if (strtolower($types) == strtolower($file_type)) {
    //                        $hasMatch = true;
    //                        $type = 'video';
    //                    }
    //                }
    //                // check if image type
    //                foreach ($imageTypes as $types) {
    //                    if (strtolower($types) == strtolower($file_type)) {
    //                        $hasMatch = true;
    //                        $type = 'image';
    //                    }
    //                }
    //
    //                if (!$hasMatch) {
    //                    return $this->statusCode(400, 'Invalid file type. Acceptable types are [' . implode(', ', $acceptTypes) . ']');
    //                }
    //
    //                $path = $this->path . auth()->id();
    //
    //                $path = $this->media->save($path, $request->file('media'));
    //
    //                $trans = DB::transaction(function () use ($request, $type, $path) {
    //
    //                    $data['my_saves'] = MySave::create([
    //                    'user_id' => auth()->id(),
    //                    'type' => $type,
    //                    'path' => $path
    //                ]);
    //              if ($data) {
    //                        return $this->statusCode(200, "Saved successfully", [ 'data' => $data]);
    //                    } else {
    //                        $this->media->destroyFile($path);
    //                        return $this->statusCode(500, "Error occured whiles processing your request");
    //                    }
    //                });
    //                return $trans;
    //            } else {
    //                return $this->statusCode(407, 'Please Login First');
    //            }
    //        } catch (\Throwable $e) {
    //            $this->media->destroyFile($path);
    //            // return $this->statusCode(500, $e->getMessage());
    //            return $this->statusCode(500, "Error occured whiles processing your request");
    //        }
    //    }
    //
    //    /**
    //     * @OA\Delete(
    //     * path="/api/user/messagings/medias/saves/{id}",
    //     * summary="Delete single user's saves from messaging",
    //     * description="Delete single user's saves from messaging",
    //     * tags={"Delete single user's saves from messaging"},
    //     * security={ {"bearer": {} }},
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Deleted successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=401, description="Error occured while processing request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=407, description="Please Login First"),
    //     * )
    //     * )
    //     */
    //
    //    public function destroy(MySave $mySave)
    //    {
    //        try {
    //            if (request()->user('sanctum')) {
    //                $chk = MySave::whereId($mySave->id)->whereUser_id(auth()->id())->count();
    //                if ($chk > 0) {
    //                    $my_save = MySave::whereId($mySave->id)->whereUser_id(auth()->id())->first();
    //                    // get media
    //                    $medias = $my_save->select('path')->get();
    //                    if ($my_save->delete()) {
    //                        if (count($medias) > 0) {
    //                            $this->media->destroyFile($medias);
    //                        }
    //                        return $this->statusCode(200, "Deleted successfully");
    //                    }
    //                    return $this->statusCode(500, "Error occured whiles processing your request");
    //                } else {
    //                    return $this->statusCode(404, "Post not found");
    //                }
    //            } else {
    //                return $this->statusCode(407, 'Please Login First');
    //            }
    //        } catch (\Throwable $e) {
    //            return $this->statusCode(500, "Error occured whiles processing your request");
    //        }
    //    }
    //
    //
    //    /**
    //     * @OA\GET(
    //     * path="/api/user/messagings/medias/saves",
    //     * operationId="All my saves from messaging",
    //     * tags={"All my saves from messaging"},
    //     * summary="All my saves from messaging",
    //     * description="All my saves from messaging",
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Save successfully.",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=422,
    //     *          description="Unprocessable Entity",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=499, description="Account disabled"),
    //     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
    //     * )
    //     */
    //
    //    public function mySaveMedia()
    //    {
    //
    //
    //        $data['my_saves'] = MySave::whereUser_id(auth()->id())->orderByDesc('created_at')->paginate(30);
    //
    //        if ($data) {
    //            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
    //        } else {
    //            return $this->statusCode(404, 'Request  unsuccessful');
    //        }
    //
    //
    //    }
    //
    //    /**
    //     * @OA\GET(
    //     * path="/api/user/messagings/medias/saves/images",
    //     * operationId="My image  saves from messaging",
    //     * tags={"My image saves from messaging"},
    //     * summary="My image saves from messaging",
    //     * description="My image saves from messaging",
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Save successfully.",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=422,
    //     *          description="Unprocessable Entity",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=499, description="Account disabled"),
    //     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
    //     * )
    //     */
    //
    //    public function mySaveImage()
    //    {
    //
    //
    //        $data['my_saves'] = MySave::whereUser_id(auth()->id())->whereType('image')->orderByDesc('created_at')->paginate(30);
    //
    //        if ($data) {
    //            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
    //        } else {
    //            return $this->statusCode(404, 'Request  unsuccessful');
    //        }
    //
    //
    //    }
    //
    //    /**
    //     * @OA\GET(
    //     * path="/api/user/messagings/medias/saves/videos",
    //     * operationId="My videos  saves from messaging",
    //     * tags={"My videos saves from messaging"},
    //     * summary="My videos saves from messaging",
    //     * description="My videos saves from messaging",
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Save successfully.",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=422,
    //     *          description="Unprocessable Entity",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=499, description="Account disabled"),
    //     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
    //     * )
    //     */
    //
    //    public function mySaveVideo()
    //    {
    //
    //
    //        $data['my_saves'] = MySave::whereUser_id(auth()->id())->whereType('video')->orderByDesc('created_at')->paginate(30);
    //
    //        if ($data) {
    //            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
    //        } else {
    //            return $this->statusCode(404, 'Request  unsuccessful');
    //        }
    //
    //
    //    }



    /**
     * @OA\POST(
     * path="/api/user/saves/posts/{id}",
     * operationId="Save image or video from  posts ",
     * tags={"Save image or video from posts"},
     * summary="Save image or video from posts",
     * description="User Save image or video from posts",
     *      @OA\Response(
     *          response=201,
     *          description="Saved  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Saved  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */

    public function mySavePost(Post $post)
    {

        try {
            if (request()->user('sanctum')) {

                $trans = DB::transaction(function () use ($post) {
                    $chk = Post::whereId($post->id)->count();

                    if ($chk > 0) {
                        $data['my_post_saves'] = SaveFromPost::create([
                            'user_id' => auth()->id(),
                            'post_id' => $post->id
                        ]);
                    }

                    if ($data) {

                        return  $this->statusCode(200, "Saved successful", ['data' => $data]);
                    } else {

                        return $this->statusCode(500, "Error occured whiles processing your request");
                    }
                });
                return $trans;
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Delete(
     * path="/api/user/saves/posts/{id}",
     * summary="Delete single user's save post",
     * description="Delete single user's save post",
     * tags={"Delete single user's save post"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Deleted successfully",
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

    public function destroySavePost(SaveFromPost $saveFromPost)
    {
        try {
            if (request()->user('sanctum')) {
                $chk = SaveFromPost::whereId($saveFromPost->id)->whereUser_id(auth()->id())->count();
                if ($chk > 0) {
                    $my_save = SaveFromPost::whereId($saveFromPost->id)->whereUser_id(auth()->id())->first();
                    // get media
                    if ($my_save->delete()) {

                        return $this->statusCode(200, "Deleted successfully");
                    }
                    return $this->statusCode(500, "Error occured whiles processing your request");
                } else {
                    return $this->statusCode(404, "Post not found");
                }
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }


    /**
     * @OA\GET(
     * path="/api/user/mypost/saves",
     * operationId="All my saves from post",
     * tags={"All my saves from post"},
     * summary="All my saves from post",
     * description="All my saves from post",
     *      @OA\Response(
     *          response=200,
     *          description="Found successfully.",
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

    public function mySavePostMedia()
    {


        $data['posts'] = Post::with(
            'postCount:id,post_id,downloads_count',
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postShares:id,post_id,share_count'
        )
            ->whereHas('postSave', function ($query) {
                $query->where('user_id', auth()->id());
            })->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps', 'postSave')
            ->orderByDesc('created_at')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }

    /**
     * @OA\GET(
     * path="/api/user/mypost/saves/images",
     * operationId="My image  saves from posts",
     * tags={"My image saves from posts"},
     * summary="My image saves from posts",
     * description="My image saves from posts",
     *      @OA\Response(
     *          response=200,
     *          description="Save successfully.",
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

    public function mySavePostImage()
    {


        $data['posts'] = Post::with(
            'postCount:id,post_id,downloads_count',
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postShares:id,post_id,share_count'
        )
            ->whereHas('postSave', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps', 'postSave')
            ->whereType('image')
            ->paginate(30);


        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }

    /**
     * @OA\GET(
     * path="/api/user/mypost/saves/videos",
     * operationId="My videos  saves from posts",
     * tags={"My videos saves from posts"},
     * summary="My videos saves from posts",
     * description="My videos saves from posts",
     *      @OA\Response(
     *          response=200,
     *          description="Save successfully.",
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

    public function mySavePostsVideo()
    {
        $data['posts'] = Post::with(
            'postCount:id,post_id,downloads_count',
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postShares:id,post_id,share_count'
        )
            ->whereHas('postSave', function ($query) {
                $query->where('user_id', auth()->id());
            })->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps', 'postSave')
            ->whereType('video')
            ->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }

    /**
     * @OA\GET(
     * path="/api/user/privates/posts",
     * operationId="My privates posts",
     * tags={"My My privates posts"},
     * summary="My My privates posts",
     * description="My My privates posts",
     *      @OA\Response(
     *          response=200,
     *          description="Found successfully.",
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
    public function myPrivatePosts()
    {
        try {
            // check if data exists
            $chk = Post::whereUser_id(auth()->id())->wherePrivate(true)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No record available');
            } else {
                $post = Post::whereUser_id(auth()->id())->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count'
                )
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->wherePrivate(true)->paginate(15);
                return $this->statusCode(200, "Posts available", ['post' => $post]);
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }
    /**
     * @OA\GET(
     * path="/api/user/public/post",
     * operationId="My Public Posts",
     * tags={"Get User Public Posts only"},
     * summary="My Public posts",
     * description="My Public posts",
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
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function myPublicPosts()
    {
        try {
            // check if data exists
            $chk = Post::whereUser_id(auth()->id())->wherePrivate(false)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No record available');
            } else {
                $post = Post::whereUser_id(auth()->id())->with(
                    'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count'
                )
                    ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->wherePrivate(false)->paginate(15);
                return $this->statusCode(200, "Posts available", ['post' => $post]);
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }



    public function saveFile(Request $request)
    {
        $path = '';



        $validate = Validator::make(
            $request->all(),
            [

                'media' => ['nullable', 'file'], //not more than 20mb

            ]
        );

        if ($validate->fails()) {
            return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
        }

        //
        //                // validate files
        //                // check if file is within acceptable formats
        if ($request->hasFile('media')) {
            $acceptTypes = ['mp4', 'mov', 'wmv', 'mkv', 'avi', 'mpeg4', 'jpg', 'svg', 'jpeg', 'heif', 'png', 'gif'];
            $imageTypes = ['jpg', 'svg', 'jpeg', 'heif', 'png', 'gif'];
            $videoTypes = ['mp4', 'mov', 'wmv', 'mkv', 'avi', 'mpeg4'];
            $file = $request->file('media');
            $file_type = $file->getClientOriginalExtension();
            $hasMatch = false;

            // check if video type
            foreach ($videoTypes as $types) {
                if (strtolower($types) == strtolower($file_type)) {
                    $hasMatch = true;
                }
            }
            // check if image type
            foreach ($imageTypes as $types) {
                if (strtolower($types) == strtolower($file_type)) {
                    $hasMatch = true;
                }
            }

            if (!$hasMatch) {
                return $this->statusCode(400, 'Invalid file type. Acceptable types are [' . implode(', ', $acceptTypes) . ']');
            }

            $path = $this->path . auth()->id();

            $path = $this->media->save($path, $request->file('media'));

            return $this->statusCode(200, "File saved successfully", ['path' => $path]);
        } else {
            return $this->statusCode(200, "No file selected", ['path' => $path]);
        }
    }






    /**
     * @OA\POST(
     * path="/api/chats/files",
     * operationId="Save file from chat to gallary",
     * tags={"Save file from chat to gallary"},
     * summary="Save file from chat to gallary",
     * description="Save file from chat to gallary.",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"path", "private", "category_id","type"},
     *               @OA\Property(property="title", type="string"),
     *               @OA\Property(property="path", type="string"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="album_id", type="integer"),
     *               @OA\Property(property="event_id", type="integer"),
     *               @OA\Property(property="business_id", type="integer"),
     *               @OA\Property(property="project_id", type="integer"),
     *               @OA\Property(property="tag", type="string"),
     *               @OA\Property(property="type", type="string", description="video or image"),
     *               @OA\Property(property="category_id", type="number"),
     *               @OA\Property(property="private", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Saves Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Saves  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */

    public function saveFileFromChat(Request $request)
    {
        $post =  '';
        $source = null;

        //        return $request;
        try {
            if (request()->user('sanctum')) {
                // VALIDATE INPUT FIELDS
                $validate = Validator::make(
                    $request->all(),
                    [
                        'title' => ['nullable', 'max:50'],
                        'path' => ['required'], //not more than 20mb
                        'description' => ['nullable', 'max:100'],
                        'private' => ['required'],
                        'album_id' => ['nullable', "integer"],
                        'business_id' => ['nullable', "integer"],
                        'event_id' => ['nullable', "integer"],
                        'project_id' => ['nullable', "integer"],
                        'category_id' => ['required', 'integer'],
                    ]
                );

                if ($validate->fails()) {
                    return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
                }

                $chk = Category::whereId($request->category_id)->count();
                if ($chk == 0) {
                    return $this->statusCode(404, 'Category id invalid');
                }

                if ($request->album_id != null) {
                    // check if id exists for the selected user
                    $chk = Album::whereId($request->album_id)->whereUser_id(auth()->id())->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Album id invalid');
                    }
                    $source = 'album';
                }

                if ($request->event_id != null) {
                    // check if id exists for the selected user
                    $chk = Event::whereId($request->event_id)->whereUser_id(auth()->id())->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Event id invalid');
                    }
                    // check if event came with album
                    if ($request->album_id != null) {
                        // check if album id for event is added
                        $chk = Album::whereId($request->album_id)->whereUser_id(auth()->id())->whereEvent_id($request->event_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Event Album id invalid');
                        }
                    }
                    $source = 'event';
                }

                if ($request->project_id != null) {
                    // check if id exists for the selected user
                    $chk = Project::whereId($request->project_id)->whereUser_id(auth()->id())->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Project id invalid');
                    }
                    // check if project came with album
                    if ($request->album_id != null) {
                        // check if album id for project is added
                        $chk = Album::whereId($request->album_id)->whereUser_id(auth()->id())->whereProject_id($request->project_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Project Album id invalid');
                        }
                    }
                    $source = 'project';
                }

                if ($request->business_id != null) {
                    // check if id exists for the selected user
                    $chk = Business::whereId($request->business_id)->whereUser_id(auth()->id())->count();
                    if ($chk == 0) {
                        return $this->statusCode(404, 'Business id invalid');
                    }
                    // check if business came with album
                    if ($request->album_id != null) {
                        // check if album id for business is added
                        $chk = Album::whereId($request->album_id)->whereUser_id(auth()->id())->whereBusiness_id($request->business_id)->count();
                        if ($chk == 0) {
                            return $this->statusCode(404, 'Business Album id invalid');
                        }
                    }
                    $source = 'business';
                }


                $trans = DB::transaction(function () use ($post, $request, $source) {
                    $post = new Post();
                    $post->user_id = auth()->id();
                    $post->title = $request->title;
                    $post->type = $request->type;
                    $post->description = trim($request->description);
                    $post->private = $request->private;
                    $post->category_id = $request->category_id;
                    $post->tag = trim($request->tag) == '' ? null : trim(strtolower($request->tag));
                    $post->source = $source;
                    $post->album_id = $request->album_id;
                    $post->event_id = $request->event_id;
                    $post->project_id = $request->project_id;
                    $post->business_id = $request->business_id;
                    if ($post->save()) {
                        $media = $post->medias()->create([
                            'file_download' => null,
                            'file_view' => null,
                            'file_grid' => null,
                            'type' => $request->type,
                            'path' => $request->path,
                            'mime_type' => null
                        ]);

                        $post = Post::with(
                            'postCount:id,post_id,downloads_count',
                            'user:id,first_name,last_name,email',
                            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                            'likes:id,post_id,user_id',
                            'claps:id,post_id,user_id',
                            'loves:id,post_id,user_id',
                            'postShares:id,post_id,share_count'
                        )
                            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
                            ->whereId($post->id)->first();

                        return $this->statusCode(200, "Post saved successfully", ['post' => $post, 'media' => $media]);
                    } else {

                        return $this->statusCode(500, "Error occured whiles processing your request");
                    }
                });
                return $trans;
            } else {
                return $this->statusCode(407, 'Please Login First');
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }
}
