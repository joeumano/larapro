<?php

namespace App\Scopes;

use App\Models\Company;

class SetCompanyIdInSession
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $vendor = Company::where('user_id', $event->user->id)->first();
        if ($vendor) {
            session(['company_id' => $vendor->id]);
            session(['company_currency' => $vendor->currency]);
            session(['company_convertion' => $vendor->do_covertion]);
        }

    }
}
