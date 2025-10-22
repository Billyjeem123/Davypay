<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Helpers\EsimLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EsimWebhookController extends Controller
{
    public function processEsim(Request $request){

     EsimLogger::log("Logged Esim", $request->all());

    }
}
