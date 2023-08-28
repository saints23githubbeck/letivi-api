<?php

namespace App\Http\Controllers;


use App\Models\Event;
use App\Models\Post;
use App\Models\WorkspaceCollaborator;
use App\Models\WorkspaceSponsor;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class EventController extends Controller
{

    private $logo_path;
    private $media;

    public function __construct()
    {
        $this->logo_path = 'eventlogo/';
        $this->media = new MediaController();
    }
    //todo search user
    //    user::select('*',db:raw("CONCAT(users.colunm,' ',users.column) as full_name)where

    //Create event page

    /**
     * @OA\Post(
     * path="/api/events",
     * operationId="Create Event",
     * tags={"Create Event "},
     * summary="Create Event",
     * description="create Event here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name","specialize","location","description","logo","country"},
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="type", type="string"),
     *               @OA\Property(property="industry_id", type="number"),
     *               @OA\Property(property="other_industry", type="string"),
     *               @OA\Property(property="specialize", type="string"),
     *               @OA\Property(property="logo", type="file"),
     *               @OA\Property(property="country", type="string"),
     *               @OA\Property(property="location", type="string"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="collaborator_id", type="number"),
     *               @OA\Property(property="sponsor_id", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Event Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Event Created  Successfully",
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
                'logo' => 'nullable|file',
                'country' => 'required|string',
                'location' => 'required|string',
                'type' => 'nullable|string',
                'collaborator_id' => 'nullable',
                'sponsor_id' => 'nullable',
            ]);
        } catch (\Throwable $e) {
            return $this->statusCode(403, $e->getMessage());
        }

        try {

            return DB::transaction(function () use ($request) {

                if ($request->hasFile('logo')){
                    $response = $this->media->checkFileFormat($request, 'logo', 'image');

                        if (!$response['hasMatch']) {
                            return $this->statusCode(400, $response['msg']);
                        }

                    $this->logo_path .= auth()->id();
                    $path = $this->media->save($this->logo_path, $request->file('logo'));

                }

                $event = Event::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'location' => $request->location,
                    'industry_id' => $request->industry_id??null,
                    'other_industry' => $request->other_industry??null,
                    'specialize' => $request->specialize,
                    'type' => $request->type,
                    'country' => $request->country,
                    'user_id' => auth()->id(),
                ]);

                $event->eventProfile()->create([
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

                    $event->eventMembers()->attach($member);
                    //
                }

                $data['event'] = Event::with('eventProfile')->whereId($event->id)->first();

                if ($data) {
                    return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                } else {
                    $this->media->destroyFile($path);
                    return $this->statusCode(400, 'Request  unsuccessful');
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
     * path="/api/events/{id}",
     * operationId="Update Event by id",
     * tags={"Update Event by id"},
     * summary="Update Event by id",
     * description="Update Event by id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id","name","specialize","location","description","logo","country"},
     *               @OA\Property(property="id", description="id of the event",type="number"),
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="type", type="string"),
     *               @OA\Property(property="industry_id", type="number"),
     *               @OA\Property(property="other_industry", type="string"),
     *               @OA\Property(property="specialize", type="string"),
     *               @OA\Property(property="logo", type="file"),
     *               @OA\Property(property="country", type="string"),
     *               @OA\Property(property="location", type="string"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="collaborator_id", type="number"),
     *               @OA\Property(property="sponsor_id", type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Event Updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Event Updated  Successfully",
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

    public function update(Request $request, Event $event)
    {
        $path = null;
        try {
            $event_id = Event::whereId($event->id)->count();

            if ($event_id > 0) {
                return  DB::transaction(function () use ($request, $event, $path) {

                    $event =  Event::find($event->id);

                    if ($request->hasFile('logo')) {
                        $response = $this->media->checkFileFormat($request, 'logo', 'image');

                        if (!$response['hasMatch']) {
                            return $this->statusCode(400, $response['msg']);
                        }

                        $this->logo_path .= auth()->id();
                        $path = $this->media->save($this->logo_path, $request->file('logo'));
                    }

                    $event->update([
                        'name' => $request->name ?? $event->name,
                        'description' => $request->description ?? $event->description,
                        'industry_id' => $request->industry_id ?? $event->industry_id,
                        'other_industry' => $request->other_industry ?? $event->other_industry,
                        'specialize' => $request->specialize ?? $event->specialize,
                        'location' => $request->location ?? $event->location,
                        'type' => $request->type ?? $event->type,
                        'country' => $request->country ?? $event->country,
                        'user_id' => $event->user_id,
                    ]);

                    $event->eventProfile()->update([
                        'logo' => $path ?? $event->eventProfile->logo,
                        'linkedin' => $request->linkedin ?? $event->eventProfile->linkedin,
                        'twitter' => $request->twitter ?? $event->eventProfile->twitter,
                        'facebook' => $request->facebook ?? $event->eventProfile->facebook,
                        'instagram' => $request->instagram ?? $event->eventProfile->youtube,
                        'website' => $request->website ?? $event->eventProfile->website,
                    ]);

                    if ($request->input('collaborator_id')) {
                        $collaborator = [];
                        if (gettype($request->collaborator_id) == 'string') {
                            $myString = trim($request->collaborator_id, '[]');
                            $member = explode(',', $myString);
                        } else {
                            $member = $request->collaborator_id;
                        }

                        $event->eventMembers()->attach($member);

                    }

                    $data['event'] = Event::with('eventProfile')->whereId($event->id)->first();

                    if ($data) {
                        // delete old logo
                        $path != null ? $this->media->destroyFile($event->eventProfile->logo) : '';
                        return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                    } else {
                        return $this->statusCode(400, 'Request  unsuccessful');
                    }
                });
            }
        } catch (\Throwable $th) {
            return $this->statusCode(500, $th->getMessage());
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }

    // Delete event page

    /**
     * @OA\DELETE(
     * path="/api/events/{id}",
     * operationId="Delete Event by id",
     * tags={"Delete Event by id"},
     * summary="Delete Event by id",
     * description="Delete Event by id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the event",type="number"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Event Deleted  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Event Deleted  Successfully",
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
    public function destroy(Event $event)
    {
        try {
            $event_id = Event::whereId($event->id)->count();
            if (auth()->id() != $event->user_id){
                return $this->statusCode(403, "This workspace is not Yours");

            }
            if ($event_id > 0) {
             return DB::transaction(function () use ($event) {
                    $profile = $event->eventProfile()->first();
                    $event->delete();
                    $data['event'] = $event;
                    if ($data) {
                        $this->media->destroyFile($profile->logo);
                        return $this->statusCode(200, 'Request  successful', ['data' => $data]);
                    } else {
                        return $this->statusCode(400, 'Request  unsuccessful', ['data' => $data]);
                    }
                });
            }
        } catch (\Throwable $th) {
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }


    /**
     * @OA\Get(
     * path="/api/user/my/events/workspaces",
     * operationId="Current login user event workspace",
     * tags={"Current login user event workspace"},
     * summary="Current login user event workspace",
     * description="Current login user event workspace",
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

    public function myEventPage()
    {

        $chk =  Event::with('eventProfile')->whereUser_id(auth()->id())->count();
        if ($chk>0){

            $data['event'] = Event::with(
                'eventMembers:id,first_name,last_name',
                'eventMembers.profile',
                'eventMembers.profession',
                'eventFollowers.user:id,first_name,last_name',
                'eventFollowers.user.profile:id,user_id,picture',
                'albums','eventProfile','industry')
                ->withCount('eventMembers','eventSponsors', 'eventFollowers','albums','posts','myImages','myVideos')
                ->whereUser_id(auth()->id())->get();
        }
        $data = $data['event'];
        $data['my_member_event_workspaces'] = Event::with(
            'eventProfile',
//            'businessMembers:id,first_name,last_name',
//            'businessMembers.profile',
//            'businessMembers.profession',
            //            'businessSponsors:id,first_name,last_name',
            //            'businessSponsors.profile:id,user_id,picture',
//            'businessFollowers.user:id,first_name,last_name',
//            'businessFollowers.user.profile:id,user_id,picture',
            'industry',
            'albums'
        )->whereHas('eventMembers', function ($query) {
            $query->where('user_id','=',auth()->id());
        })->withCount('eventMembers', 'eventFollowers', 'albums', 'posts', 'myImages', 'myVideos')->orderByDesc('created_at')->get();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }


    }

    /**
     * @OA\Get(
     * path="/api/view/events/workspaces/{id}",
     * operationId="View event workspace",
     * tags={"View event workspace"},
     * summary="View event workspace",
     * description="View event workspace",
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

    public function eventPageView(Event $event)
    {

        $chk =  Event::with('eventProfile')->whereId($event->id)->count();
        if ($chk>0){

            $data['event'] = Event::with('posts','posts.medias',
                'eventMembers:id,first_name,last_name',
                'eventMembers.profile',
                'eventMembers.profession',
                'eventFollowers.user:id,first_name,last_name',
                'eventFollowers.user.profile:id,user_id,picture',
                'albums','eventProfile','industry')
                ->withCount('eventMembers','eventSponsors', 'eventFollowers','albums','posts','myImages','myVideos')->whereId($event->id)->get();
        }
        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }


    }


    /**
     * @OA\GET(
     * path="/api/view/events/workspaces/images/{id}",
     * operationId="View All images posts in event page",
     * tags={" View All images posts in event page"},
     * summary=" view All images posts in event page",
     * description="view All images posts in event page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the event"),
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

    public function eventPageViewImage(Event $event)
    {


        $data['posts'] = Post::with('medias', 'likes:id,post_id,user_id',
            'claps:id,post_id,user_id', 'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count','event','event.eventProfile','album')
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereEvent_id($event->id)->whereType('image')->orderByDesc('created_at')->get();

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }

    /**
     * @OA\GET(
     * path="/api/view/events/workspaces/videos{id}",
     * operationId="View All videos posts in event page",
     * tags={" View All videos posts in event page"},
     * summary=" view All videos posts in event page",
     * description="view All videos posts in event page here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the event"),
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

    public function eventPageViewVideos(Event $event)
    {


        $data['posts'] = Post::with('medias', 'likes:id,post_id,user_id',
            'claps:id,post_id,user_id', 'loves:id,post_id,user_id',
            'postCount:id,post_id,downloads_count','event','event.eventProfile','album')
            ->withCount('comments', 'views', 'impressions', 'likes', 'loves', 'claps')
            ->whereEvent_id($event->id)->whereType('video')->orderByDesc('created_at')->paginate(30);

        if ($data) {
            return $this->statusCode(200, 'Request  successful', ['data' => $data]);
        } else {
            return $this->statusCode(404, 'Request  unsuccessful', ['data' => $data]);
        }
    }
}
