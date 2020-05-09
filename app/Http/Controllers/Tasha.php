<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Cache\LaravelCache;
use App\Http\Conversations\OnboardingConversation;

class Tasha extends Controller
{
    protected $config = [
        // Your driver-specific configuration
    ];

    public function __invoke()
    {

        // Load the driver(s) you want to use
        DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

        // Create an instance
        $botman = BotManFactory::create($this->config, new LaravelCache());

        // Hears for whisper to start the onboarding process
        $botman->hears('GET_STARTED', function(BotMan $bot) {
            $bot->startConversation(new OnboardingConversation());
        });

        // Start listening
        $botman->listen();
    }
}
