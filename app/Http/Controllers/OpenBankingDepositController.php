<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OpenBankingDepositController extends Controller
{
    public function initiate(Request $request)
    {
        // TODO: Implement Open Banking integration
        return response()->json(['message' => 'Open Banking integration not yet implemented']);
    }

    public function callback(Request $request)
    {
        // TODO: Implement Open Banking callback
        return response()->json(['message' => 'Open Banking callback not yet implemented']);
    }
}
