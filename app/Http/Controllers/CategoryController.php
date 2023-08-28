<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{

    // Create category

    /**
     * @OA\Post(
     * path="/api/categories",
     * operationId="Create Category",
     * tags={"Create Category "},
     * summary="Create Category",
     * description="create Category here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name"},
     *               @OA\Property(property="name", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Category Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Category Created  Successfully",
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
            ]);
        } catch (\Throwable $e) {
            return $this->statusCode(403, $e->getMessage());
        }

        $name = trim(strtolower($request->name));

        try {
            $chk = Category::whereName($name)->count();
            if ($chk > 0) {
                return $this->statusCode(409, "Name already exist");
            }
            return DB::transaction(function () use ($name) {

                $data['categories'] = Category::create([
                    'name' => $name,
                ]);

                if ($data) {
                    return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                } else {
                    return $this->statusCode(404, 'Request  unsuccessful');
                }
            });
        } catch (\Throwable $th) {
            return  $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }

    // Autocomplete search category name

    /**
     * @OA\Get(
     * path="/api/categories/search",
     * operationId="Auto complete search Category name",
     * tags={"Auto complete search Category name"},
     * summary="Auto complete search Category name",
     * description="Auto complete search Category name here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name"},
     *               @OA\Property(property="name", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Record Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Record Successfully",
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

    public function getCategories(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        try {

            $data['categories'] = Category::latest()->where('name', 'LIKE', "%{$request->name}%")->get();

            //checking if request exit

            if ($data) {
                return $this->statusCode(200, 'Request  successful', ['data' => $data]);
            } else {
                return $this->statusCode(400, 'Request  unsuccessful');
            }
        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);
        }
    } // Autocomplete search category name

    /**
     * @OA\Get(
     * path="/api/categories",
     * operationId="All categories",
     * tags={"All categories"},
     * summary="All categories",
     * description="All categories name here",
     *      @OA\Response(
     *          response=200,
     *          description="Record Successfully",
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

    public function index()
    {

        try {

            $data['categories'] = Category::latest()->get();
            //checking if request exit
            if ($data) {
                return $this->statusCode(200, 'Request  successful', ['data' => $data]);
            } else {
                return $this->statusCode(400, 'Request  unsuccessful');
            }
        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);
        }
    }

    /**
     * @OA\Get(
     * path="/api/posts/categories/{category}",
     * operationId="Get all post on selected category",
     * tags={"Get all post on selected category"},
     * summary="Get all post on selected category",
     * description="Get all post on selected category here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the category"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Record Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Record Successfully",
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

    public function postOnCategory($category)
    {
        // $userId = request()->user('sanctum')->id;
        $chk = Category::whereId($category)->count();
        if ($chk > 0) {
            $data['posts'] = Post::inRandomOrder()->with(
                 'postCount:id,post_id,downloads_count',
                    'user:id,first_name,last_name,email',
                    'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
                    'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
                    'likes:id,post_id,user_id',
                    'claps:id,post_id,user_id',
                    'loves:id,post_id,user_id',
                    'postShares:id,post_id,share_count',
                    'category:id,name'
            )->whereDoesntHave('blocks', function (Builder $query) {
                    $query->where('user_id','=',request()->user('sanctum')->id??null);
                })
                ->withCount('comments','views','impressions','likes','loves','claps','postSave', 'downloads')
                ->whereCategory_id($category)
                ->wherePrivate(false)
                // >when(request()->user('sanctum')->id??null, function ($query, $userId) {
                //     return $query->where('user_id', '!=', $userId);
                // })
                // ->where('user_id','!=',auth()->id())
//                ->orderBy('created_at','DESC')
                ->paginate(30);

            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'No Post found');
        }
    }


    /**
     * @OA\Get(
     * path="/api/categories/posts/images{category}",
     * operationId="Get all images post on selected category",
     * tags={"Get all images post on selected category"},
     * summary="Get all images post on selected category",
     * description="Get all images post on selected category here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the category"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Record Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Record Successfully",
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

    public function postImageOnCategory(Category $category)
    {

        $data['posts'] = Post::inRandomOrder()->with(
            'postCount:id,post_id,downloads_count',
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postShares:id,post_id,share_count',
            'category:id,name'
        )->whereDoesntHave('blocks', function (Builder $query) {
                $query->where('user_id','=',request()->user('sanctum')->id);
            })
            ->withCount('comments','views','impressions','likes','loves','claps','postSave')
            ->whereCategory_id($category->id)
            ->whereType('image')
            ->wherePrivate(false)
            ->where('user_id','!=',auth()->id())->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }



    /**
     * @OA\Get(
     * path="/api/categories/posts/videos{category}",
     * operationId="Get all vidoes post on selected category",
     * tags={"Get all vidoes post on selected category"},
     * summary="Get all vidoes post on selected category",
     * description="Get all vidoes post on selected category here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the category"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Record Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Record Successfully",
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

    public function postVideoOnCategory(Category $category)
    {

        $data['posts'] = Post::inRandomOrder()->with(
            'postCount:id,post_id,downloads_count',
            'user:id,first_name,last_name,email',
            'user.profile:id,picture,country,phone_number,invite_link,bio,user_id',
            'medias:small_thumbnail,medium_thumbnail,large_thumbnail,path,file_view,file_download,file_grid,post_id',
            'likes:id,post_id,user_id',
            'claps:id,post_id,user_id',
            'loves:id,post_id,user_id',
            'postShares:id,post_id,share_count',
            'category:id,name'
        )->whereDoesntHave('blocks', function (Builder $query) {
                $query->where('user_id','=',request()->user('sanctum')->id);
            })
            ->withCount('comments','views','impressions','likes','loves','claps','postSave')
            ->whereCategory_id($category->id)
            ->whereType('video')
            ->wherePrivate(false)
            ->where('user_id','!=',auth()->id())->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful');
        }
    }



    //
    //
    //
    //    /**
    //     * @OA\Get(
    //     * path="/api/category/posts/{category}",
    //     * operationId="Get all post on selected categories",
    //     * tags={"Get all post on selected categories"},
    //     * summary="Get all post on selected categories",
    //     * description="Get all post on selected categories here",
    //     *     @OA\RequestBody(
    //     *         @OA\JsonContent(),
    //     *         @OA\MediaType(
    //     *            mediaType="multipart/form-data",
    //     *            @OA\Schema(
    //     *               type="object",
    //     *               required={"id"},
    //     *               @OA\Property(property="id", type="integer",description="id of the category"),
    //     *            ),
    //     *        ),
    //     *    ),
    //     *      @OA\Response(
    //     *          response=201,
    //     *          description="Record Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Record Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=422,
    //     *          description="Unprocessable Entity",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=401, description="Error occured while processing request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=407, description="Not authenticated"),
    //     * )
    //     */
    //
    //    public function postOnCategories(Category $category)
    //    {
    //
    //        $data['posts'] = Post::with('medias','likes:id,post_id,user_id','claps:id,post_id,user_id','loves:id,post_id,user_id')
    //            ->withCount('comments','views','impressions','likes','loves','claps')->whereCategory_id($category->id)->orderBy('created_at','DESC')->paginate(30);
    //
    //        if ($data) {
    //            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
    //        } else {
    //            return $this->statusCode(404, 'Request  unsuccessful');
    //        }
    //
    //    }
    //
    //
    //    /**
    //     * @OA\Get(
    //     * path="/api/category/posts/images{category}",
    //     * operationId="Get all images post on selected categories",
    //     * tags={"Get all images post on selected categories"},
    //     * summary="Get all images post on selected categories",
    //     * description="Get all images post on selected categories here",
    //     *     @OA\RequestBody(
    //     *         @OA\JsonContent(),
    //     *         @OA\MediaType(
    //     *            mediaType="multipart/form-data",
    //     *            @OA\Schema(
    //     *               type="object",
    //     *               required={"id"},
    //     *               @OA\Property(property="id", type="integer",description="id of the category"),
    //     *            ),
    //     *        ),
    //     *    ),
    //     *      @OA\Response(
    //     *          response=201,
    //     *          description="Record Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Record Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=422,
    //     *          description="Unprocessable Entity",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=401, description="Error occured while processing request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=407, description="Not authenticated"),
    //     * )
    //     */
    //
    //    public function postImageOnCategories(Category $category)
    //    {
    //
    //        $data['posts'] = Post::with('medias','likes:id,post_id,user_id','claps:id,post_id,user_id','loves:id,post_id,user_id')
    //            ->withCount('comments','views','impressions','likes','loves','claps')->whereCategory_id($category->id)->whereType('image')->orderBy('created_at','DESC')->paginate(30);
    //
    //        if ($data) {
    //            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
    //        } else {
    //            return $this->statusCode(404, 'Request  unsuccessful');
    //        }
    //
    //    }
    //
    //
    //
    //    /**
    //     * @OA\Get(
    //     * path="/api/category/posts/videos{category}",
    //     * operationId="Get all vidoes post on selected categories",
    //     * tags={"Get all vidoes post on selected categories"},
    //     * summary="Get all vidoes post on selected categories",
    //     * description="Get all vidoes post on selected categories here",
    //     *     @OA\RequestBody(
    //     *         @OA\JsonContent(),
    //     *         @OA\MediaType(
    //     *            mediaType="multipart/form-data",
    //     *            @OA\Schema(
    //     *               type="object",
    //     *               required={"id"},
    //     *               @OA\Property(property="id", type="integer",description="id of the category"),
    //     *            ),
    //     *        ),
    //     *    ),
    //     *      @OA\Response(
    //     *          response=201,
    //     *          description="Record Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=200,
    //     *          description="Record Successfully",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(
    //     *          response=422,
    //     *          description="Unprocessable Entity",
    //     *          @OA\JsonContent()
    //     *       ),
    //     *      @OA\Response(response=400, description="Bad request"),
    //     *      @OA\Response(response=401, description="Error occured while processing request"),
    //     *      @OA\Response(response=403, description="Error in input fields"),
    //     *      @OA\Response(response=404, description="Resource Not Found"),
    //     *      @OA\Response(response=407, description="Not authenticated"),
    //     * )
    //     */
    //
    //    public function postVideoOnCategories(Category $category)
    //    {
    //
    //        $data['posts'] = Post::with('medias','likes:id,post_id,user_id','claps:id,post_id,user_id','loves:id,post_id,user_id')
    //            ->withCount('comments','views','impressions','likes','loves','claps')->whereCategory_id($category->id)->whereType('video')->orderBy('created_at','DESC')->paginate(30);
    //
    //        if ($data) {
    //            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
    //        } else {
    //            return $this->statusCode(404, 'Request  unsuccessful');
    //        }
    //
    //    }
}
