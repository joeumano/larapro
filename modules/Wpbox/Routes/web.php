<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('wpbox')->group(function() {
    Route::get('/', 'WpboxController@index');
});
    Route::group([
        'middleware' =>[ 'web','impersonate'],
        'namespace' => 'Modules\Wpbox\Http\Controllers'
    ], function () {
        Route::group([
            'middleware' =>[ 'web','auth','impersonate','XssSanitizer','isOwnerOnPro','Modules\Wpbox\Http\Middleware\CheckPlan'],
        ], function () {
            //Chat
            Route::get('chat', 'ChatController@index')->name('chat.index');


            //Setup
            Route::get('whatsapp/setup', 'DashboardController@setup')->name('whatsapp.setup');
            Route::post('whatsapp/setup', 'DashboardController@savesetup')->name('whatsapp.store');


            //Campaigns
            Route::get('campaigns', 'CampaignsController@index')->name('campaigns.index');
            Route::get('campaigns/{campaign}/show', 'CampaignsController@show')->name('campaigns.show');
            Route::get('campaigns/create', 'CampaignsController@create')->name('campaigns.create');
            Route::post('campaigns', 'CampaignsController@store')->name('campaigns.store');
            Route::put('campaigns/{campaign}', 'CampaignsController@update')->name('campaigns.update');
            Route::get('campaigns/del/{campaign}', 'CampaignsController@destroy')->name('campaigns.delete');

            //Templates
            Route::get('templates', 'TemplatesController@index')->name('templates.index');
            Route::get('templates/load', 'TemplatesController@loadTemplates')->name('templates.load');


            //Replies
            Route::get('replies', 'RepliesController@index')->name('replies.index');
            Route::get('replies/{reply}/edit', 'RepliesController@edit')->name('replies.edit');
            Route::get('replies/create', 'RepliesController@create')->name('replies.create');
            Route::post('replies', 'RepliesController@store')->name('replies.store');
            Route::put('replies/{reply}', 'RepliesController@update')->name('replies.update');
            Route::get('replies/del/{reply}', 'RepliesController@destroy')->name('replies.delete');

            //API
            Route::prefix('api/wpbox')->group(function() {
                Route::get('info', 'APIController@info')->name('whatsapi.info');;
                Route::get('chats/{lastmessagetime}', 'ChatController@chatlist');
                Route::get('chat/{contact}', 'ChatController@chatmessages');
                Route::post('send/{contact}', 'ChatController@sendMessageToContact');
                Route::post('sendimage/{contact}', 'ChatController@sendImageMessageToContact');
                Route::post('sendfile/{contact}', 'ChatController@sendDocumentMessageToContact');
                Route::post('assign/{contact}', 'ChatController@assignContact');

            });
        });

        //Webhook
        Route::prefix('webhook/wpbox')->group(function() {
            Route::post('receive/{token}', 'ChatController@receiveMessage');
            Route::get('receive/{tokenViaURL}', 'ChatController@verifyWebhook');
            Route::get('sendschuduledmessages', 'CampaignsController@sendSchuduledMessages');
        });

        //PUBLIC API
        Route::prefix('api/wpbox')->group(function() {
            Route::post('sendtemplatemessage', 'APIController@sendTemplateMessageToPhoneNumber');
            Route::post('sendmessage', 'APIController@sendMessageToPhoneNumber');
            Route::get('getTemplates', 'APIController@getTemplates');
            Route::get('getGroups', 'APIController@getGroups');
            Route::get('getContacts', 'APIController@getContacts');
            Route::post('makeContact', 'APIController@contactApiMake');
            
            
        });

          
  

});
