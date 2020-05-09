<?php

namespace App\Http\Conversations;

use Validator;
use App\User;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;

class SignUpConversation extends Conversation {
    protected $user;
    protected array $info = [];

    public function run()
    {
        $this->info = [];
        $this->info['id'] = $this->bot->getUser()->getId();
        $this->sayWelcomeAndAuthenticateIfPossible();
    }

    private function sayWelcomeAndAuthenticateIfPossible() {
        $this->bot->reply('Wuhu, great to have you on board! 🎉. <br />In order to use me as expense managment you need to create a account.');
        $this->askForFirstName();
    }

    private function askForFirstName() {
        $this->ask('What is your First Name?', function(Answer $answer) {
            $validator = Validator::make(['first_name' => $answer->getText()], [
                'first_name' => 'required|min:2|max:100'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid first name. Please try again.');
            }

           $this->info['first_name'] = $answer->getText();

           $this->askLastName();
        });
    }

     private function askLastName() {
        $this->ask('What is your Last Name?', function(Answer $answer) {
            $validator = Validator::make(['last_name' => $answer->getText()], [
                'last_name' => 'required|min:2|max:100'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid last name. Please try again.');
            }

           $this->info['last_name'] = $answer->getText();

           $this->askPhone();
        });
    }

    private function askPhone() {
        $this->ask('What is your Phone Number?', function(Answer $answer) {
            $validator = Validator::make(['phone' => $answer->getText()], [
                'phone' => 'required|min:10|max:20'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid phone number. Please try again.');
            }

           $this->info['phone'] = $answer->getText();

           $this->askPin();
        });
    }

    private function askPin() {
        $this->ask('What is your security Pin?', function(Answer $answer) {
            $validator = Validator::make(['pin' => $answer->getText()], [
                'pin' => 'required|min:4|max:10'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid security. Please try again.');
            }

           $this->info['pin'] = $answer->getText();

           $this->askCurrency();
        });
    }

    private function askCurrency() {
        $this->ask('What is your Currency?', function(Answer $answer) {
            $validator = Validator::make(['currency' => $answer->getText()], [
                'currency' => 'required|min:3|max:5'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid currency. Please try again.');
            }

           $this->info['currency'] = $answer->getText();

           $this->bot->typesAndWaits(.5);

           User::createFromIncomingMessage($this->info);
           $this->say('Great!');
           $this->startTransactionConversations();
        });
    }

    private function startTransactionConversations() {
        // $this->bot->startConversation(new OnboardingConversation());
         $this->bot->reply('Starting a new conversation for performing a transaction.');
    }
}