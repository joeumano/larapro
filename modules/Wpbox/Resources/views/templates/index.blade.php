@extends('general.index', $setup)
@section('thead')
    <th>{{ __('Name') }}</th>
    <th>{{ __('Status') }}</th>
    <th>{{ __('Category') }}</th>
    <th>{{ __('Language') }}</th>
@endsection
@section('tbody')
    @foreach ($setup['items'] as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>{{ __($item->status) }}</td>
            <td>{{ __($item->category) }}</td>
            <td>{{ __($item->language) }}</td>
            
        </tr> 
    @endforeach
@endsection