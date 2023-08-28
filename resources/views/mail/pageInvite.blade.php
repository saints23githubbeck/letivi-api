@extends('layout.structure')
@section('content')
    <a href="{{ url('/api/invite/page?token=' . $token . '&recepient=' . $email . '&pg_src=' . $src . '&pg=' . $id) }}"
        style="display:inline-block;width:150px;background:#FFCD3C;color:#ffffff;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:15px;font-weight:bold;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:10px;"
        target="_blank"> {{ __('ACCEPT INVITE') }} </a>
    <br>
@endsection
@section('challenges')
    <a href="{{ url('/api/invite/page?token=' . $token . '&recepient=' . $email . '&pg_src=' . $src . '&pg=' . $id) }}"
        style="color:black; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:15px;font-weight:400;text-decoration:none;text-transform:none;"
        target="_blank"> Experiencing challenges click
        {{ url('/api/invite/page?token=' . $token . '&recepient=' . $email . '&pg_src=' . $src . '&pg=' . $id) }} </a>
@endsection
{{--  --}}
