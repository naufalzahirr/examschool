<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SilapSyncController extends Controller
{
    public function index()
    {
        $endpoint = url('/api/silap/sync');
        $tokenConfigured = filled(env('SILAP_SYNC_TOKEN'));

        return view('silap.index', compact('endpoint', 'tokenConfigured'));
    }
}
