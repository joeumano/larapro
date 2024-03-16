<?php

namespace Modules\Wpbox\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Contacts\Models\Contact;
use Modules\Wpbox\Models\Campaign;
use Modules\Wpbox\Models\Template;
use Modules\Wpbox\Traits\Whatsapp;
use Modules\Wpbox\Traits\Contacts;
use Carbon\Carbon;
use Modules\Contacts\Models\Group;
use Modules\Wpbox\Models\Message;

class APIController extends Controller
{
    use Contacts;
    use Whatsapp;
   


    //Send message to phone number
    public function sendMessageToPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'phone' => 'required',
            'message' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

   


        if (config('settings.is_demo')) {
            return response()->json([
                'status' => 'error',
                'errors' => "API is disabled in demo"
            ], 400);
        }
        
        //Validate token
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token){
            return response()->json(['status'=>'error','message'=>'Invalid token']);
        }else{
            $user=User::findOrFail($token->tokenable_id);
            Auth::login($user);

            //Company
            $company=$this->getCompany();

            //Make or get the contact
            $contact=$this->getOrMakeContact($request->phone,$company,$request->phone);

            $message=$contact->sendMessage($request->message,false);

            return response()->json(['status'=>'success','message_id'=>$message->id,'message_wamid'=>$message->fb_message_id]);
        }

        
    }

    //Send Template     message to phone number
    public function sendTemplateMessageToPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'phone' => 'required',
            'template_name' => 'required',
            'template_language' => 'required',
            'components' => 'array'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        if (config('settings.is_demo')) {
            return response()->json([
                'status' => 'error',
                'errors' => "API is disabled in demo"
            ], 400);
        }
        
        
        //Validate token
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token){
            return response()->json(['status'=>'error','message'=>'Invalid token']);
        }else{
            $user=User::findOrFail($token->tokenable_id);
            Auth::login($user);

            //Company
            $company=$this->getCompany();

            //Make or get the contact
            $contact=$this->getOrMakeContact($request->phone,$company,$request->phone);

            //Find the template based on the provided id
            $template=Template::where('company_id',$company->id)->where('name',$request->template_name)->where('language',$request->template_language)->first();

            if(!$template){
                return response()->json(['status'=>'error','message'=>'Invalid template']);
            }

            $campaign = Campaign::create([
                'name'=>"api_message_".now(),
                'timestamp_for_delivery'=>null,
                'variables'=>"",
                'variables_match'=>"",
                'template_id'=>$template->id,
                'group_id'=>null,
                'contact_id'=>$contact->id,
                'total_contacts'=>Contact::count(),
            ]);

            $bodyText="API Message";
            $header_text="";
            try {
                foreach(json_decode($template->components,true) as $component){
                    if($component['type']=='BODY'){
                        $bodyText=$component['text'];
                        foreach ($request->components as $key => $receivedComponent) {
                            if($receivedComponent['type']=='body'){
                                foreach ($receivedComponent['parameters'] as $keyp => $parameter) {
                                    $bodyText=str_replace("{{".($keyp+1)."}}", $parameter['text'], $bodyText);
                                }
                            }
                        }
                    }
                    if($component['type']=='HEADER'&&$component['format']=="TEXT"){ 
                        $header_text=$component['text'];
                        foreach ($request->components as $key => $receivedComponent) {
                            if($receivedComponent['type']=='header'){
                                foreach ($receivedComponent['parameters'] as $keyp => $parameter) {
                                    $bodyText=str_replace("{{".($keyp+1)."}}", $parameter['text'], $bodyText);
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
            
           
            $dataForMessage=[
                "contact_id"=>$contact->id,
                "company_id"=>$contact->company_id,
                "value"=>$bodyText,
                "header_image"=>"",
                "header_video"=>"",
                "header_audio"=>"",
                "header_document"=>"",
                "footer_text"=>"",
                "buttons"=>"",
                "header_text"=>$header_text,
                "is_message_by_contact"=>false,
                "is_campign_messages"=>true,
                "status"=>0,
                "created_at"=>now(),
                "scchuduled_at"=>Carbon::now(),
                "components"=>json_encode($request->components),
                "campaign_id"=>$campaign->id,
            ];
           

            //Create a message on the contact
            $message=Message::create($dataForMessage);


            $this->sendCampaignMessageToWhatsApp($message);


            return response()->json(['status'=>'success','message_id'=>$message->id,'message_wamid'=>$message->fb_message_id]);
        }
        
    }

    //Get ot make contact
    public function makeContact($phone,$company)
    {
        $contact=Contact::where('company_id',$company->id)->where('phone',$phone)->first();
        if(!$contact){
            $contact=Contact::create([
                'name'=>$phone,
                'phone'=>$phone,
                'company_id'=>$company->id,
            ]);
        }
        return $contact;
    }

    //Get templates
    public function getTemplates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        if (config('settings.is_demo')) {
            return response()->json([
                'status' => 'error',
                'errors' => "API is disabled in demo"
            ], 400);
        }
        
        
        //Validate token
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token){
            return response()->json(['status'=>'error','message'=>'Invalid token']);
        }else{
            $user=User::findOrFail($token->tokenable_id);
            Auth::login($user);

            //Company
            $company=$this->getCompany();

         
            //Find the template based on the provided id
            $templates=Template::where('company_id',$company->id)->get();

            return response()->json(['status'=>'success','templates'=>$templates]);

        }
    }
   

    public function info()  
    {
        $token = PersonalAccessToken::where('tokenable_id',auth()->user()->id)->where('tokenable_type','App\Models\User')->first();
        $company= $this->getCompany();

        if(!$token||$company->getConfig('whatsapp_webhook_verified','no')!='yes' ||$company->getConfig('whatsapp_settings_done','no')!='yes'){
            return redirect(route('whatsapp.setup'));
         }

       
        //Get old config
        $planText=$company->getConfig('plain_token','');
        
        return view('wpbox::api.info',['token'=>$planText,'company'=>$company]);
    }


    public function getGroups(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        if (config('settings.is_demo')) {
            return response()->json([
                'status' => 'error',
                'errors' => "API is disabled in demo"
            ], 400);
        }
        
        
        //Validate token
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token){
            return response()->json(['status'=>'error','message'=>'Invalid token']);
        }else{
            $user=User::findOrFail($token->tokenable_id);
            Auth::login($user);

            //Company
            $company=$this->getCompany();

         
            //Find the groups based on the provided id
            if ($request->has('showContacts') && $request->showContacts == "yes") {
                $groups = Group::where('company_id', $company->id)->with('contacts')->get();
            } else {
                $groups = Group::where('company_id', $company->id)->get();
            }

            return response()->json(['status'=>'success','groups'=>$groups]);

        }
    }

    public function getContacts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }


        if (config('settings.is_demo')) {
            return response()->json([
                'status' => 'error',
                'errors' => "API is disabled in demo"
            ], 400);
        }
        
        
        //Validate token
        $token = PersonalAccessToken::findToken($request->token);
        if(!$token){
            return response()->json(['status'=>'error','message'=>'Invalid token']);
        }else{
            $user=User::findOrFail($token->tokenable_id);
            Auth::login($user);

            //Company
            $company=$this->getCompany();

         
        
            return response()->json(['status'=>'success','contacts'=>Contact::where('company_id',$company->id)->get()]);

        }
    }


     //Send Template     message to phone number
     public function contactApiMake(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'token' => 'required',
             'phone' => 'required'
         ]);
         
         if ($validator->fails()) {
             return response()->json([
                 'status' => 'error',
                 'errors' => $validator->errors()
             ], 400);
         }

         if (config('settings.is_demo')) {
            return response()->json([
                'status' => 'error',
                'errors' => "API is disabled in demo"
            ], 400);
        }
        
         
         //Validate token
         $token = PersonalAccessToken::findToken($request->token);
         if(!$token){
             return response()->json(['status'=>'error','message'=>'Invalid token']);
         }else{
             $user=User::findOrFail($token->tokenable_id);
             Auth::login($user);
 
             //Company
             $company=$this->getCompany();


            $contact=$this->makeContact($request->phone,$company);

            //If request has groups
            if($request->has('groups')){
               // Attaching groups to the contact
                $contact->groups()->attach($request->groups);
            }

            //If request has custom fields
            if($request->has('custom')){
                $contact->fields()->sync([]);
                foreach ($request->custom as $key => $value) {
                    if($value){
                        $contact->fields()->attach($key, ['value' => $value]);
                    }
                }
                
            }
            $contact->update();
            return response()->json(['status'=>'success','contact'=>$contact]);
         }
        }
}