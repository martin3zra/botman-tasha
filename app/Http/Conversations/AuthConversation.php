<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Http\Conversations\MenuOptionsConversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class AuthConversation extends Conversation {

    protected $user;

    public function run()
    {

        $this->sayWelcomeAndAuthenticateIfPossible();
    }

    private function sayWelcomeAndAuthenticateIfPossible() {

        $this->askPhoneNumber();
    }

    private function askPhoneNumber() {
        $this->ask('What is your Phone Number?', function(Answer $answer) {
            $validator = Validator::make(['phone' => $answer->getText()], [
                'phone' => 'required|min:10|max:20'
            ]);

            if ($validator->fails()) {
                return $this->repeat('That doesn\'t look like a valid phone number. Please try again.');
            }

            $this->user = User::where('phone', $answer->getText())->first();

            if ($this->user) {

                $this->bot->reply('Welcome back! '. $this->user->first_name . ' ' . $this->user->last_name);
                $this->askPin();
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
                    $this->askPhoneNumber();
                } else {
                    $this->bot->typesAndWaits(1);
                    $this->bot->reply('Ok no problem. If change your mind, just type "authenticate".');
                }
            });
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
                        $this->askPin();
                    } else {
                        $this->bot->typesAndWaits(1);
                        $this->bot->reply('Ok no problem. If change your mind, just type "authenticate".');
                    }
                });

                return ;
            }

            $this->bot->driverStorage()->save([
                'user' => $this->user,
            ]);

            $this->startTransactionConversations();
        });
    }

    private function startTransactionConversations() {
        $this->bot->startConversation(new MenuOptionsConversation());
    }
}