<?php

namespace Modules\Wpbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\CompanyScope;
use Modules\Contacts\Models\Contact;
use PDO;

use function Ramsey\Uuid\v1;

class Reply extends Model
{
   // use SoftDeletes;
    
    protected $table = 'replies';
    public $guarded = [];

    public function shouldWeUseIt($receivedMessage,Contact $contact){
        $receivedMessage = " " . strtolower($receivedMessage);
        $shouldWeUseIt = false;

        // Store the value of $this->trigger in a new variable
        $triggerValues = $this->trigger;

        // Convert $triggerValues into an array if it contains commas
        if (strpos($triggerValues, ',') !== false) {
            $triggerValues = explode(',', $triggerValues);
        }

        //Check if we can use this reply
        if (is_array($triggerValues)) {
            foreach ($triggerValues as $trigger) {
                if ($this->type == 2) {
                    // Exact match
                    if ($receivedMessage == " " . $trigger) {
                        $shouldWeUseIt=true;
                        break; // exit the loop once a match is found
                    }
                } else if ($this->type == 3) {
                    // Contains
                    if (stripos($receivedMessage, $trigger) !== false) {
                        $shouldWeUseIt=true;
                        break; // exit the loop once a match is found
                    }
                }
            }
        } else {
            //Doesn't contain commas
            if ($this->type == 2) {
                // Exact match
                if ($receivedMessage == " " . $triggerValues) {
                    $shouldWeUseIt=true;
                }
            } else if ($this->type == 3) {
                // Contains
                if (stripos($receivedMessage, $triggerValues) !== false) {
                    $shouldWeUseIt=true;
                }
            }
        }
        
        //Change message
        if($shouldWeUseIt==true){
            $this->increment('used', 1);
            $this->update();


            //Change the values in the  $this->text
            $pattern = '/{{\s*([^}]+)\s*}}/';
            preg_match_all($pattern, $this->text, $matches);
            $variables = $matches[1];
            foreach ($variables as $key => $variable) {
                if($variable=="name"){
                    $this->text=str_replace("{{".$variable."}}",$contact->name,$this->text);
                }else if($variable=="phone"){
                    $this->text=str_replace("{{".$variable."}}",$contact->phone,$this->text);
                }else{
                    //Field
                    $val=$contact->fields->where('name',$variable)->first()->pivot->value;
                    $this->text=str_replace("{{".$variable."}}",$val,$this->text);
                }
            }

            //Change the values in the  $this->header
            $pattern = '/{{\s*([^}]+)\s*}}/';
            preg_match_all($pattern, $this->header, $matches);
            $variables = $matches[1];
            foreach ($variables as $key => $variable) {
                if($variable=="name"){
                    $this->header=str_replace("{{".$variable."}}",$contact->name,$this->header);
                }else if($variable=="phone"){
                    $this->header=str_replace("{{".$variable."}}",$contact->phone,$this->header);
                }else{
                    //Field
                    $val=$contact->fields->where('name',$variable)->first()->pivot->value;
                    $this->header=str_replace("{{".$variable."}}",$val,$this->header);
                }
            }
            
            
            $contact->sendReply($this);

            return true;
           
        }else{
            return false;
        }

        
    }

    protected static function booted(){
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model){
           $company_id=session('company_id',null);
            if($company_id){
                $model->company_id=$company_id;
            }
        });
    }
}
