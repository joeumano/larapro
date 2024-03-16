<div class="col-lg-4 col-6 my-auto ps-0 align-right" style="text-align: right">
    
    <b-button v-if="!mobileChat" class="btn btn-icon btn-outline" v-b-modal.modal-templates style="background-color: buttonface; !important; border-color: buttonface; box-shadow:0px 0px 0px !important;">
        
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2dce89" class="w-6 h-6" style="width: 20px; height:20px">
            <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0118 9.375v9.375a3 3 0 003-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 00-.673-.05A3 3 0 0015 1.5h-1.5a3 3 0 00-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6zM13.5 3A1.5 1.5 0 0012 4.5h4.5A1.5 1.5 0 0015 3h-1.5z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 013 20.625V9.375zM6 12a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V12zm2.25 0a.75.75 0 01.75-.75h3.75a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM6 15a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V15zm2.25 0a.75.75 0 01.75-.75h3.75a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM6 18a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V18zm2.25 0a.75.75 0 01.75-.75h3.75a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
          </svg>
          
          
    </b-button>



    <b-modal id="modal-templates" scrollable hide-backdrop content-class="shadow" title="{{__('Send template message')}}">
        <div class="table-responsive">
            <div>
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">🔍</span>
                        </div>
                        <input type="text" v-model="filterTemplates" class="form-control" placeholder="{{ __('Search') }}" aria-label="seeach" aria-describedby="basic-addon1">
                    </div>
                </div>
                <table class="table align-items-center">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col" class="sort" data-sort="name">{{ __('Template')}}</th>
                            
                        </tr>
                    </thead>
                    <tbody class="list">
                        <tr  v-for="(template) in filteredTemplates">
                            <td class="">
                                <a :href="'/campaigns/create?template_id='+template.id+'&send_now=on&contact_id='+activeChat.id" ><span class="name mb-0 text-sm">@{{ template.name }}</span></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </b-modal>

    <b-button class="btn btn-icon btn-outline" type="button" v-b-modal.modal-replies style="background-color: buttonface; !important; border-color: buttonface; box-shadow:0px 0px 0px !important;">
        <span class="btn-inner--icon"><i class="ni ni-curved-next" style="color:#2dce89 "></i></span>
    </b-button>

    <b-modal id="modal-replies" scrollable hide-backdrop content-class="shadow" title="{{__('Quick replies')}}">
        <div class="table-responsive">
            <div>
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">🔍</span>
                        </div>
                        <input type="text" v-model="filterText" class="form-control" placeholder="{{ __('Search') }}" aria-label="seeach" aria-describedby="basic-addon1">
                    </div>
                </div>
                <table class="table align-items-center">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col" class="sort" data-sort="name">{{ __('Reply')}}</th>
                            <th scope="col" class="sort" data-sort="name">
                                <div class="d-flex justify-content-end">
                                    <b-button pill class="btn btn-default btn-sm" href="{{ route('replies.index',['type'=>'qr'])}}">
                                        <b>{{ __('Manage Quick replies') }}</b>
                                    </b-button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        <tr v-for="(reply, index) in filteredReplies">
                            <td colspan="2" class="">
                                <span @click="setMessage(reply.text)" class="name mb-0 text-sm">@{{ reply.name }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </b-modal>

   
   




</div>