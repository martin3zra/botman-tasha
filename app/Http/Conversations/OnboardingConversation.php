<?php

namespace App\Http\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;

class OnboardingConversation extends Conversation {
    public function run()
    {
        $this->askSomething();
    }

    private function askSomething() {
        $this->bot->reply('Hi!, Welcome to Tasha Expenser.');

        // $user = $this->bot->getUser();
        // // Access ID
        // $id = $user->getId();
        // $this->bot->reply($id);

    }
}