<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;


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
        $botman = BotManFactory::create($this->config);

        // Give the bot something to listen for.
        $botman->hears('hello', function (BotMan $bot) {
            $bot->reply('Hello yourself.');
        });

        // Start listening
        $botman->listen();
    }
}
