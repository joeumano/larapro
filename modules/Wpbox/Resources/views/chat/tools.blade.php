<div class="card-footer d-block" style="padding: 1.25rem">
    <div class="align-items-center" >
        <div class="d-flex">
            <button v-if="!mobileChat" type="button" class="btn btn-outline" id="emoji-btn">
                😀
            </button>
            <button type="button" class="btn btn-outline" id="img-btn" @click="openImageSelector">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2dce89" class="w-6 h-6" style="width: 20px; height:20px">
                    <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
                  </svg>
                  
            </button>


            <button type="button" class="btn btn-outline" id="file-btn" @click="openFileSelector">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2dce89" class="w-6 h-6" style="width: 20px; height:20px">>
                    <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z" />
                    <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" />
                  </svg>
                  
                  
            </button>

            <input accept="image/*, video/*, audio/*" @change="handleImageChange" type="file" ref="imageInput" style="display: none" />
            <input accept=".pdf, .doc, .docx" @change="handleFileChange" type="file" ref="fileInput" style="display: none" />
                      
            
            <div class="input-group">

                <input @keyup.enter="sendChatMessage" v-model="activeMessage" type="text" id="message" class="form-control" placeholder="{{ __('Type here') }}"
                    aria-label="{{ __('Message') }}">
                    
            </div>
            <button class="btn btn-success mb-0 ms-2 ml-2" @click="sendChatMessage">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6" style="width: 20px; height:20px">
                    <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
                  </svg>
                  
            </button>
        </div>
    </div>
</div>