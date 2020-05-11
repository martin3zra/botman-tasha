<?php

namespace App\Http\Controllers;

use App\Currency;
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
        $this->loadCurrencies($botman);
        // Hears for whisper to start the onboarding process
        $botman->hears('GET_STARTED|hey|start', function(BotMan $bot) {
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

            $bot->reply('type "hey" or "start" or "GET_STARTED" to continue.');
            //TODO: render buttons with the supported options
        });

        // Start listening
        $botman->listen();
    }


    private function loadCurrencies(BotMan $bot) {
        //Cache currencies to avoid loading each time from the database
        //by storing on the driver storage, in this case web driver.
        $currencies = Currency::all();
        $bot->driverStorage()->save([
                'currencies' => $currencies,
            ]);
    }
}
