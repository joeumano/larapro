<?php

namespace Modules\Wpbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\CompanyScope;
use Modules\Contacts\Models\Contact;

class Message extends Model
{
   // use SoftDeletes;
    
    protected $table = 'messages';
    public $guarded = [];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
    public function campaign()
    {
        return $this->belongsTo(Campaign::class,'campaign_id','id','wa_campaings');
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
