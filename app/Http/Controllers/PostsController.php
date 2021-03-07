<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class PostsController extends Controller
{
    public function myPosts($decodedMsg)
    {
        Redis::publish("{$decodedMsg['pattern']}.reply", json_encode([
            "response" => "this is your posts",
            "isDisposed" => true,
            "id" => $decodedMsg['id']
        ]));
    }
}
