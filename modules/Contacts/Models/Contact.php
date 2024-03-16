<?php

namespace Modules\Contacts\Models;

use App\Models\Company;
use Modules\Wpbox\Events\AgentReplies;
use Modules\Wpbox\Events\ContactReplies;
use Modules\Wpbox\Events\Chatlistchange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\CompanyScope;
use Modules\Wpbox\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\Wpbox\Models\Campaign;
use Modules\Wpbox\Models\Reply;
use Modules\Wpbox\Traits\Whatsapp;

class Contact extends Model
{
    use SoftDeletes;
    use Whatsapp;
    
    protected $table = 'contacts';
    public $guarded = [];

    public function groups()
    {
        return $this->belongsToMany(
                Group::class,
                'groups_contacts',
                'contact_id',
                'group_id'
            );
    }

    public function getCompany(){
        return Company::findOrFail($this->company_id);
    }

    public function fields()
    {
        return $this->belongsToMany(
                Field::class,
                'custom_contacts_fields_contacts',
                'contact_id',
                'custom_contacts_field_id'
            )->withPivot('value');
    }

    public function messages()
    {
        return $this->hasMany(
                Message::class
            )->orderBy('created_at','DESC');
    }

    public function country()
    {
        return $this->belongsTo(
                Country::class
            );
    }


    private function trimString($str, $maxLength) {
        if (mb_strlen($str) <= $maxLength) {
            return $str;
        } else {
            $trimmed = mb_substr($str, 0, $maxLength);
            $lastSpaceIndex = mb_strrpos($trimmed, ' ');
    
            if ($lastSpaceIndex !== false) {
                return mb_substr($trimmed, 0, $lastSpaceIndex) . '...';
            } else {
                return $trimmed . '...';
            }
        }
    }

    

    
    public function sendDemoMessage($content,$is_message_by_contact=true,$is_campaign_messages=false,$messageType="TEXT",$fb_message_id=null){
        //Check that all is set ok

        //Create the messages
         $messageToBeSend=Message::create([
            "contact_id"=>$this->id,
            "company_id"=>$this->company_id,
            "value"=>$messageType=="TEXT"?$content:"",
            "header_image"=>$messageType=="IMAGE"?$content:"",
            "header_document"=>$messageType=="DOCUMENT"?$content:"",
            "header_video"=>$messageType=="VIDEO"?$content:"",
            "header_location"=>$messageType=="LOCATION"?$content:"",
            "is_message_by_contact"=>$is_message_by_contact,
            "is_campign_messages"=>$is_campaign_messages,
            "status"=>1,
            "buttons"=>"[]",
            "components"=>"",
            "fb_message_id"=>$fb_message_id
         ]);
         $messageToBeSend->save();
    }



    /**
     * $reply - Reply - The reply to be send
     */
    public function sendReply(Reply $reply){
        //Create the message
        $buttons=[];

        for ($i=1; $i < 3; $i++) { 
            if ($reply->{"button".$i} != "") {
                $buttons[sizeof($buttons)] = [
                    "type" => "reply",
                    "reply" => [
                        "id" => $reply->{"button".$i."_id"},
                        "title" => $reply->{"button".$i}
                    ]
                ];
            }
        }
 
       

        $messageToBeSend=Message::create([
            "contact_id"=>$this->id,
            "company_id"=>$this->company_id,
            "value"=>$reply->text,
            "header_text"=>$reply->header,
            "footer_text"=>$reply->footer,
            "buttons"=>json_encode($buttons),
            "is_message_by_contact"=>false,
            "is_campign_messages"=>false,
            "status"=>1,
            "fb_message_id"=>null
        ]);
        $messageToBeSend->save();

       

        $this->last_support_reply_at=now();
        $this->is_last_message_by_contact=false;
        $this->sendMessageToWhatsApp($messageToBeSend,$this);
        //Find the user of the company
        $companyUser=Company::findOrFail($this->company_id)->user;
        event(new AgentReplies($companyUser,$messageToBeSend,$this));

        $this->last_message=$this->trimString($reply->text,40);
        $this->update();

    }


    


