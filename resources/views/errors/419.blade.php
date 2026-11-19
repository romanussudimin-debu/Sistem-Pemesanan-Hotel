{{-- resources/views/errors/419.blade.php — sesi/CSRF kedaluwarsa --}}
@extends('errors.layout')
@section('code', '419')
@section('title', __('Session expired'))
@section('message', __('Your session has expired. Please reload the page and try again.'))
@section('action')<a href="javascript:location.reload()">{{ __('Reload') }}</a>@endsection
