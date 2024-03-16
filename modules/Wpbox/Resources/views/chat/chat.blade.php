<div class="theChatHolder card shadow max-height-vh-90 overflow-auto overflow-x-hidden">
    <div class="card-header shadow-lg" id="theChatHeader">
        <div class="row">

            <div v-cloak class="col-lg-8 col-6">

                <div class="d-flex align-items-center" v-cloak>
                    <button @click="showConversations" v-cloak v-if="mobileChat" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#2dce89" class="w-6 h-6"
                            style="width: 20px; height:20px">
                            <path fill-rule="evenodd"
                                d="M9.53 2.47a.75.75 0 010 1.06L4.81 8.25H15a6.75 6.75 0 010 13.5h-3a.75.75 0 010-1.5h3a5.25 5.25 0 100-10.5H4.81l4.72 4.72a.75.75 0 11-1.06 1.06l-6-6a.75.75 0 010-1.06l6-6a.75.75 0 011.06 0z"
                                clip-rule="evenodd" />
                        </svg>

                    </button>
                    
                        
                    
                    <a   :href="'/contacts/contacts/'+activeChat.id+'/edit'" class="profile-picture-container">
                        <div v-cloak v-if="activeChat&&activeChat.name&&activeChat.name[0]&&(activeChat.avatar==''||activeChat.avatar==null)"
                            class="avatar avatar-content bg-gradient-success" style="min-width:48px">@{{activeChat.name[0]}}
                        </div>
                        <img v-cloak v-if="activeChat&&(activeChat.avatar!=''&&activeChat.avatar!=null)"  alt="" :src="activeChat.avatar"
                            :data-src="activeChat.avatar" class="avatar" />
                            
                        <span  id="userCountry" v-if="activeChat&&activeChat.country" :class="'fi-'+activeChat.country.iso2.toLowerCase()" class="fi  fis flag-icon"></span>
                        <b-tooltip  target="userCountry">@{{activeChat.country.name}}</b-tooltip>
                        
                    </a>
                    <div class="ml-3">
                        <a :href="'/contacts/contacts/'+activeChat.id+'/edit'">
                            <h4 class="mb-0 d-block">@{{activeChat.name}} <span class="badge badge-pill" :class="(getReplyNotification(activeChat)).class">@{{ (getReplyNotification(activeChat)).text }}</span></h4>
                        </a>
                        <span class="text-sm text-dark opacity-8">@{{activeChat.phone}}</span>
                        


                    </div>
                </div>
            </div>

            @include('wpbox::chat.actions')
        </div>
    </div>
    <div class="card-body overflow-auto overflow-x-hidden scrollable-div" ref="scrollableDiv" id="chatMessages">
        @include('wpbox::chat.message')
    </div>
</div>
