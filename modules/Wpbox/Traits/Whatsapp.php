<?php

namespace Modules\Wpbox\Traits;

use App\Models\Company;
use Modules\Wpbox\Models\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;
use Modules\Contacts\Models\Contact;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Modules\Wpbox\Models\Campaign;
use Modules\Wpbox\Models\Template;

trait Whatsapp
{

    public static $facebookAPI = 'https://graph.facebook.com/v19.0/';

    private function getToken(Company $company=null){
        if($company==null){
            $company=$this->getCompany();
        }
        return $company->getConfig('whatsapp_permanent_access_token','');
    }

    private function getPhoneID(Company $company=null){
        if($company==null){
            $company=$this->getCompany();
        }
        return $company->getConfig('whatsapp_phone_number_id','');
    }

    private function getAccountID(Company $company=null){
        if($company==null){
            $company=$this->getCompany();
        }
        return $company->getConfig('whatsapp_business_account_id','');
    }

    

    private function sendCampaignMessageToWhatsApp(Message $message){

        //We need data per company
        $company=null;
        try {
            $company=$message->campaign->company;
            $message->contact->phone;
        } catch (\Throwable $th) {
            $message->error="The company or contact is not found";
            $message->status=1;
            $message->update();
        }
       
        if($company){
            $url = self::$facebookAPI.$this->getPhoneID($company).'/messages';
            $accessToken = $this->getToken($company);
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $message->contact->phone, // Add recipient information
                    'type' => 'template',
                    'template'=>[
                        "name"=> $message->campaign->template->name,
                        "language"=> [
                            "code"=> $message->campaign->template->language
                        ],
                        "components"=>json_decode($message->components)
                    ]
                ]);
            
                
                $statusCode = $response->status();
                $content = json_decode($response->body(),true);
                //dd($content);
                $message->created_at=now();
                if(isset($content['messages'])){
                    $message->fb_message_id=$content['messages'][0]['id'];
                }else{
                    $message->error=isset($content['error'])?$content['error']['message']:"Unknown error";
                    //dd($content);
                }
                $message->status=1;
                $message->update();
                // Handle the response as needed based on $statusCode and $content
            } catch (\Exception $e) {
                //dd($e);
                // Handle the exception
            }
        }
        
    
    }

    public function receiveMessage(Request $request,$token){
        $token = PersonalAccessToken::findToken($token);
  
        if ($token) {

            
            // Token is valid
            // Proceed with the request handling

            //Find the user
            $user=User::findOrFail($token->tokenable_id);
            Auth::login($user);
            
            //Company
            $company=$this->getCompany();

            //Resend the Request to webhook
            try {
                $whatsapp_data_send_webhook=$company->getConfig('whatsapp_data_send_webhook',"");
                if(strlen($whatsapp_data_send_webhook)>5){
                    //Send the data to a webhook
                    Http::post($whatsapp_data_send_webhook, $request->all());
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
            
            //Get the message object
            try {
                $value=$request->entry[0]['changes'][0]['value'];
                if(isset($value['statuses'])){

                    //Status change -- Message update
                    $newStatus=$value['statuses'][0]['status'];
                    $messageFBID=$value['statuses'][0]['id'];
                    $message=Message::where('fb_message_id',$messageFBID)->first();
                    if($message){
                        if($newStatus=="sent"&&$message->status!=3){
                            $message->status=2;
                        }else if($newStatus=="delivered"&&$message->status!=4){
                            $message->status=3;
                        }else if($newStatus=="read"){
                            $message->status=4;
                        }else if($newStatus=="failed"){
                            $message->status=5;
                            $message->error=$value['statuses'][0]['errors'][0]['message'];
                        }
                        $message->update();
                        if($message->campaign_id!=null){
                            $campaign=Campaign::where('id',$message->campaign_id)->first();
                            if($campaign){
                                if($newStatus=="sent"){
                                    $campaign->increment('sended_to', 1);
                                }else if($newStatus=="delivered"){
                                    $campaign->increment('delivered_to', 1);
                                }else if($newStatus=="read"){
                                    $campaign->increment('read_by', 1);
                                }
                                $campaign->update();
                            }
                            
                        }
                    }
                }else{
                    //Message receive
                    $phone=$value['messages'][0]['from'];

                     //Check if this phone is in the blacklist
                    $blacklist=$company->getConfig('black_listed_phone_numbers',"");
                    if(strlen($blacklist)>5){
                        $blacklist=explode(",",$blacklist);
                        
                        if(in_array($phone,$blacklist)){
                            return response()->json(['send' => false,'error'=>"Blacklisted"]);
                        }
                    }

                    $type=$value['messages'][0]['type'];
                    $name=$value['contacts'][0]['profile']['name'];
                    $messageID=$value['messages'][0]['id'];

                    //Find the contact
                    $contact=Contact::where('phone',$phone)->orWhere('phone',"+".$phone)->first();

                    if(!$contact){
                        //Create new contact
                        $contact=Contact::create([
                            'name' => $name,
                            'phone' =>  $phone,
                            'avatar'=> '',
                            'company_id'=>$company->id,
                            'has_chat'=>true,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'last_support_reply_at'=>null,
                            'last_reply_at'=>now(),
                            "last_message"=>"",
                            "is_last_message_by_contact"=>true,    
                        ]);
                    }

                    

                    if($type=="image"){
                        //We need to download and store the image
                        $urlLink=$this->downloadAndStoreMedia($value['messages'][0]['image']['id'],".jpg");
                        $message=$contact->sendMessage($urlLink,true,false,"IMAGE",$messageID);
                    }else if($type=="audio"){
                        // We need to download and store the audio
                        $urlLink = $this->downloadAndStoreMedia($value['messages'][0]['audio']['id'], '.mp3');
                        $message = $contact->sendMessage($urlLink, true, false, "AUDIO", $messageID);
                    } else if($type=="video"){
                        //We need to download and store the video
                        $urlLink=$this->downloadAndStoreMedia($value['messages'][0]['video']['id'],'.mp4');
                        $message=$contact->sendMessage($urlLink,true,false,"VIDEO",$messageID);
                    }else if($type=="document"){
                        //We need to download and store the video
                        $urlLink=$this->downloadAndStoreMedia($value['messages'][0]['document']['id'],'.pdf');
                        $message=$contact->sendMessage($urlLink,true,false,"DOCUMENT",$messageID);
                    }else if($type=="text"){
                        
                        $message=$value['messages'][0]['text']['body'];
                        //Store the message
                        $message=$contact->sendMessage($message,true,false,"TEXT",$messageID);

                    }else if($type=="contacts"||$type=="contact"){
                        $message=$contact->sendMessage(__("Contact message is sent. But the message format is unsupported"),true,false,"TEXT",$messageID);
                    }else if($type=="location"){

                        //return response()->json(['send' => "Location"]);
                        $message="https://www.google.com/maps?q=".$value['messages'][0]['location']['latitude'].','.$value['messages'][0]['location']['longitude'];
                        //Store the message
                        $message=$contact->sendMessage($message,true,false,"LOCATION",$messageID);
                    }else if($type=="button"){
                        $message=$value['messages'][0]['button']['text'];
                        //Store the message
                        $message=$contact->sendMessage($message,true,false,"TEXT",$messageID);
                    } 
                    
                    
                }
                
                
                return response()->json(['send' => true]);


            } catch (\Throwable $th) {
                return response()->json(['send' => false,'error'=>$th,'data'=>$request]);
            }

            
            


           


            
        }else{
            return response()->json(['send' => false]);
        }
    }

    public function downloadAndStoreMedia($mediaID,$ext=".jpg"){
        $url =  self::$facebookAPI.$mediaID;
        $accessToken = $this->getToken();
        $company=$this->getCompany();
        try {

            


            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($url);
        
            
            $statusCode = $response->status();
            $content = json_decode($response->body(),true);

            $responseImage = $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($content['url']);
        
            $fileContents = $responseImage->getBody()->getContents();
            

            // Define the local path where you want to save the downloaded file
            if(config('settings.use_s3_as_storage',false)){
                //S3 - store per company
                $fileName='uploads/media/received/'.$company->id."/".$content['id'].$ext;
                $path = Storage::disk('s3')->put($fileName, $fileContents,'public');
                return Storage::disk('s3')->url($fileName);
            }else{
                //Regular
                $localPath = public_path('uploads/media/'.$content['id'].$ext);
                file_put_contents($localPath, $fileContents);
                $url=config('app.url')."/uploads/media/".$content['id'].$ext;
                return preg_replace('#(https?:\/\/[^\/]+)\/\/#', '$1/', $url);
            }



           



            

        }catch (\Exception $e) {
            dd($e);
            // Handle the exception
        }
    }

    public function verifyWebhook(Request $request,$tokenViaURL){
        // Parse params from the webhook verification request
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        
        $token = PersonalAccessToken::findToken($token);
        if ($token) {
            // Token is valid
            // Proceed with the request handling
            // Check if a token and mode were sent
            if ($mode && $token) {
                // Check the mode and token sent are correct
                if ($mode === 'subscribe') {
                    $user=User::findOrFail($token->tokenable_id);
                    Auth::login($user);

                    //Company
                    $company=$this->getCompany();

                    $company->setConfig('whatsapp_webhook_verified',"yes");

                    // Respond with 200 OK and challenge token from the request
                    return response($challenge, 200);
                } else {
                    // Respond with '403 Forbidden' if verify tokens do not match
                    return response()->json([], 403);
                }
            }
        }else{
            return response()->json([], 403);
        }

        

       
    }



    public function loadTemplatesFromWhatsApp($after=""){
        $url =  self::$facebookAPI.$this->getAccountID().'/message_templates';
        $queryParams = [
            'fields' => 'name,category,language,quality_score,components,status',
            'limit' => 100
        ];
        if($after!=""){
            $queryParams['after']=$after;
        }
        $headers = [
            'Authorization' => 'Bearer '.$this->getToken()
        ];

        $response = Http::withHeaders($headers)->get($url, $queryParams);

 
    

        // Handle the response here
        if ($response->successful()) {
            $responseData = $response->json();
            

            //On success - remove all previous templates, if on initial call
            if($after==""){
                Template::query()->each(function ($model) {
                    try {
                        $model->delete();
                    } catch (\Throwable $th) {
                       
                    }
                   
                });
            }
            


            $companyID=$this->getCompany()->id;
            foreach ($responseData['data'] as $key => $template) {
                //Insert new messages
                try {
                    $data=[
                        'name' => $template['name'],
                        'category' => $template['category'],
                        'language' => $template['language'],
                        'status' => $template['status'],
                        'id' => $template['id'],
                        'company_id' =>$companyID,
                        'components' => json_encode($template['components']),
                    ];
                    $template=Template::upsert($data,'id',['components','status']);
                } catch (\Throwable $th) {
                    //throw $th;
                    //dd($th);    
                }
            }

            //Check if we have more templates
            if($responseData['paging']&&isset($responseData['paging']['next'])&&isset($responseData['paging']['cursors']['after'])){
                return $this->loadTemplatesFromWhatsApp($responseData['paging']['cursors']['after']);
            }else{
                return true;
            }
            
            // Process $responseData as needed
        } else {
            // Handle error response
           return false;
        }
    }



    public function sendMessageToWhatsApp(Message $message,$contact){
        $url =  self::$facebookAPI.$this->getPhoneID().'/messages';
        $accessToken = $this->getToken();

        if(strlen($accessToken>5)){
            try {

                $dataToSend=[
                    'messaging_product' => 'whatsapp',
                    'to' => $contact->phone, // Add recipient information
                ];
    
                if(strlen($message->buttons)>5){
                    //Interactive message
                    $dataToSend['type']='interactive';
                    $dataToSend['interactive']['type']="button";

                    $dataToSend['interactive']['body']=[
                        'text' => $message->value,
                    ];

                    //Header if available
                    if(strlen($message->header_text)>0){
                        $dataToSend['interactive']['header']=[
                            'type' => 'text',
                            'text' => $message->header_text,
                        ];
                    }

                    //Footer if available
                    if(strlen($message->footer_text)>0){
                        $dataToSend['interactive']['footer']=[
                            'text' => $message->footer_text,
                        ];
                    }

                    $dataToSend['interactive']['action']['buttons']= array_values(json_decode($message->buttons,true));
                    //dd($dataToSend);
                }
                else if(strlen($message->value)>0){
                    //Text message
                    $dataToSend['type']='text';

                    if(config('settings.is_demo',false)){
                        //Demo
                        $dataToSend['text']=[
                            'body' => "[THIS IS DEMO] ".$message->value,
                            'preview_url' => true,
                        ];
                    }else{
                        //Production
                        $dataToSend['text']=[
                            'body' => $message->value,
                            'preview_url' => true,
                        ];
                    }


                
                    
                }else if(strlen($message->header_image)>0){
                    $dataToSend['type']='image';
                    $dataToSend['image']=[
                        'link' => $message->header_image
                    ];
                }else if(strlen($message->header_video)>0){
                    if (substr($message->header_video, -4) === ".mp3") {
                        $dataToSend['type']='audio';
                        $dataToSend['audio']=[
                            'link' => $message->header_video
                        ];
                    }else{
                        $dataToSend['type']='video';
                        $dataToSend['video']=[
                            'link' => $message->header_video
                        ];
                    }
                    
                }else if(strlen($message->header_audop)>0){
                    $dataToSend['type']='audio';
                    $dataToSend['audio']=[
                        'link' => $message->header_audio
                    ];
                    
                }else if(strlen($message->header_document)>0){
                    $path = parse_url($message->header_document, PHP_URL_PATH);
                    $filename = pathinfo($path, PATHINFO_FILENAME);
                    $dataToSend['type']='document';
                    $dataToSend['document']=[
                        'link' => $message->header_document,
                        "filename"=>$filename,
                    ];
                }
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, $dataToSend);
                
                $statusCode = $response->status();
                $content = json_decode($response->body(),true);
                $message->fb_message_id=$content['messages'][0]['id'];
                $message->update();
                // Handle the response as needed based on $statusCode and $content
            } catch (\Exception $e) {
                // Handle the exception
            }
        }
        
        
    
    }


}