<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Helpers\RedbillerLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RedbillerWebhookController extends Controller
{
    public function redBiller3dWebhook(Request $request)
    {
        RedbillerLogger::log("redbiller response", ['request' => $request->all()]);
    }


}
