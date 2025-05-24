<?php

namespace App\Http\Controllers;

use App\Events\MyEvent;
use Illuminate\Http\Request;

class PusherTestController extends Controller
{
    public function test()
    {
        event(new MyEvent('hello world'));
        return response()->json(['message' => 'Event dispatched successfully']);
    }
} 