<div class="card shadow mb-5 mb-lg-0 max-height-vh-70 overflow-auto overflow-x-hidden" style="min-height: 20vh!important; max-height: 85vh!important;">
    <div class="card-header p-3">
        <h3 class="mb-0">{{__('Conversations')}}</h3>
    </div>

    <!--  @click.prevent="newTab" -->   
    <div class="card-header" style="padding-top: 0.7rem; padding-bottom: 0rem; background-color: rgb(243,244,246);">

        <div>
            <b-tabs pills justified content-class="mt-3">
              
              <b-tab @click.prevent="allMessages" active title-link-class="small-tab"  title-link-style="padding: 0.35rem 0.5rem;">
                <template #title >
                    {{__('All')}} <span class="badge badge-primary">@{{allCount}}</span>
                   </template>
              </b-tab>
              <b-tab title-link-class="small-tab" @click.prevent="mineMessages">
                <template #title >
                    {{__('Mine')}} <span class="badge badge-primary">@{{mineCount}}</span>
                  </template>
               </b-tab>
              <b-tab @click.prevent="newMessages" title-link-class="small-tab">
                <template #title >
                    {{__('New')}} <span class="badge badge-primary">@{{newCount}}</span>
                   </template>
              </b-tab>
            </b-tabs>
          </div>
    </div>
    <div class="card-body p-2 d-flex flex-column"  v-cloak >

        <div  v-for="contact in contacts" :class="[ 'd-block','p-2',' border-radius-lg',{ 'bg-gradient-success': contact.isActive }]"  v-cloak>
            <div v-cloak class="d-flex p-2" v-on:click="setCurrentChat(contact.id)">

                <div v-if="contact.name[0]&&contact.avatar==''" class="avatar avatar-content bg-gradient-primary">@{{contact.name[0]}}</div>
                <img v-if="contact.avatar!=''" alt="Image"
                  :src="contact.avatar" :data-src="contact.avatar"
                    class="avatar shadow">
                <div class="ml-3">
                    <div class="justify-content-between align-items-center">
                        <h4 :class="[ 'mb-0',{ 'text-white': contact.isActive }]">@{{ contact.name }} - <span
                            :class="[ 'mb-2','text-xs','text-muted',{ 'text-white': contact.isActive }]">@{{ momentIt(contact.last_reply_at) }}</span></h4>
                        <p :style="{ fontWeight: contact.is_last_message_by_contact === 1 ? '700' : 'normal' }" :class="[ 'mb-0','text-sm',{ 'text-white': contact.isActive }]">@{{ contact.last_message }}</p>
                    </div>
                </div>


            </div>
            
        </div>
        <div v-if="contacts.length === 0">
            <p class="text-muted">{{__('Start a chat by sending a template message to contact.')}}</p>
          </div>



    </div>
</div>