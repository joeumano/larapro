<div v-for="(message, indexMessage) in messages" class="row mb-4" :class="[ {'justify-content-start': message.is_message_by_contact==1}, {'justify-content-end': message.is_message_by_contact==0},{'text-right': (message.is_message_by_contact==0&& message.is_campign_messages==0)} ]">
    <div class="col-md-12" v-if="indexMessage==0 || momentDay(message.created_at)!=momentDay(messages[indexMessage-1].created_at)">
        <div class="col-md-12 text-center">
            <span class="badge text-dark">@{{ momentDay(message.created_at) }}</span>
        </div>
    </div>
    <div class="col-auto" :style="{ 'max-width': ( message.is_campign_messages==1?'440px':'80%'  )    }">
        <div class="card"  :class="[ {'bg-secondary': message.is_message_by_contact==1}, {'bg-success': message.is_message_by_contact==0} ]" >
            <div class="card-body py-2 px-3" >
                <img class="mb-2 inChatImage" v-if="message.header_image" :src="message.header_image" />
                <a v-if="message.header_document" :href="message.header_document" target="_blank" type="button" class="btn btn-secondary btn-lg btn-block">{{ __('Document link')}}</a>
                <a v-if="message.header_location" :href="message.header_location" target="_blank" type="button" class="btn btn-secondary btn-lg btn-block">{{ __('See location')}}</a>

                <video v-if="message.header_video" style="max-width: 300px" controls>
                    <source :src="message.header_video" type="video/mp4">
                </video>

                <audio v-if="message.header_audio" controls>
                    <source :src="message.header_audio" type="audio/mpeg">
                </audio>
                
                <h4 v-if="message.header_text" class="mb-2 text-white">@{{ message.header_text }}</h4>
                
                <p v-html="formatIt(message.value)" class="mb-2" :class="[ {'text-white': message.is_message_by_contact==0} ]" >
                </p>
                <p v-if="message.footer_text" class="text-muted text-xs text-white" style="opacity: 0.8">@{{ message.footer_text }}</p>

                <a :href="button.type=='URL'?button.url:''" target="_blank" v-for="(button, indexButton) in parseJSON(message.buttons)" type="button" class="btn btn-secondary btn-lg btn-block">@{{ button.text }}</a>

                <div   class="box-sizing: content-box; d-flex text-sm opacity-6 align-items-center" :class="[  {'text-white': message.is_message_by_contact==0} ,  {'justify-content-end': message.is_message_by_contact==0},{'text-right': message.is_message_by_contact==0} ]">
                    <i class="ni ni-check-bold text-sm me-1"></i>
                    <small> @{{ momentHM(message.created_at) }}</small>
                    
                </div>
            </div>
        </div>
        <div v-if="message.error" class="alert alert-danger" role="alert">
            <span class="alert-icon"><i class="ni ni-bell-55"></i></span>
            @{{ message.error }}
        </div>
    </div>
</div>