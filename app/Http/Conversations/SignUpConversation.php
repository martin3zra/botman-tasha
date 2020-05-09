<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use App\Currency;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Http\Conversations\MenuOptionsConversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
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

        $buttons = [];
        $currencies = Currency::all();

        foreach ($currencies as $currency) {
            $buttons[] = Button::create($currency->code . ' - '. $currency->country)->value($currency->code);
        }

        $question = Question::create("Choose a desire currency for you.")
            ->addButtons($buttons);

        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->info['currency'] = $answer->getText();
                $this->bot->reply('Your currency is: ' . $answer->getText());
                $this->registerUser();
            }
        });
    }

    private function startTransactionConversations() {
        $this->bot->startConversation(new MenuOptionsConversation());
    }

    private function registerUser() {
        $user = $this->findUser();
        if ($user) {
            $this->bot->reply('Whoops, An account already exists with this phone numer.');
            $this->bot->reply('Type "register" and try again.');
            return;
        }

        User::createFromIncomingMessage($this->info);
        $user = $this->findUser();
        if ($user) {
            $this->bot->driverStorage()->save([
                'user' => $user,
            ]);

            $this->say('Great!');
            $this->startTransactionConversations();
            return ;
        }

        $this->bot->reply('Whoops, something wrong happened.');
    }

    private function findUser() {
        return User::where('phone', $this->info['phone'])->first();
    }
}