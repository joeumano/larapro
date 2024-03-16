@extends('general.index', $setup)
@section('contenttop')
<div class="card-body">
    <div class="row">
        <div class="col-12">

            <!-- Groups modal -->
            <div class="modal fade" id="move-to-group-modal" tabindex="-1" role="dialog" aria-labelledby="moveToGroupModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="moveToGroupModalLabel">{{ __('Add to group') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            @foreach ($setup['groups'] as $group)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="group" id="group-{{ $group->id }}" value="{{ $group->id }}">
                                    <label class="form-check-label" for="group-{{ $group->id }}">
                                        {{ $group->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                            <button type="button" class="btn btn-primary" id="move-to-group-confirm">{{ __('Add') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="remove-from-group-modal" tabindex="-1" role="dialog" aria-labelledby="removeFromGroupModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="removeFromGroupModalLabel">{{ __('Remove from group') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            @foreach ($setup['groups'] as $group)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="groupremove" id="group-{{ $group->id }}" value="{{ $group->id }}">
                                    <label class="form-check-label" for="group-{{ $group->id }}">
                                        {{ $group->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                            <button type="button" class="btn btn-primary" id="remove-from-group-confirm">{{ __('Remove') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk action button, initially hidden -->
            <div class="btn-group" id="bulk-action-button" style="display: none;">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="ni ni-archive-2"></i>
                    {{ __('Bulk action') }}
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" id="move-to-group">{{ __('Add to group') }}</a>
                    <a class="dropdown-item" href="#" id="remove-from-group">{{ __('Remove from group') }}</a>
                    <a class="dropdown-item" href="#" id="delete-selected">{{ __('Delete selected') }}</a>
                </div>
            </div>
            <div class="col-12 mt-3">
                <span id="selected-count"></span>
            </div>
            
        </div>
    </div>
</div>
    
@endsection
@section('thead')
    <th><input type="checkbox" id="select-all"></th>
    <th>{{ __('Name') }}</th>
    <th>{{ __('Phone') }}</th>
    <th>{{ __('Create at') }}</th>
    <th>{{ __('Groups') }}</th>
    <th>{{ __('crud.actions') }}</th>
@endsection
@section('tbody')
    @foreach ($setup['items'] as $item)
        <tr>
            <td><input type="checkbox" class="select-item" value="{{ $item->id }}"></td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->phone }}</td>
            <td>{{ $item->created_at->format('Y-m-d') }}</td>
            <td>
                @foreach ($item->groups as $group)
                    <a href="/contacts/contacts?group={{ $group->id }}" class="badge badge-primary">{{ $group->name }}</a>
                @endforeach
            </td>
            <td>
                <!-- CHAT -->
                <a href="{{ route('campaigns.create',['contact_id'=>$item->id]) }}" class="btn btn-outline-success btn-sm">
                    <span class="btn-inner--icon"><i class="ni ni-chat-round"></i></span>
                    <span class="btn-inner--text">{{ __('Start chat')}}</span>
                </a>

                <!-- EDIT -->
                <a href="{{ route('contacts.edit',['contact'=>$item->id]) }}" class="btn btn-primary btn-sm">
                    <i class="ni ni-ruler-pencil"></i>
                </a>

                <!-- DELETE -->
                <a href="{{ route('contacts.delete',['contact'=>$item->id]) }}" class="btn btn-danger btn-sm">
                    <i class="ni ni ni-fat-remove"></i>
                </a>
            </td>
        </tr> 
    @endforeach
@endsection
@section('js')
    @include('contacts::contacts.scripts')
@endsection