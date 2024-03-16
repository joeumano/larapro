@extends('general.index', $setup)

@section('cardbody')
    @foreach ($setup['items'] as $item)
        <a href="{{ route('campaigns.show',$item->id)}}"><h3 class="mb-0">{{__('Camapign')}}: {{ $item->name }}</h3><br />
        @include('wpbox::campaigns.infoboxes',$item)
        <hr /> </a>
    @endforeach
    @if (count($setup['items'])==0)
        <div style="display: flex; justify-content: center; width:100%;">
            <div style="text-align: center">
                <p style="text-align: center;"> 
                    {{ __('There are no campaigns, send your first one!')}}
                   </p>
               <p style="text-align: center;"> 
                <img style="max-height: 200px" src="{{  asset('uploads/default/wpbox/inbox.png') }}" />
               </p>
               
            </div>
           
        </div>
    @endif
@endsection