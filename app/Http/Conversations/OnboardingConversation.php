<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class OnboardingConversation extends Conversation {
    protected $user;
    public function run()
    {
        $this->sayWelcomeAndAuthenticateIfPossible();
    }

    private function sayWelcomeAndAuthenticateIfPossible() {
        $this->bot->reply('Hi!, Welcome to Tasha Expenser.');
        $this->bot->typesAndWaits(.5);

        $this->askForAction();
    }

    private function askForAction() {
        $this->bot->reply('In order to perform any transaction such as deposit, withdraw or money exchange you must be authenticated in the system.');
        //create question
        $question = Question::create('Do you have account already?')
            ->fallback('Unable to authenticate you. :(')
            ->addButtons([
                Button::create('Yes, I have one')->value('yes'),
                Button::create('Nope')->value('no'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($answer->getText() === 'yes') {
                $this->askForPhoneNumber();
            } else {
                $this->bot->typesAndWaits(1);
                $this->bot->reply('Ok no problem. Just type "register", to create a new account.');
            }
        });
    }

    private function askForPhoneNumber() {
        $this->ask('What is your Phone Number?', function(Answer $answer) {
            $validator = Validator::make(['phone' => $answer->getText()], [
                'phone' => 'required|min:10|max:20'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid phone number. Please try again.');
            }

             $this->user = User::where('phone', $answer->getText())->first();

            if ($this->user) {
                $this->askForPin();
                return;
            }

            //create question
            $question = Question::create('Your credentials doesn\'t match. Want try again.')
                ->fallback('Unable to authenticate you. :(')
                ->addButtons([
                    Button::create('Yes please')->value('yes'),
                    Button::create('Nope')->value('no'),
                ]);

            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() === 'yes') {
                    $this->askForPhoneNumber();
                } else {
                    $this->bot->typesAndWaits(1);
                    $this->bot->reply('Ok no problem. If change your mind, just type "authenticate".');
                }
            });
        });
    }

    private function askForPin() {
        $this->ask('What is your pin?', function(Answer $answer) {
            $validator = Validator::make(['pin' => $answer->getText()], [
                'pin' => 'required|min:4|numeric'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid pin. Please try again.');
            }

            if ($this->user->pin != $answer->getText()) {

                //create question
                $question = Question::create('Your credentials doesn\'t match. Want try again.')
                    ->fallback('Unable to authenticate you. :(')
                    ->addButtons([
                        Button::create('Yes please')->value('yes'),
                        Button::create('Nope')->value('no'),
                    ]);

                $this->ask($question, function (Answer $answer) {
                    if ($answer->getText() === 'yes') {
                        $this->askForPin();
                    } else {
                        $this->bot->typesAndWaits(1);
                        $this->bot->reply('Ok no problem. If change your mind, just type "authenticate".');
                    }
                });

                return ;
            }

            $this->startTransactionConversations();
        });
    }

    private function startRegistrationConversations() {
        $this->bot->startConversation(new SignUpConversation());
    }

    private function startTransactionConversations() {
        // $this->bot->startConversation(new OnboardingConversation());
         $this->bot->reply('Starting a new conversation for performing a transaction.');
    }
}