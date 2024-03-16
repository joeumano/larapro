<script>
    "use strict";
    var chatList=null;
    var lastmessagetime="none";
    var chatMessages={};
    var pusherConn = null;
    var pusherConnForUpdates = null;
    var channel = null;
    var channelUpdate=null;
    var pusherActiveChat=null;
    var companyID="<?php echo auth()->user()->company_id; ?>";
    var serverTimezone = "<?php echo config('app.timezone'); ?>";
    var pusherAvailable=false;

    var initPusher=function(){
        if (typeof Pusher !== 'undefined') {
            // The variable is defined
            // You can safely use it here
            Pusher.logToConsole = false;

            pusherConn = new Pusher(PUSHER_APP_KEY, {
                cluster: PUSHER_APP_CLUSTER
            });
            pusherAvailable=true;

            pusherConnForUpdates = new Pusher(PUSHER_APP_KEY, {
                cluster: PUSHER_APP_CLUSTER
            });

            //Bind to new chat list update
            channelUpdate = pusherConnForUpdates.subscribe('chatupdate.'+companyID);
            channelUpdate.bind('general', chatListUpdate);

            

        } else {
            // Pusher
            js.notify("Error: Pusher is not defined. Chat will not load new messages. Please check documentation","danger");
        }
    }


    var connectToChannel=function(chatID){
        if(pusherActiveChat!=chatID && pusherAvailable){
            if(channel!=null){
                //Change chat, release old one
                channel.unsubscribe();
                channel.unbind('general', receivedMessageInPusher);
            }
            //Set active chat
            pusherActiveChat=chatID;

            //Bind to new chat
            channel = pusherConn.subscribe('chat.'+chatID);
            channel.bind('general', receivedMessageInPusher);

            

        }else{
            //Same chat, no changes
        }
    }

    var receivedMessageInPusher=function(data){
        
        const index = chatList.contacts.findIndex(item => item.id === data.contact.id);
        chatMessages[data.contact.id].push(data.message);
      
        //Update the last message
        chatList.contacts[index].last_message = data.message.value;

        //Scroll to bottom
        setTimeout(() => {
            if($('#chatMessages')[0]&&$('#chatMessages')[0].scrollHeight){
                $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight); 
            }
            
        }, 1000);
        
        
        
    }

    var chatListUpdate=function(data){
        
        
        if(data.contact!==chatList.activeChat.id){
            
            getChatsJS();
        }else{
            //Same chat
            
        }
    }


    


    

    var getChatJS=function(contact_id){
        if(chatMessages[contact_id]){
            //Previous messages
            chatList.messages=chatMessages[contact_id];
        }
        axios.get('/api/wpbox/chat/'+contact_id).then(function (response) {
            var messages=response.data.data;
            messages=messages.reverse();
            chatMessages[contact_id]=messages;
            chatList.messages=chatMessages[contact_id];
        }).catch(function (error) {
            
        });

        connectToChannel(contact_id);
        
        
    }

    var getChatsJS=function(){
        axios.get('/api/wpbox/chats/'+lastmessagetime).then(function (response) {
            if(response.data.status){
                var initialChatLoad=chatList.contacts.length==0;
                chatList.contacts=response.data.data;
                chatList.all=response.data.data;

                if(chatList.contacts.length>0){
                    
                    if(chatList.activeChat.id==null){
                        /*getChatJS(chatList.contacts[0].id);
                        chatList.contacts[0].isActive=true;
                        chatList.activeChat=chatList.contacts[0];*/
                    }else{
                        //Stays the same last active chat
                        const index = chatList.contacts.findIndex(item => item.id === chatList.activeChat.id);
                        if (index !== -1) {
                            chatList.contacts[index].name = chatList.contacts[index].name+" ";
                            chatList.contacts[index].isActive = true;
                        }
                    }
                    lastmessagetime=chatList.contacts[0].last_reply_at; 
                    
                    //Play Sound
                    if(!initialChatLoad){
                        playSound();
                    }
                    

                }
            }
            
        }).catch(function (error) {
            
        });
    }

    function playSound() {
        var audio = new Audio('/vendor/meta/pling.mp3');
        audio.play();
    }

    function escapeSingleQuotesInJSON(jsonString) {
        // Use a regular expression to find and replace single quotes inside string values
        const escapedJSONString = jsonString.replace(/"([^"]*?)":\s*"([^"]*?)"/g, function(match, key, value) {
            const escapedValue = value.replace(/'/g, "\\'");
            return `"${key}": "${escapedValue}"`;
        });

        return escapedJSONString;
    }

    

    window.onload = function () {
        initPusher();
        getChatsJS();

        //Emojy
        new EmojiPicker({
            trigger: [
                {
                    selector: '#emoji-btn',
                    insertInto: ['#message'] // If there is only one '.selector', than it can be used without array
                }
            ],
           
            closeButton: true,
            specialButtons: 'green' // #008000, rgba(0, 128, 0);
        });

        //VUE Chat list
        Vue.config.devtools=true;
        
        chatList = new Vue({
        el: '#chatList',
        data: {
            templates: @json($templates),
            replies: @json($replies),
            users: @json($users),
            currentUserID: "{{auth()->user()->id}}",
            contacts: [],
            all:[],
            activeChat:{},
            messages:[],
            activeMessage:"",
            selectedImage: null,
            selectedFile: null,
            filterText: '',
            filterTemplates: '',
            mobileChat:window.innerWidth<768,
            conversationsShown:true,
            tab:"all",
        },
        errorCaptured(err, component, info) {
            console.error('An error occurred:', err);
            console.error('Component in which error occurred:', component);
            console.error('Additional information:', info);
            return false; // this ensures that we still get the default behavior
        },
       computed: {
            filteredReplies() {
                const filterText = this.filterText.toLowerCase();
                return this.replies.filter(item => item.name.toLowerCase().includes(filterText));
            },
            filteredTemplates() {
                const filterTemplates = this.filterTemplates.toLowerCase();
                return this.templates.filter(item => item.name.toLowerCase().includes(filterTemplates));
            },
            newCount(){
                return this.all.filter(contact => contact.is_last_message_by_contact).length;
            },
            mineCount(){
                return this.all.filter(contact => contact.user_id==this.currentUserID).length;
            },
            allCount(){
                return this.all.length;
            },
        },
        methods: {
            mineMessages:function(){
                this.tab="mine";
                this.filterContacts();
            },
            allMessages:function(){
                this.tab="all";
                this.filterContacts();
            },
            newMessages:function(){
                this.tab="new";
                this.filterContacts();
            },
            filterContacts() {
                const index = this.contacts.findIndex(item => item.id === chatList.activeChat.id);
                        if (index !== -1) {
                            chatList.contacts[index].name = chatList.contacts[index].name+" ";
                            chatList.contacts[index].isActive = true;
                        }

                if(this.tab=="all"){
                    this.contacts=this.all;
                }else if(this.tab=="mine"){
                    this.contacts=this.all.filter(contact => contact.user_id==this.currentUserID);
                }else if(this.tab=="new"){
                    this.contacts=this.all.filter(contact => contact.is_last_message_by_contact);
                }
            },
            formatIt: function(message){
                const linkRegex = /https?:\/\/[^\s/$.?#].[^\s]*/g;
      
                // Replace links with placeholders for rendering
                const replacedText = message.replace(linkRegex, '<a href="$&" class="text-bold">$&</a>');

                return replacedText;
            },
            getAssignedUser: function(contact){
                if(contact.user_id){
                    const user = Object.keys(this.users).find(user => user == contact.user_id);
                    return this.users[user] ? this.users[user] : '-';
                }
                return 'Not assigned';
            },
            assignUser: function(user_id, contact_id){
                axios.post('/api/wpbox/assign/'+contact_id, {user_id: user_id}).then(function (response) {
                    if(response.data.status){
                        chatList.activeChat.user_id=user_id;
                        const indexUpdate = chatList.all.findIndex(item => item.id == contact_id);
                        console.log(indexUpdate);
                        if (indexUpdate !== -1) {
                            chatList.all[indexUpdate].user_id = user_id;
                        }
                        chatList.filterContacts();
                    }else{  
                        js.notify(response.data.errMsg,"danger");
                    }
                }).catch(function (error) {
                    console.log(error);
                });
            },
            getReplyNotification(contact){
                var timeSinceLastClientReply= moment.tz(contact.last_client_reply_at,serverTimezone).add(24, 'hours');
                const minutesDifference = timeSinceLastClientReply.diff(moment.now(), 'minutes');
                var statusOfReply={
                    "class":"badge-danger",
                    "text":"{{ __('You can no longer reply')}}!"
                };
                if(minutesDifference>0){
                    if(minutesDifference>60){
                        statusOfReply.class="badge-success";
                        statusOfReply.text=moment.duration(minutesDifference, 'minutes').humanize();
                    }else{
                        statusOfReply.class="badge-warning";
                        statusOfReply.text=moment.duration(minutesDifference, 'minutes').humanize();
                    }
                    statusOfReply.text+=" {{ __('left to reply')}}";
                }
                return statusOfReply;
            },
            setCurrentChat: function (contact_id) {


                if(this.mobileChat){
                    this.conversationsShown=false;
                }
                
                getChatJS(contact_id);

                const indexRemove = this.all.findIndex(item => item.id === this.activeChat.id);
                if (indexRemove !== -1) {
                    this.all[indexRemove].name = this.all[indexRemove].name+" ";
                    this.all[indexRemove].isActive = false;
                }
                
                const index = this.all.findIndex(item => item.id === contact_id);
                if (index !== -1) {
                    this.all[index].name = this.all[index].name+" ";
                    this.all[index].isActive = true;
                    this.activeChat= this.all[index];
                }

                setTimeout(() => {
                    this.scrollToBottomOfChat();
                }, 1000);
               
               




                
            },
            getChats:function (){
                getChatsJS();
            },
            momentIt: function (date) {
                return moment.tz(date,serverTimezone).fromNow();
            },
            momentHM: function (date) {
                return moment.tz(date,serverTimezone).format('HH:mm');;
            },
            momentDay:function (date) {
                return moment.tz(date,serverTimezone).format('dddd, D MMM, YYYY');
            },
            scrollToBottomOfChat() {
                const scrollableDiv = this.$refs.scrollableDiv;
                if( scrollableDiv && scrollableDiv.scrollHeight){
                    scrollableDiv.scrollTop = scrollableDiv.scrollHeight;
                   
                }
            },
            parseJSON:function(jsonString){
                if(jsonString==null||jsonString==""){
                    return [];
                }
                return JSON.parse(jsonString);
            },
            setMessage(message){
                this.$bvModal.hide('modal-replies');    
                message=message.replace("\{\{name\}\}",this.activeChat.name);   
                message=message.replace("\{\{phone\}\}",this.activeChat.phone);   
                this.activeMessage=message;
            },
            setVueMessage(message){
                this.activeMessage=this.activeMessage+message;
            },
            sendChatMessage(){
               
            
                var message=this.activeMessage;
                this.activeMessage="";
                axios.post('/api/wpbox/send/'+chatList.activeChat.id, {message: message}).then(function (response) {
                    
                    if(response.data.status){
                        lastmessagetime=response.data.messagetime;

                    }else{
                        js.notify(response.data.errMsg,"danger");
                    }}).catch(function (error) {
                
                    });
                    
            },
            showConversations(){
                const indexRemove = this.contacts.findIndex(item => item.id === this.activeChat.id);
                if (indexRemove !== -1) {
                    this.contacts[indexRemove].name = this.contacts[indexRemove].name+" ";
                    this.contacts[indexRemove].isActive = false;
                }   
                this.activeChat={};
                this.conversationsShown=true;
            },
            openImageSelector() {
                // Trigger the file input click event
                this.$refs.imageInput.click();
            },
            openFileSelector() {
                // Trigger the file input click event
                this.$refs.fileInput.click();
            },
            handleImageChange(event) {
                // Get the selected image file
                this.selectedImage = event.target.files[0];

                if (!this.selectedImage) {
                    alert('Please select an image first.');
                    return;
                }else{
                     // Create a FormData object to send the image to the API
                    const formData = new FormData();
                    formData.append('image', this.selectedImage);
                    axios.post('/api/wpbox/sendimage/'+chatList.activeChat.id, formData);
                }
            },
            handleFileChange(event) {
                // Get the selected file
                this.selectedFile = event.target.files[0];

                if (!this.selectedFile) {
                    alert('Please select a file first.');
                    return;
                }else{
                     // Create a FormData object to send the image to the API
                    const formData = new FormData();
                    formData.append('file', this.selectedFile);
                    axios.post('/api/wpbox/sendfile/'+chatList.activeChat.id, formData);
                }
            },
            },
        })

     
    };


</script>