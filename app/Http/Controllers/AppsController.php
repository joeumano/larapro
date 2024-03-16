<?php

namespace App\Http\Controllers;

use Akaunting\Module\Facade as Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use ZipArchive;

class AppsController extends Controller
{
    public function index(): View
    {

        $this->adminOnly();

        //1. Get all available apps
        $appsLink = 'https://gist.githubusercontent.com/dimovdaniel/b1621923f8bb30327a6a53a7d6562216/raw/apps.json';

        $installed = [];
        foreach (Module::all() as $key => $module) {
            array_push($installed, $module->alias);

        }
        $installedAsString = implode(',', $installed);

        //Code
        $response = (new \GuzzleHttp\Client())->get($appsLink);

        $rawApps = [];
        if ($response->getStatusCode() == 200) {
            $rawApps = json_decode($response->getBody());
        }

        //2. Merge info
        foreach ($rawApps as $key => &$app) {
            $app->installed = Module::has($app->alias);
            if ($app->installed) {
                $app->version = Module::get($app->alias)->get('version');
                if ($app->version == '') {
                    $app->version = '1.0';
                }

                //Check if app needs update
                if ($app->latestVersion) {
                    $app->updateAvailable = $app->latestVersion != $app->version.'';
                } else {
                    $app->updateAvailable = false;
                }

            }
            if (! isset($app->category)) {
                $app->category = ['tools'];
            }
        }

        //Filter apps by type
        $apps = [];
        $newRawApps = unserialize(serialize($rawApps));
        foreach ($newRawApps as $key => $app) {
            if (isset($app->rule) && $app->rule) {
                $rules = explode(',', $app->rule);
                $alreadyAdded = false;
                foreach ($rules as $keyrule => $rule) {
                    if (! $alreadyAdded && config('settings.app_code_name', '') == $rule) {
                        $alreadyAdded = true;
                        array_push($apps, $app);
                    }
                }
            } else {
                $alreadyAdded = true;
                array_push($apps, $app);
            }

            //remove
            if ($alreadyAdded && isset($app->rulenot) && $app->rulenot) {
                $alreadyRemoved = false;
                $rulesNot = explode(',', $app->rulenot);
                foreach ($rulesNot as $keyrulnot => $rulenot) {
                    if (! $alreadyRemoved && config('app.'.$rulenot)) {
                        $alreadyRemoved = true;
                        array_pop($apps);
                    }
                }
            }
        }

        //3. Return view
        return view('apps.index', compact('apps'));

    }

    public function remove($alias): RedirectResponse
    {
        if (! auth()->user()->hasRole('admin') || strlen($alias) < 2 || (config('settings.is_demo') || config('settings.is_demo'))) {
            abort(404);
        }
        $destination = Module::get($alias)->getPath();
        if (File::exists($destination)) {
            File::deleteDirectory($destination);

            return redirect()->route('apps.index')->withStatus(__('Removed'));
        } else {
            abort(404);
        }
    }

    public function store(Request $request): RedirectResponse
    {

        $path = $request->appupload->storeAs('appupload', $request->appupload->getClientOriginalName());

        $fullPath = storage_path('app/'.$path);
        $zip = new ZipArchive;

        if ($zip->open($fullPath)) {

            //Modules folder - for plugins
            $destination = public_path('../modules');
            $message = __('App is installed');

            //If it is language pack
            if (strpos($fullPath, '_lang') !== false) {
                $destination = public_path('../resources/lang');
                $message = __('Language pack is installed');
            }else if(strpos($fullPath, '_update') !== false){
                $destination = public_path('../');
                $message = __('Update is installed. Please go to settings.');
            }

            // Extract file
            $zip->extractTo($destination);

            // Close ZipArchive
            $zip->close();

            return redirect()->route('admin.apps.index')->withStatus($message);
        } else {
            return redirect(route('admin.apps.index'))->withError(__('There was an error on app install. Please try manual install'));
        }
    }
}
