<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use App\Transaction;
use App\Services\MoneyFormat;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Http\Conversations\ExchangeConversation;
use App\Http\Conversations\WithdrawConversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class MenuOptionsConversation extends Conversation {
    protected $loggedUser;

    public function run()
    {
        $data = $this->bot->driverStorage()->find();
        $this->loggedUser = $data->get('user');
        $this->sayWelcomeAndShowOptionsMenu();
    }

    private function sayWelcomeAndShowOptionsMenu() {
        $this->bot->typesAndWaits(.5);
        $this->askOption();
    }

    private function askOption() {
        $this->bot->typesAndWaits(.5);
        $question = Question::create($this->loggedUser['first_name'].', what kind of transaction would you like to perform?')
            ->addButtons([
                Button::create('Deposit')->value('deposit'),
                Button::create('Withdraw')->value('withdraw'),
                Button::create('Currency conversion')->value('exchange'),
                Button::create('Check balance')->value('balance'),
                Button::create('Sign out')->value('forgetme'),
            ]);

        $this->ask($question, function(Answer $answer) {
            switch ($answer->getValue()) {
                case 'deposit':
                    $this->bot->startConversation(new DepositConversation());
                    break;
                case 'withdraw':
                    $this->bot->startConversation(new WithdrawConversation());
                    break;
                case 'exchange':
                    $this->bot->startConversation(new ExchangeConversation());
                    break;
                case 'balance':
                    $this->sayCurrentBalance();
                    break;
                default:
                    $this->forgetMe();
                    break;
            }
        });
    }

    private function forgetMe() {
        $this->bot->driverStorage()->delete();
        $this->bot->reply('Good bye!');
    }

    private function sayCurrentBalance() {

        $balance = Transaction::balanceFor((int)$this->loggedUser['id']);
        $this->bot->reply('Hey, ' . $this->loggedUser['first_name'] . ' your current balance is: ' . MoneyFormat::format($balance) . $this->loggedUser['currency']);
        $this->bot->reply('If you want to perform another transaction just type "hey"');
    }
}