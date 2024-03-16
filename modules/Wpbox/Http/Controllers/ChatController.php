<?php

namespace Modules\Wpbox\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Modules\Contacts\Models\Contact;
use Modules\Wpbox\Models\Message;
use Illuminate\Support\Facades\Storage;
use Modules\Wpbox\Models\Reply;
use Modules\Wpbox\Models\Template;
use Modules\Wpbox\Traits\Whatsapp;
use Illuminate\Support\Facades\Validator;
use Modules\Wpbox\Events\Chatlistchange;

class ChatController extends Controller
{
    use Whatsapp;

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if($this->getCompany()->getConfig('whatsapp_webhook_verified','no')!='yes' || $this->getCompany()->getConfig('whatsapp_settings_done','no')!='yes'){
            return redirect(route('whatsapp.setup'));
         }
        $templates=Template::where('status','APPROVED')->select('name','id','language')->get();
        $replies = Reply::where('type', 1)->get();
        
        //Find the users of the company
        $users=$this->getCompany()->users()->pluck('name','id');



        return view('wpbox::chat.master',[
            'templates'=>$templates->toArray(),
            'replies'=>$replies->toArray(),
            'users'=>$users->toArray(),

        ]);
    }






    /**
     * API
     */
    public function chatlist($lastmessagetime){
        $shouldWeReturnChats=$lastmessagetime=="none";

        if(!$shouldWeReturnChats){
            //Check for updated chats
            if(Contact::where('has_chat',1)->orderBy('last_reply_at','DESC')->first()->last_reply_at.""==$lastmessagetime){
                //Front end last message, is same as backedn last message time
                $shouldWeReturnChats=false;
            }else{
                $shouldWeReturnChats=true;
            }
        }

        if($shouldWeReturnChats){
            //Return list of contacts that have chat active
            $chatList=Contact::where('has_chat',1)->with(['messages','country'])->orderBy('last_reply_at','DESC')->limit(150)->get();
            return response()->json([
                'data' => $chatList,
                'status' => true,
                'errMsg' => '',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'errMsg' => 'No changes',
            ]);
        }
        
    }

    public function assignContact(Request $request, Contact $contact)
    {
        // Validate the request...
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Assign the contact to the user
        $contact->user_id = $validatedData['user_id'];
        $contact->save();

        event(new Chatlistchange($contact->id,$contact->company_id)); 

        return response()->json([
            'status' => true,
            'message' => 'Contact assigned successfully',
        ]);
    }

    public function chatmessages($contact){
        $messages=Message::where('contact_id',$contact)->where('status','>',0)->orderBy('id','desc')->limit(50)->get();
        return response()->json([
            'data' =>  $messages,
            'status' => true,
            'errMsg' => '',
        ]);
    }

    public function sendMessageToContact(Request $request, Contact $contact){
        /**
         * Contact id
         * Message
         */

        // Create a validator instance
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            $errorsText = $validator->errors()->all();
            // Convert the array of error messages to a single string
            $errorsString = implode("\n", $errorsText);
            return response()->json([
                'status' => false,
                'errMsg' => $errorsString,
            ]);
        }else if(strip_tags($request->message)!=$request->message){
            return response()->json([
                'status' => false,
                'errMsg' => __('Only text is allowed!'),
            ]);
        }else{
            //OK, we can send the message
            $messageSend=$contact->sendMessage(strip_tags($request->message),false);
            return response()->json([
                'message'=> $messageSend,
                'messagetime'=>$messageSend->created_at->format('Y-m-d H:i:s'),
                'status' => true,
                'errMsg' => '',
            ]);
        }



        
    }

    public function sendImageMessageToContact(Request $request, Contact $contact){
        /**
         * Contact id
         * Message
         */
        $imageUrl="";
        if(config('settings.use_s3_as_storage',false)){
            //S3 - store per company
            $path = $request->image->storePublicly('uploads/media/send/'.$contact->company_id,'s3');
            $imageUrl = Storage::disk('s3')->url($path);
        }else{
            //Regular
            $path = $request->image->store(null,'public_media_upload',);
            $imageUrl = Storage::disk('public_media_upload')->url($path);
        }

        $fileType = $request->file('image')->getMimeType();
        if (str_contains($fileType, 'image')) {
            // It's an image
            $messageType = "IMAGE";
        } elseif (str_contains($fileType, 'video')) {
            // It's a video
            $messageType = "VIDEO";
        } elseif (str_contains($fileType, 'audio')) {
            // It's audio
            $messageType = "VIDEO";
        } else {
            // Handle other types or show an error message
            $messageType = "IMAGE";
        }
       
        $messageSend=$contact->sendMessage($imageUrl,false,false,$messageType);
        return response()->json([
            'message'=> $messageSend,
            'messagetime'=>$messageSend->created_at->format('Y-m-d H:i:s'),
            'status' => true,
            'errMsg' => '',
        ]);
    }

    public function sendDocumentMessageToContact(Request $request, Contact $contact){
        /**
         * Contact id
         * Message
         */
        $fileURL="";
        if(config('settings.use_s3_as_storage',false)){
            //S3 - store per company
            $path = $request->file->storePublicly('uploads/media/send/'.$contact->company_id,'s3',);
            $fileURL = Storage::disk('s3')->url($path);
        }else{
            //Regular
            $path = $request->file->store(null,'public_media_upload',);
            $fileURL = Storage::disk('public_media_upload')->url($path);
        }

        $messageSend=$contact->sendMessage($fileURL,false,false,"DOCUMENT");
        return response()->json([
            'message'=> $messageSend,
            'messagetime'=>$messageSend->created_at->format('Y-m-d H:i:s'),
            'status' => true,
            'errMsg' => '',
        ]);
    }
}
