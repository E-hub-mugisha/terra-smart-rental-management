@extends('layouts.app')

@section('title', 'My properties')
@section('page_heading', 'My properties')
@section('page_subtitle', 'Properties you market for Terra. Share the public page with prospective tenants.')

@section('content')
@include('agent._properties', ['properties' => $properties])
@endsection
