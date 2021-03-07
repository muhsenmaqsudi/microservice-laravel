<?php

namespace App\Console\Commands;

use App\Http\Controllers\PostsController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;

class RedisSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:subscriber';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for running redis subscriber';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Redis::connection('pubsub')->subscribe(['laravel', 'get_my_posts'], function ($message) {
            $decodedMsg = json_decode($message, true);
            $this->info(sprintf('Message received: %s', $message));
            switch ($decodedMsg['pattern']) {
                case 'laravel':
                    Redis::publish("{$decodedMsg['pattern']}.reply", json_encode([
                        "response" => "Hi from {$decodedMsg['data']['framework']} framework",
                        "isDisposed" => true,
                        "id" => $decodedMsg['id']
                    ]));
                    break;
                case 'get_my_posts':
                    /** @var PostsController $postController */
                    $postController = resolve(PostsController::class);
                    $postController->myPosts($decodedMsg);
                    break;
            }
        });
    }
}
