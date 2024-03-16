@extends('layouts.app', ['title' => __('Chat'), 'hideActions'=>true ])
@section('content')
<div class="col-12">
    @include('partials.flash')
</div>
<div class="container-fluid mt-4" id="chatList">  
    <div class="row">
        <div v-if="conversationsShown" class="col-lg-4 col-md-5 col-12">
            @include('wpbox::chat.conversations')
        </div>
        <div v-cloak id="chatAndTools" class="col-lg-8 col-md-7 col-12" v-if="activeChat&&activeChat.name" style="padding-left:0px !important; padding-right:4px !important;" >
            @include('wpbox::chat.chat')
            @include('wpbox::chat.tools')
        </div>
    </div>
</div>
@include('wpbox::chat.scripts')
@include('wpbox::chat.onesignal')
<script src="{{ asset('vendor/emoji/emojiPicker.js') }}">
@endsection
