@hasrole('owner')




<div class="mt-4">
    <a href="{{ route('campaigns.create') }}" type="button" class="btn btn-outline-primary ">📢 {{ __('Send campaign')}}</a>
    <a href="{{ route('contacts.create') }}" type="button" class="btn btn-outline-primary ">🪪 {{ __('Create contact')}}</a>
    <a href="{{ route('replies.create') }}" type="button" class="btn btn-outline-primary ">📤 {{ __('Create reply bot')}}</a>
    <a target="_blank" href="https://business.facebook.com/wa/manage/message-templates/" type="button" class="btn btn-outline-primary ">🔖 {{ __('Create template')}}</a>
</div>

    @include('partials.infoboxes.advanced',['collection'=>$wpbox])

    @if(config('settings.is_demo',false))
    <div class="modal" id="modal-notification" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true" >
        <div class="modal-dialog modal-danger modal-dialog-centered modal-" role="document">
            <div class="modal-content bg-gradient-danger">
                
                <div class="modal-header">
                    <h6 class="modal-title" id="modal-title-notification"></h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    
                    <div class="py-3 text-center">
                        <i class="ni ni-bell-55 ni-3x"></i>
                        <h4 class="heading mt-4">You should read this!</h4>
                        <p>This is demo of the project. But we want to showcase all the capabilities. So we have include a test phone number for testing purposes so you don't have to. <br><b>So please don't miss use the demo!</b></p>
                        <p>The demo data refreshes every hour. So you can add your phone number as a contact, and send / receive a message. Your number will be deleted in some minutes.</p> 
                        <p>Alternatively, feel free to create your own account in this demo, and test the full process, from creating/connecting to facebook api and own whatsapp number. The data is removed every hour.</p>
                        <p>In this demo, we have included the "AI Chats" plugin which is paid plugin, that needs to be purchased separatly - Optional.</p>
                    </div>
                    
                </div>
                
                <div class="modal-footer">
                  
                    <button type="button" class="btn btn-link text-white ml-auto" data-dismiss="modal">OK, I Agree</button>
                </div>
                
            </div>
        </div>
    </div>
    <script>
        window.onload = function () {
            $('#modal-notification').modal('show');
        }
       
    </script>
    @endif
@endhasrole