<?php

namespace Modules\Wpbox\Database\Seeds;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class CompanyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

       
        //Company owner 
        $demoOwnerId=DB::table('users')->insertGetId([
            'name' => 'Company owner',
            'email' =>  'owner@example.com',
            'password' => Hash::make('secret'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'plan_id'=> 2,
        ]);

        //Assign owner role
        $demoOwner=User::find($demoOwnerId);
        $demoOwner->assignRole('owner');

      

        // Pizza
        $lastCompanyId=DB::table('companies')->insertGetId([
            'name'=>'Pizza Restaurant',
            'is_featured'=>1,
            'active'=>1,
            'logo'=>asset('uploads').'/default/wpbox/pizza_logo.png',
            'cover'=>asset('uploads').'/default/wpbox/pizza_hero.png',
            'subdomain'=>'leukapizza',
            'user_id'=>$demoOwnerId,
            'created_at' => now(),
            'updated_at' => now(),
            'address' => '6 Yukon Drive Raeford, NC 28376',
            'phone' => '(530) 625-9694',
            'whatsapp_phone' => '+38971203673',
            'description'=>'italian, pasta, pizza',
            'minimum'=>10
        ]);
        $vendor=Company::find($lastCompanyId);

        Model::reguard();
    }
}
