<?php

// namespace App\Http\Controllers;

// use App\Models\Transaction;
// use App\Models\User;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;
// use Srmklive\PayPal\Services\PayPal as PayPalClient;

// class PayPalControllerCopy extends Controller
// {
//     /**
//      * create transaction.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function createTransaction()
//     {
//         return view('paypal.transaction');
//     }

//     /**
//      * process transaction.
//      *
//      * @return \Illuminate\Http\Response
//      */

//     /**
//      * @OA\POST(
//      * path="/api/pay",
//      * operationId="Make Paypal Payment here",
//      * tags={"Paypal Payment API"},
//      * summary="User Make Paypal Payment here",
//      * description="User Make Paypal Payment here here.",
//      *     @OA\RequestBody(
//      *         @OA\JsonContent(),
//      *         @OA\MediaType(
//      *            mediaType="multipart/form-data",
//      *            @OA\Schema(
//      *               type="object",
//      *               required={"amount"},
//      *               @OA\Property(property="amount", type="float"),
//      *            ),
//      *        ),
//      *    ),
//      *      @OA\Response(
//      *          response=201,
//      *          description="Transaction successful",
//      *          @OA\JsonContent()
//      *       ),
//      *      @OA\Response(
//      *          response=200,
//      *          description="Transaction successful",
//      *          @OA\JsonContent()
//      *       ),
//      *      @OA\Response(response=400, description="Bad request"),
//      *      @OA\Response(response=403, description="Error in input fields"),
//      *      @OA\Response(response=407, description="Please Login First"),
//      *      @OA\Response(response=422, description="Error occured while processing request"),
//      *      @OA\Response(response=499, description="Transaction cancelled/failed"),
//      * )
//      */
//     public function processTransaction(Request $request)
//     {
//         try {
//             // VALIDATE INPUT FIELDS
//             $validate = Validator::make(
//                 $request->all(),
//                 [
//                     'amount' => ['required', 'numeric', 'min:1'],
//                 ]
//             );

//             if ($validate->fails()) {
//                 return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
//             }
//             $user = User::find(request()->user('sanctum')->id);

//             // create a new transaction
//             $trs = new Transaction();
//             $trs->name = $user->first_name . ' ' . $user->last_name;
//             $trs->email = $user->email;
//             $trs->amt = $request->amount;
//             $trs->save();

//             $provider = new PayPalClient;
//             $provider->setApiCredentials(config('paypal'));
//             $paypalToken = $provider->getAccessToken();
//             $response = $provider->createOrder([
//                 "intent" => "CAPTURE",
//                 "application_context" => [
//                     "return_url" => url("/successTransaction/" . auth()->id()),
//                     "cancel_url" => url("/cancelTransaction/" . auth()->id())
//                 ],
//                 "purchase_units" => [
//                     0 => [
//                         "amount" => [
//                             "currency_code" => "USD",
//                             "value" => $request->amount
//                         ]
//                     ]
//                 ]
//             ]);

//             // return $response;
//             $trs = Transaction::whereEmail(auth()->user()->email)->latest()->first();
//             if (isset($response['id']) && $response['id'] != null) {
//                 // redirect to approve href
//                 foreach ($response['links'] as $links) {
//                     if ($links['rel'] == 'approve') {
//                         return $this->statusCode(200, "Approve payment...", ['redirect' => $links['href']]);
//                     }
//                 }
//             } else {
//                 $trs->status = 'failed';
//                 $trs->bio_generation_status = 'failed';
//                 $trs->save();
//                 return $this->statusCode(422, $response['message'] ?? 'Something went wrongs.');
//             }
//         } catch (\Throwable $e) {
//             return $this->statusCode(422, $e->getMessage());
//             return $this->statusCode(422, 'Error occured while procssing your request');
//         }
//     }

//     /**
//      * success transaction.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function successTransaction(Request $request)
//     {
//         try {
//             $user = User::find($request->user_id);
//             $trs = Transaction::whereEmail($user->email)->latest()->first();
//             if ($trs) {
//                 $provider = new PayPalClient;
//                 $provider->setApiCredentials(config('paypal'));
//                 $provider->getAccessToken();
//                 $response = $provider->capturePaymentOrder($request['token']);
//                 if (isset($response['status']) && $response['status'] == 'COMPLETED') {
//                     $trs->status = 'success';
//                     $trs->save();
//                     return $this->statusCode(200, 'Transaction complete.', ['transaction' => $trs]);
//                 } else {
//                     $trs->status = 'failed';
//                     $trs->bio_generation_status = 'failed';
//                     $trs->save();
//                     return $this->statusCode(422, 'Something went wrong.');
//                 }
//             }
//         } catch (\Throwable $e) {
//             return $this->statusCode(422, $e->getMessage());
//         }
//     }

//     /**
//      * cancel transaction.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function cancelTransaction(Request $request)
//     {
//         try {
//             $user = User::find($request->user_id);
//             // dd($user);
//             $trs = Transaction::whereEmail($user->email)->latest()->first();
//             if ($trs) {
//                 $trs->status = 'failed';
//                 $trs->bio_generation_status = 'failed';
//                 $trs->save();
//                 return $this->statusCode(499, 'You have cancelled the transaction.');
//             }
//             return $this->statusCode(404, "No user found for the transaction");
//         } catch (\Throwable $e) {
//             return $this->statusCode(422, $e->getMessage());
//         }
//     }

//     /**
//      * @OA\GET(
//      * path="/api/pay/check",
//      * operationId="Check user payment status",
//      * tags={"Check user payment status"},
//      * summary="Check user payment status",
//      * description="Check user payment status",
//      *      @OA\Response(
//      *          response=200,
//      *          description="Checking transaction status",
//      *          @OA\JsonContent()
//      *       ),
//      *      @OA\Response(
//      *          response=422,
//      *          description="Unprocessable Entity",
//      *          @OA\JsonContent()
//      *       ),
//      *      @OA\Response(response=400, description="Bad request"),
//      *      @OA\Response(response=404, description="Resource Not Found"),
//      * )
//      */
//     public function checkPaymentStatus()
//     {
//         try {
//             $chk = Transaction::checkPaymentStatus()->count();
//             if ($chk > 0) {
//                 $trs = Transaction::checkPaymentStatus()->first();
//                 return $this->statusCode(200, 'Checking transaction status', ['status' => $trs->status]);
//             } else {
//                 return $this->statusCode(404, 'User has not made any payment yet');
//             }
//         } catch (\Throwable $e) {
//             return $this->statusCode(422, $e->getMessage());
//         }
//     }
// }
