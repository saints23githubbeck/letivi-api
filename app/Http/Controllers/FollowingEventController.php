<?php

namespace App\Http\Controllers;


use App\Models\Event;
use App\Models\FollowingEvent;


use Illuminate\Http\Request;

class FollowingEventController extends Controller
{


    // Follow event page

    /**
     * @OA\POST(
     * path="/api/follow/event/{id}",
     * operationId="follow event  by event  id",
     * tags={"follow event  by event   id"},
     * summary="follow event  by event   id",
     * description="follow event  by event   id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the event", type="number"),
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

    public function follow(Event $event)
    {

        try {
            if (request()->user('sanctum')) {

                // check if current user following this business
                $follow_id = Event::whereId($event->id)->count();

                if ($follow_id > 0) {

                    if (auth()->id() === $event->user_id) {

                        return $this->statusCode(422, 'You cannot follow Your own event page');
                    }

                  FollowingEvent::create([
                        "user_id" => auth()->id(),
                        "event_id" => $event->id,
                    ]);
                }

                $data['followingCount'] = FollowingEvent::whereEvent_id($event->id)->count();
                return $this->statusCode(200, 'You are following this event',['data'=>$data]);

            } else {

                return $this->statusCode(407, 'You must login to perform this act');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    //Unfollow event page

    /**
     * @OA\DELETE(
     * path="/api/unfollow/event/{id}",
     * operationId="Unfollow event  by event  id",
     * tags={"Unfollow event  by event   id"},
     * summary="Unfollow event  by event   id",
     * description="Unfollow event  by event   id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the event", type="number"),
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
    public function unfollow(Event $event)
    {

        try {


            if (request()->user('sanctum')) {

                // check if current user following this business
                $follow_id = FollowingEvent::whereEvent_id($event->id)->whereUser_id(auth()->id())->count();

                if ($follow_id > 0) {
                    $event_follow = FollowingEvent::whereEvent_id($event->id)->whereUser_id(auth()->id())->first();
                    $deleteFollow = FollowingEvent::find($event_follow->id);
                    $deleteFollow->delete();
                }
                $data['followingCount'] = FollowingEvent::whereEvent_id($event->id)->count();
                return $this->statusCode(200, 'You are no longer following this event',['data'=>$data]);
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
