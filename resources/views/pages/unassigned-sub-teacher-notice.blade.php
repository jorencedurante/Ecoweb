@extends('layouts.admin')

@section('title', 'EcoCollect - ' . $module . ' - Access Restricted')
@section('page-title', $module)
@section('page-subtitle', 'Access restricted')

@section('content')
    {{-- No module data is loaded here. The shared notice reuses the dashboard's
         "No Teacher Assignment Yet" design and never exposes restricted records. --}}
    @include('partials.unassigned-sub-teacher-notice')
@endsection
