<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Drivers\DriverManager;
use App\Http\Conversations\AuthConversation;
use App\Http\Conversations\SignUpConversation;
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

        $botman->hears('register', function(BotMan $bot) {
            $bot->startConversation(new SignUpConversation());
        });

        $botman->hears('authenticate', function(BotMan $bot) {
            $bot->startConversation(new AuthConversation());
        });

        $botman->fallback(function(BotMan $bot) {
            $bot->reply('Hey!');
            $bot->typesAndWaits(1);
            $bot->reply('I see those words of yours, but I have no idea what they mean. 🤔');
            $bot->typesAndWaits(1);
            $bot->reply('Alfredo said I need to focus on telling you about the transactions that I can help with. Maybe later he will train me to understand your messages as well. I hope so ☺️');

            //TODO: render buttons with the supported options
        });

        // Start listening
        $botman->listen();
    }
}
