<?php

namespace Modules\Wpbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Wpbox\Models\Template;
use Modules\Wpbox\Traits\Whatsapp;

class TemplatesController extends Controller
{

    use Whatsapp;
    /**
     * Provide class.
     */
    private $provider = Template::class;

    /**
     * Web RoutePath for the name of the routes.
     */
    private $webroute_path = 'templates.';

    /**
     * View path.
     */
    private $view_path = 'wpbox::templates.';

    /**
     * Parameter name.
     */
    private $parameter_name = 'templates';

    /**
     * Title of this crud.
     */
    private $title = 'template';

    /**
     * Title of this crud in plural.
     */
    private $titlePlural = 'templates';


    public function index()
    {
        $this->authChecker();

        if($this->getCompany()->getConfig('whatsapp_webhook_verified','no')!='yes' || $this->getCompany()->getConfig('whatsapp_settings_done','no')!='yes'){
            return redirect(route('whatsapp.setup'));
         }


        $items=$this->provider::orderBy('id', 'desc');
        if(isset($_GET['name'])&&strlen($_GET['name'])>1){
            $items=$items->where('name',  'like', '%'.$_GET['name'].'%');
        }else{
            //If there are 0 template,and there is no filter, load them
            try {
                $this->loadTemplatesFromWhatsApp();
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        $items=$items->paginate(config('settings.paginate'));
        

        return view($this->view_path.'index', ['setup' => [
           
            'title'=>__('crud.item_managment', ['item'=>__($this->titlePlural)]),
            
            'action_link'=>route($this->webroute_path.'load'),
            'action_name'=>__('crud.load_items', ['item'=>__($this->titlePlural)]),
            'action_link5'=>"https://business.facebook.com/wa/manage/message-templates/",
            'action_name5'=>__('crud.item_managment', ['item'=>__($this->title)]),
            'items'=>$items,
            'item_names'=>$this->titlePlural,
            'webroute_path'=>$this->webroute_path,
            'fields'=>[],
            'custom_table'=>true,
            'parameter_name'=>$this->parameter_name,
            'parameters'=>count($_GET) != 0
        ]]);
    }

    /**
     * Auth checker functin for the crud.
     */
    private function authChecker()
    {
        $this->ownerAndStaffOnly();
    }

    public function loadTemplates(){
        
        if($this->loadTemplatesFromWhatsApp()){
            return redirect()->route($this->webroute_path.'index')->withStatus(__('crud.item_has_been_updated', ['item'=>__($this->titlePlural)]));
            // Process $responseData as needed
        } else {
            // Handle error response
            return redirect()->route($this->webroute_path.'index')->withStatus(__('crud.error', ['error'=>'Error']));
        }
    }
}