@extends('layouts.public')

@section('content')
    @include('partials.page_header')
    @php \Actions::do_action('pre_content',$item, $home??null) @endphp
    {!! $item->rendered !!}
@stop
