<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PayseraDepositController extends Controller
{
    public function initiate(Request $request)
    {
        // TODO: Implement Paysera integration
        return response()->json(['message' => 'Paysera integration not yet implemented']);
    }

    public function callback(Request $request)
    {
        // TODO: Implement Paysera callback
        return response()->json(['message' => 'Paysera callback not yet implemented']);
    }
}
