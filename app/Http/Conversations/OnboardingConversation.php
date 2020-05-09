<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use BotMan\BotMan\Messages\Incoming\Answer;
use App\Http\Conversations\AuthConversation;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Http\Conversations\MenuOptionsConversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class OnboardingConversation extends Conversation {

    protected $user;
    protected $loggedUser = [];

    public function run()
    {

        $data = $this->bot->driverStorage()->find();
        $this->loggedUser = $data->get('user');
        $this->sayWelcome();
    }

    private function sayWelcome() {

        if (! is_null($this->loggedUser)) {
            $this->bot->reply('Hi!, Welcome to back Tasha Expenser, ' . $this->loggedUser['first_name']);
            $this->bot->typesAndWaits(.5);
            $this->startTransactionConversations();
            return ;
        }

        $this->sayWelcomeAndAuthenticateIfPossible();
    }

    private function sayWelcomeAndAuthenticateIfPossible() {
        $this->bot->reply('Hi!, Welcome to Tasha Expenser.');
        $this->bot->typesAndWaits(.5);

        $this->askForAction();
    }

    private function askForAction() {

        //create question
        $question = Question::create('In order to perform any transaction such as deposit, withdraw or money exchange you must be authenticated in the system. Do you have account already?')
            ->fallback('Unable to authenticate you. :(')
            ->addButtons([
                Button::create('Yes, I have one')->value('yes'),
                Button::create('Nope')->value('no'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($answer->getText() === 'yes') {
                $this->startAuthConversations();
            } else {
                $this->bot->typesAndWaits(1);
                $this->bot->reply('Ok no problem. Just type "register", to create a new account.');
            }
        });
    }

    private function startAuthConversations() {
        $this->bot->startConversation(new AuthConversation());
    }

    private function startRegistrationConversations() {
        $this->bot->startConversation(new SignUpConversation());
    }

    private function startTransactionConversations() {
        $this->bot->startConversation(new MenuOptionsConversation());
    }
}