    /**
     * $content- String - The content to be stored, text or link
     * $is_message_by_contact - Boolean - is this a message send by contact - RECEIVE
     * $is_campaign_messages - Boolean - is this a message generated from campaign
     * $messageType [TEXT | IMAGE | VIDEO | DOCUMENT ]
     * $fb_message_id String - The Facebook message ID
     */
    public function sendMessage($content,$is_message_by_contact=true,$is_campaign_messages=false,$messageType="TEXT",$fb_message_id=null){
        //Check that all is set ok

        //Create the messages
         $messageToBeSend=Message::create([
            "contact_id"=>$this->id,
            "company_id"=>$this->company_id,
            "value"=>$messageType=="TEXT"?$content:"",
            "header_image"=>$messageType=="IMAGE"?$content:"",
            "header_document"=>$messageType=="DOCUMENT"?$content:"",
            "header_video"=>$messageType=="VIDEO"?$content:"",
            "header_audio"=>$messageType=="AUDIO"?$content:"",
            "header_location"=>$messageType=="LOCATION"?$content:"",
            "is_message_by_contact"=>$is_message_by_contact,
            "is_campign_messages"=>$is_campaign_messages,
            "status"=>1,
            "buttons"=>"[]",
            "components"=>"",
            "fb_message_id"=>$fb_message_id
         ]);
         $messageToBeSend->save();

         

        //Update the contact last message, time etc
        

        if(!$is_campaign_messages){
            $this->has_chat=true;
            $this->last_reply_at=now();
            if($is_message_by_contact){
                $this->last_client_reply_at=now();
                $this->is_last_message_by_contact=true;

                
               
                //Reply bot
                $textReplies=Reply::where('type','!=',1)->where('company_id',$this->company_id)->get();
                $replySend=false;
                foreach ($textReplies as $key => $qr) {
                    if(!$replySend){
                        $replySend=$qr->shouldWeUseIt($content,$this);
                    }
                }

                //If no text reply found, look for campaign reply
                if(!$replySend){
                    $campaignReplies=Campaign::where('is_bot',1)->where('is_bot_active',1)->where('company_id',$this->company_id)->get();
                    foreach ($campaignReplies as $key => $cr) {
                        if(!$replySend){
                            $replySend=$cr->shouldWeUseIt($content,$this);
                        }
                    }
                }

                if($replySend){
                    $messageToBeSend->bot_has_replied=true;
                    $messageToBeSend->update();
                }

                //Notify
                event(new ContactReplies(auth()->user(),$messageToBeSend,$this));
                event(new Chatlistchange($this->id,$this->company_id)); 
                

            }else{
                $this->last_support_reply_at=now();
                $this->is_last_message_by_contact=false;
                $this->sendMessageToWhatsApp($messageToBeSend,$this);
                event(new AgentReplies(auth()->user(),$messageToBeSend,$this));
            }    
        }
        $this->last_message=$this->trimString($content,40);
        $this->update();

       


        
        return $messageToBeSend;
    }

    protected static function booted(){
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model){
           $company_id=session('company_id',null);
            if($company_id){
                $model->company_id=$company_id;
            }
        });

        static::created(function ($model){
            //Determine the country
            $country_id=$model->getCountryByPhoneNumber($model->phone);
            if($country_id){
                $model->country_id=$country_id;
                $model->update();
            }
            
         });
    }
    
    private function getCountryByPhoneNumber($phoneNumber) {

        if (strpos($phoneNumber, '+') !== 0) {
            $phoneNumber = '+' . $phoneNumber;
        }

        $prefixes = Country::pluck('id','phone_code');
       
        // Use regular expression to extract the prefix
        if (preg_match('/^\+(\d{1})/', $phoneNumber, $matches)) {
            $prefix = $matches[1];
    
            // Check if the prefix exists in the array
            if (isset($prefixes[$prefix])) {
                return $prefixes[$prefix];
            }else if (preg_match('/^\+(\d{2})/', $phoneNumber, $matches)) {
                $prefix = $matches[1];
        
                // Check if the prefix exists in the array
                if (isset($prefixes[$prefix])) {
                    return $prefixes[$prefix];
                }else if (preg_match('/^\+(\d{3})/', $phoneNumber, $matches)) {
                $prefix = $matches[1];
        
                // Check if the prefix exists in the array
                if (isset($prefixes[$prefix])) {
                    return $prefixes[$prefix];
                }
            }
            }
        }
    
        return null;
    }
}
