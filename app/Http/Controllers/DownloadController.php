<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Post;
use App\Models\PostDownloadCount;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\getCount;

class DownloadController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/downloads/increase/post/{id}",
     * operationId="Increase count on Post Media Download",
     * tags={"Increase count on Post Media Download"},
     * summary="Increase count on Post Media Download",
     * description="Increase count on Post Media Download here",
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
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function increase_count($post_id)
    {
        $media = new MediaController;
        try {
            if (request()->user('sanctum')) {
                // get info related to the post
                $chk = Post::whereId($post_id)->count();
                $increament  = 1;
                if ($chk > 0) {
                    $post = Post::find($post_id);
                    $download = new Download();
                    $download->downloader_id = auth()->id();
                    $download->user_id = $post->user_id;
                    $download->post_id = $post->id;
                    $download->save();

                    $data['downloads'] = $download;
                    $getCount =  PostDownloadCount::wherePost_id($post_id)->count();

                    //                       return $getCount;
                    if ($getCount > 0) {
                        $post->postCount()->update([
                            'downloads_count' => $getCount + $increament,
                        ]);
                    } else {
                        $post->postCount()->Create([
                            'downloads_count' => 1,
                        ]);
                    }

                    $downloadCount = $post->postCount()->select('downloads_count')->first();

                    return $data ? $this->statusCode(200, "Download count recorded", ['downloads' => $downloadCount]) : $this->statusCode(500, "Error occured whiles processing your request");
                } else {
                    return $this->statusCode(404, 'Post not found');
                }
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
     * path="/api/downloads/post/{id}",
     * operationId="Download Post Media",
     * tags={"Download Post Media"},
     * summary="Download Post Media",
     * description="Download Post Media here",
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
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function download($post_id)
    {
        $media = new MediaController;
        try {
            $chk = Post::whereId($post_id)->count();
            if ($chk > 0) {
                $post = Post::find($post_id);
                $postMedia = $post->medias()->first();
                return $media->download($postMedia->path, $postMedia->mime_type);
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\GET(
     * path="/api/mydownloads",
     * operationId="All users downloads",
     * tags={"All users downloads"},
     * summary="All users downloads",
     * description="All users downloads here",
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

    public function myDownload()
    {

        $data['mydownloads'] = Post::with(
            'postCount:id,post_id,downloads_count',
            'medias:path,file_view,file_download,file_grid,post_id',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'user.myImages:id,user_id',
            'user.myVideos:id,user_id'
        )
            ->whereHas('downloads', function ($query) {
                $query->where('Downloader_id', auth()->id());
            })
            ->orderBy('created_at', 'DESC')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }

    /**
     * @OA\GET(
     * path="/api/mydownloaders",
     * operationId="All users downloaders",
     * tags={"All users downloaders"},
     * summary="All users downloaders",
     * description="All users downloaders here",
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

    public function myDownloader()
    {
//        return auth()->user();
        $data['mydownloads'] = Post::with(
            'postCount:id,post_id,downloads_count',
            'downloads.myDownloaders:id,first_name,last_name',
            'downloads.myDownloaders.profile:id,user_id,picture',
//            'user.profile:id,country,phone_number,invite_link,bio,user_id',
            'medias'
//            'likes:id,post_id,user_id',
//            'claps:id,post_id,user_id',
//            'loves:id,post_id,user_id'
        )
            ->whereHas('downloads', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderBy('created_at', 'DESC')->paginate(30);

        //

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }
}
