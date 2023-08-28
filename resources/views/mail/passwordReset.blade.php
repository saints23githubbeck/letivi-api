@extends('layout.structure')
@section('content')
    <div
        style="font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:20px;font-weight:bold;line-height:24px;text-align:center;color:#212b35;">
        Password Reset Request
    </div>
    <br>
    <div
        style="font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:15px;font-weight:400;line-height:24px;text-align:center;">
        Ignore this message if you did not make the request
    </div>
    <br>
    <br>
    <a href="{{ url('/password/reset?otp=' . $user->otp . '&token=' . $user->token) }}"
        style="display:inline-block;width:150px;background:#FFCD3C;color:#ffffff;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:15px;font-weight:bold;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:10px;"
        target="_blank"> {{ __('RESET PASSWORD') }} </a>
    <br>
@endsection
@section('challenges')
    <a href="{{ url('/password/reset?otp=' . $user->otp . '&token=' . $user->token) }}"
        style="color:black; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:15px;font-weight:400;text-decoration:none;text-transform:none;"
        target="_blank"> Experiencing challenges click
        {{ url('/password/reset?otp=' . $user->otp . '&token=' . $user->token) }} </a>
@endsection
{{--  --}}
