<?php

namespace Modules\Wpbox\Http\Controllers;


use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Contacts\Models\Group;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\Field;
use Modules\Wpbox\Models\Campaign;
use Modules\Wpbox\Models\Message;
use Modules\Wpbox\Models\Template;
use Modules\Wpbox\Traits\Whatsapp;

class CampaignsController extends Controller
{
    use Whatsapp;

    /**
     * Provide class.
     */
    private $provider = Campaign::class;

    /**
     * Web RoutePath for the name of the routes.
     */
    private $webroute_path = 'campaigns.';

    /**
     * View path.
     */
    private $view_path = 'wpbox::campaigns.';

    /**
     * Parameter name.
     */
    private $parameter_name = 'campaigns';

    /**
     * Title of this crud.
     */
    private $title = 'campaign';

    /**
     * Title of this crud in plural.
     */
    private $titlePlural = 'campaigns';


    public function index()
    {

        $this->authChecker();

        if($this->getCompany()->getConfig('whatsapp_webhook_verified','no')!='yes' || $this->getCompany()->getConfig('whatsapp_settings_done','no')!='yes'){
            return redirect(route('whatsapp.setup'));
         }

        $items = $this->provider::orderBy('id', 'desc')->whereNull('contact_id')->where('is_bot', false);
        if(isset($_GET['name'])&&strlen($_GET['name'])>1){
            $items=$items->where('name',  'like', '%'.$_GET['name'].'%');
        }
        $items=$items->paginate(100);
        

        return view($this->view_path.'index', [ 'total_contacts'=>Contact::count(),
        'setup' => [
           
            'title'=>__('crud.item_managment', ['item'=>__($this->titlePlural)]),
            'iscontent'=>true,
            'action_link'=>route($this->webroute_path.'create'),
            'action_name'=>__('Send new campaign')." 📢",
            'items'=>$items,
            'item_names'=>$this->titlePlural,
            'webroute_path'=>$this->webroute_path,
            'fields'=>[],
            'custom_table'=>true,
            'parameter_name'=>$this->parameter_name,
            'parameters'=>count($_GET) != 0
        ]]);
    }

    public function show(Campaign $campaign){

        //Get countries we have send to
        $contact_ids=$campaign->messages()->select(['contact_id'])->pluck('contact_id')->toArray();
        $countriesCount = DB::table('contacts')
        ->join('countries', 'contacts.country_id', '=', 'countries.id')
        ->selectRaw('count(contacts.id) as number_of_messages, country_id, countries.name, countries.lat, countries.lng')
        ->whereIn('contacts.id',$contact_ids)
        ->groupBy('contacts.country_id')
        ->get()->toArray();
 
        $dataToSend=[ 
            'total_contacts'=>Contact::count(),
            'item'=>$campaign,
        'setup' => [
            'countriesCount'=>$countriesCount,
            'title'=>__('Campaign')." ".$campaign->name,
            'action_link'=>route($this->webroute_path.'index'),
            'action_name'=>__('Back to campaings')." 📢",
            'items'=>$campaign->messages()->paginate(config('settings.paginate')),
            'item_names'=>$this->titlePlural,
            'webroute_path'=>$this->webroute_path,
            'fields'=>[],
            'custom_table'=>true,
            'parameter_name'=>$this->parameter_name,
            'parameters'=>count($_GET) != 0
        ]];

        if($campaign->is_bot){
            $dataToSend['setup']['title']=__('Bot')." ".$campaign->name;
            $dataToSend['setup']['action_name']=__('Back to bots')." 🤖";
            $dataToSend['setup']['action_link']=route('replies.index',['type'=>'bot']);
        }
        
        return view($this->view_path.'show',$dataToSend );
    }

    /**
     * Auth checker function for the crud.
     */
    private function authChecker()
    {
        $this->ownerAndStaffOnly();
    }

