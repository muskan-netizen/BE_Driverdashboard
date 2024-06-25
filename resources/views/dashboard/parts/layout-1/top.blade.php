@extends('layouts.vertical', ['title' => 'Dashboard','demo'=>'creative'])
@section('css')
    {{-- <!-- Plugins css -->
    <link href="{{ asset('demo/css/style.css') }}" rel="stylesheet" type="text/css" /> --}}
@endsection
@php
    use Carbon\Carbon;
    $color = ['one','two','three','four','five','six','seven','eight'];
    $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
@endphp