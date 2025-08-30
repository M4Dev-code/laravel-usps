<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::prefix('webhooks/usps')->group(function () {
    Route::post('/tracking', function (Request $request) {
        // Example: USPS (or a proxy service) posts tracking updates here.
        // You’d typically dispatch a job to process it.
        \Log::info('USPS webhook received', $request->all());

        return response()->json(['ok' => true]);
    });
});
