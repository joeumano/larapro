@extends('layouts.app')
@section('admin_title')
    {{__('Dashboard')}}
@endsection

@section('content')
<div class="header pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">

            <h1 class="mb-3 mt--3">{{__('Welcome back')}}, {{ auth()->user()->name}} 👏</h1>
            @if (count($tasks)>0)
                @include('dashboard::tasks')
            @endif
            @foreach (config('global.modulesWithDashboardInfo') as $moduleWithDashboardInfo)
                @include($moduleWithDashboardInfo.'::dashboard')
            @endforeach
        </div>
    </div>
</div>

<div class="container-fluid mt--6">
    @yield('dashboard_content')
    @yield('dashboard_content2')
    @yield('dashboard_content3')
    @yield('dashboard_content4')
</div>
@endsection