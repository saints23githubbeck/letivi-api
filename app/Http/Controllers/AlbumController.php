<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Post;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class AlbumController extends Controller
{

    //Create album page


    /**
     * @OA\Post(
     * path="/api/albums",
     * operationId="Create album",
     * tags={"Create album "},
     * summary="Create albulm",
     * description="create album here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name","private"},
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="private", type="boolean"),
     *               @OA\Property(property="business_id", type="number"),
     *               @OA\Property(property="event_id", type="number"),
     *               @OA\Property(property="project_id", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Album Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Album Created  Successfully",
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

        try {
            $request->validate([
                'name' => 'required|string',
                'event_id' => 'sometimes|required|integer',
                'project_id' => 'sometimes|required|integer',
                'business_id' => 'sometimes|required|integer',
                'private' => 'required'
            ]);
        } catch (\Throwable $e) {
            return $this->statusCode(403, $e->getMessage());
        }


        try {

            return DB::transaction(function () use ($request) {

                if ($request->private == true) {

                    $request->private = 1;
                } elseif($request->private == false) {
                    $request->private = 0;
                }

                $data['album'] = Album::create([
                    'name' => $request->name,
                    'project_id' => $request->project_id,
                    'event_id' => $request->event_id,
                    'business_id' => $request->business_id,
                    'private' => $request->private,
                    'user_id' => auth()->id(),
                ]);


                if ($data) {

                    return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                } else {
                    return $this->statusCode(400, 'Request  unsuccessful');
                }
            });
        } catch (\Throwable $th) {

            return response()->json([
                // 'error' => $th->getMessage(),
                'message' => "We couldn't process your request, please try again."
            ]);
        }
    }



    /**
     * @OA\Patch(
     * path="/api/albums/{id}",
     * operationId="Update album",
     * tags={"Update album "},
     * summary="Update albulm",
     * description="Update album here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name","private"},
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="private", type="boolean"),
     *               @OA\Property(property="business_id", type="number"),
     *               @OA\Property(property="event_id", type="number"),
     *               @OA\Property(property="project_id", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Album Updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Album Updated  Successfully",
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
    //Update  album page
    public function update(Request $request, Album $album)
    {


        try {
            $album_id = Album::whereId($album->id)->count();
            if ($album_id > 0) {
                DB::transaction(function () use ($request, $album) {
                    if (auth()->id() != $album->user_id){
                        return $this->statusCode(403, "This Album is not Yours");

                    }
                    $album =  Album::find($album->id);


                    $data['album'] =   $album->update([
                        'name' => $request->name ?? $album->name,
                        'project_id' => $request->project_id ?? $album->project_id,
                        'event_id' => $request->event_id ?? $album->event_id,
                        'business_id' => $request->business_id ?? $album->business_id,
                        'user_id' => auth()->id(),
                    ]);


                    if ($data) {

                        return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                    } else {

                        return $this->statusCode(400, 'Request  unsuccessful');
                    }
                });
            }
        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);
        }
    }


    /**
     * @OA\Delete(
     * path="/api/albums/{id}",
     * operationId="Delete album",
     * tags={"Delete album "},
     * summary="Delete albulm",
     * description="Delete album here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(

     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Album Deleted  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Album Deleted  Successfully",
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

    // Delete business page
    public function destroy(Album $album)
    {

        try {
            $album_id = Album::whereId($album->id)->count();
            if (auth()->id() != $album->user_id){
                return $this->statusCode(403, "This album is not Yours");

            }
            if ($album_id > 0) {

                return $album->delete() ? $this->statusCode(200, "Album deleted successfully") : $this->statusCode(500, "Error occured whiles processing your request");
            }
        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);
        }
    }




    /**
     * @OA\Get(
     * path="/api/user/albums",
     * operationId="Current login user albums",
     * tags={"Current login user albums"},
     * summary="Current login user albums",
     * description="Current login user albums",
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

    public function myAlbum()
    {
        $media_path = Album::with('posts', 'posts.medias')
        ->whereUser_id(auth()->id())
        ->get()
        ->pluck('posts.*.medias.*.path')
        ->flatten()
        ->toArray();

        $cover = null;
        if (count($media_path) > 0) {
            foreach ($media_path as $path) {
                if ($path != null) {
                    $cover = $path;
                    break;
                }
            }
        }

        $data['cover_image'] = $cover;

        $data['albums'] = Album::with('posts', 'posts.medias')->withCount('posts')
            ->whereUser_id(auth()->id())->orderBy('created_at', 'DESC')->paginate(15);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/album/posts/{id}",
     * operationId="Current login user post in  album",
     * tags={"Current login user post in  album"},
     * summary="Current login user post in  album",
     * description="Current login user post in  album",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the album"),
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

    public function myAlbumPost(Album $album)
    {

        $data['posts'] = Post::with(
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'medias', 'likes:id,post_id,user_id', 'claps:id,post_id,user_id', 'loves:id,post_id,user_id')
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereUser_id(auth()->id())->whereAlbum_id($album->id)->orderBy('created_at', 'DESC')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }

    /**
     * @OA\Get(
     * path="/api/user/album/posts/images/{id}",
     * operationId="Current login user images post in  album",
     * tags={"Current login user images post in  album"},
     * summary="Current login user images post in  album",
     * description="Current login user images post in  album",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the album"),
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

    public function myAlbumPostImage(Album $album)
    {

        $data['posts'] = Post::with('medias', 'likes:id,post_id,user_id', 'claps:id,post_id,user_id', 'loves:id,post_id,user_id')
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereUser_id(auth()->id())->whereAlbum_id($album->id)->whereType('image')->orderBy('created_at', 'DESC')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/album/posts/videos/{id}",
     * operationId="Current login user videos post in  album",
     * tags={"Current login user videos post in  album"},
     * summary="Current login user videos post in  album",
     * description="Current login user videos post in  album",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the album"),
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

    public function myAlbumPostVideo(Album $album)
    {

        $data['posts'] = Post::with('medias', 'likes:id,post_id,user_id', 'claps:id,post_id,user_id', 'loves:id,post_id,user_id')
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')->whereUser_id(auth()->id())->whereAlbum_id($album->id)->whereType('video')->orderBy('created_at', 'DESC')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }



}
