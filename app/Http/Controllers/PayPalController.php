<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    /**
     * create transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function createTransaction()
    {
        return view('paypal.transaction');
    }

    /**
     * @OA\POST(
     * path="/api/pay",
     * operationId="Make Paypal Payment here",
     * tags={"Paypal Payment API"},
     * summary="User Make Paypal Payment here",
     * description="User Make Paypal Payment here here.",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"amount"},
     *               @OA\Property(property="amount", type="float"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Transaction successful",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Transaction successful",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=407, description="Please Login First"),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     *      @OA\Response(response=499, description="Transaction cancelled/failed"),
     * )
     */
    public function processTransaction(Request $request)
    {
        // VALIDATE INPUT FIELDS
        $validate = Validator::make(
            $request->all(),
            [
                'amount' => ['required', 'numeric', 'min:1']
            ]
        );

        if ($validate->fails()) {
            return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
        }

        // cache user id
        $user = User::find(auth()->id());
        if ($user) {
            Cache::put('clientId', auth()->id());
            Cache::put('clientEmail', $user->email);
            // create a new transaction
            $trs = new Transaction();
            $trs->name = $user->first_name . ' ' . $user->last_name;
            $trs->email = $user->email;
            $trs->amt = $request->amount;
            $trs->save();
        } else {
            return $this->statusCode(404, "No user found");
        }

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('successTransaction'),
                "cancel_url" => route('cancelTransaction'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->amount
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {

            // redirect to approve href
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }
            self::resetCache();
            return redirect()
                ->route('createTransaction')
                ->with('error', 'Something went wrongs.');
        } else {
            self::resetCache();
            return redirect()
                ->route('createTransaction')
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }
    }

    /**
     * success transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function successTransaction(Request $request)
    {
        $trs = Transaction::whereEmail(Cache::get('clientEmail'))->latest()->first();
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);
        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            $trs->status = 'success';
            $trs->save();
            self::resetCache();
            return redirect()
                ->route('createTransaction')
                ->with('success', 'Transaction complete.');
        } else {
            $trs->status = 'failed';
            $trs->bio_generation_status = 'failed';
            $trs->save();
            self::resetCache();
            return redirect()
                ->route('createTransaction')
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }
    }

    /**
     * cancel transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function cancelTransaction(Request $request)
    {
        $trs = Transaction::whereEmail(Cache::get('clientEmail'))->latest()->first();
        $trs->status = 'failed';
        $trs->bio_generation_status = 'failed';
        $trs->save();
        self::resetCache();
        return redirect()
            ->route('createTransaction')
            ->with('error', $response['message'] ?? 'You have canceled the transaction.');
    }

    /**
     * @OA\GET(
     * path="/api/pay/check",
     * operationId="Check user payment status",
     * tags={"Check user payment status"},
     * summary="Check user payment status",
     * description="Check user payment status",
     *      @OA\Response(
     *          response=200,
     *          description="Checking transaction status",
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
    public function checkPaymentStatus()
    {
        try {
            $chk = Transaction::checkPaymentStatus()->count();
            if ($chk > 0) {
                $trs = Transaction::checkPaymentStatus()->first();
                return $this->statusCode(200, 'Checking transaction status', ['status' => $trs->status]);
            } else {
                return $this->statusCode(404, 'User has not made any payment yet');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

    private static function resetCache()
    {
        Cache::has('clientId') ? Cache::forget('clientId') : '';
        Cache::has('clientEmail') ? Cache::forget('clientEmail') : '';
    }
}
