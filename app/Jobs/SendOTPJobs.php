<?php

namespace App\Jobs;

use App\Http\Controllers\UserAuth\SignupController;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SendOTPJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $user;
    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if OTP is null or not
        if ($this->user->otp === null) {
            $signCo = new SignupController();
            $otp = Hash::make($signCo->randomString());
            $this->user->sendOTP($otp);
        }

        Mail::to($this->user->email)->send(new OtpMail($this->user));
    }
}