    private function componentToVariablesList($template){
        $jsonData = json_decode($template->components, true);

        $variables = [];
        foreach ($jsonData as $item) {

            if($item['type']=="HEADER"&&$item['format']=="TEXT"){
                preg_match_all('/{{(\d+)}}/', $item['text'], $matches);  
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $id) {
                        $exampleValue ="";
                        try {
                            $exampleValue = $item['example']['header_text'][$id - 1];
                        } catch (\Throwable $th) {
                        }
                        $variables['header'][] = ['id' => $id, 'exampleValue' => $exampleValue];
                    }
                }
            }else if($item['type']=="HEADER"&&$item['format']=="DOCUMENT"){
                $variables['document']=true;
            }else if($item['type']=="HEADER"&&$item['format']=="IMAGE"){
                $variables['image']=true;
            }else if($item['type']=="HEADER"&&$item['format']=="VIDEO"){
                $variables['video']=true;
            }else if($item['type']=="BODY"){
                preg_match_all('/{{(\d+)}}/', $item['text'], $matches);  
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $id) {
                        $exampleValue ="";
                        try {
                            $exampleValue = $item['example']['body_text'][0][$id - 1];
                        } catch (\Throwable $th) {
                        }
                        $variables['body'][] = ['id' => $id, 'exampleValue' => $exampleValue];
                    }
                }
            }else if($item['type']=="BUTTONS"){
                foreach ($item['buttons'] as $keyBtn => $button) {
                    if($button['type']=="URL"){
                        preg_match_all('/{{(\d+)}}/', $button['url'], $matches);  
                   
                        if (!empty($matches[1])) {
                        
                            foreach ($matches[1] as $id) {
                                $exampleValue ="";
                                try {
                                    $exampleValue = $button['url'];
                                    $exampleValue = str_replace("{{1}}", "", $exampleValue );
                                } catch (\Throwable $th) {
                                }
                                $variables['buttons'][$id - 1][] = ['id' => $id, 'exampleValue' => $exampleValue,'type'=>$button['type'],'text'=>$button['text']];
                            }
                        }
                    }
                    if($button['type']=="COPY_CODE"){
                        $exampleValue = $button['example'][0];
                        $variables['buttons'][$keyBtn][] = ['id' => $keyBtn, 'exampleValue' => $exampleValue,'type'=>$button['type'],'text'=>$button['text']];
                    }
                    
                }
               
            }
        }
        return $variables;
    }

    public function create(Request $request){
        $templates=[];
        foreach (Template::where('status','APPROVED')->get() as $key => $template) {
            $templates[$template->id]=$template->name." - ".$template->language;
        }
        if(sizeof($templates)==0){
           //If there are 0 template,re-load them
            try {
                $this->loadTemplatesFromWhatsApp();
                foreach (Template::where('status','APPROVED')->get() as $key => $template) {
                    $templates[$template->id]=$template->name." - ".$template->language;
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        
         

        if(sizeof($templates)==0){
            //Redirect to templates
            return redirect()->route('templates.index')->withStatus(__('Please add a template first. Or wait some to be approved'));
        }
    
        $groups=Group::pluck('name','id');
        $groups[0]=__("Send to all contacts");

        $selectedTemplate=null;
        $variables=null;
        if(isset($_GET['template_id'])){
            $selectedTemplate=Template::where('id',$_GET['template_id'])->first();
            $variables=$this->componentToVariablesList($selectedTemplate);
            
        }
        
        $contactFields=[];
        $contactFields[-2]=__('Use manually defined value');
        $contactFields[-1]=__('Contact name');
        $contactFields[0]=__('Contact phone');
        foreach (Field::pluck('name','id') as $key => $value) {
            $contactFields[$key]=$value;
        }
       
        return view($this->view_path.'create', [
            'selectedContacts'=>isset($_GET['group_id'])? ($_GET['group_id'].""=="0"?Contact::count():Group::findOrFail($_GET['group_id'])->contacts->count()):"",
            'selectedTemplate'=>$selectedTemplate,
            'selectedTemplateComponents'=>$selectedTemplate?json_decode($selectedTemplate->components,true):null,  
            'contactFields'=> $contactFields,
            'variables'=>$variables,
            'groups' => $groups,
            'contacts' => Contact::pluck('name','id'),
            'templates' => $templates,
            'isBot' => $request->has('type') && $request->type === 'bot',
        ]);
    }


    public function store(Request $request) {  
        //Create the campaign
        $campaign = $this->provider::create([
            'name'=>$request->has('name') ? $request->name:"template_message_".now(),
            'timestamp_for_delivery'=>$request->has('send_now')?null:$request->send_time,
            'variables'=>$request->has('paramvalues')?json_encode($request->paramvalues):"",
            'variables_match'=>json_encode($request->parammatch),
            'template_id'=>$request->template_id,
            'group_id'=>$request->group_id.""=="0"?null:$request->group_id,
            'contact_id'=>$request->contact_id,
            'total_contacts'=>Contact::count(),
        ]);

        //Check if type is bot
        $isBot=$request->has('type') && $request->type === 'bot';
        if($isBot) {
            $campaign->is_bot = true;
            $campaign->bot_type= $request->reply_type;
            $campaign->trigger= $request->trigger;
            $campaign->save();
        }

        if ($request->hasFile('pdf')) {
            $campaign->media_link = $this->saveDocument(
                "",
                $request->pdf,
            );
            $campaign->update();
        }
        if ($request->hasFile('imageupload')) {
            $campaign->media_link = $this->saveDocument(
                "",
                $request->imageupload,
            );
            $campaign->update();
        }

    

        
         if($isBot) {
            //Bot campaign
            return redirect()->route('replies.index',['type'=>'bot'])->withStatus(__('You have created a new bot.'));
         }else{
            //Regular campaign
            //Make the actual messages
            $campaign->makeMessages($request);

            if($request->has('contact_id')){
                return redirect()->route('chat.index')->withStatus(__('Message will be send shortly. Please note that if new contact, it will not appear in this list until the contact start interacting with you!'));
            }else{
                return redirect()->route($this->webroute_path.'index')->withStatus(__('Campaign is ready to be send'));
            }
         }
        

       
    
    }

   

    public function sendSchuduledMessages(){
        //Find all unsent Messages that are within the timeline
        $messagesToBeSend=Message::where('status',0)->where('scchuduled_at', '<',Carbon::now())->limit(100)->get();
        foreach ( $messagesToBeSend as $key => $message) {
            $this->sendCampaignMessageToWhatsApp($message);
        }

    }
}