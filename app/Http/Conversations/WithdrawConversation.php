<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use App\Transaction;
use App\Services\MoneyFormat;
use App\Services\ExchangeService;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Http\Conversations\MenuOptionsConversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class WithdrawConversation extends Conversation {
    protected $loggedUser;
    protected $amount;
    protected $currency;
    protected $needExchange;

    public function __construct() {
        $this->needExchange = false;
    }

    public function run()
    {
        $data = $this->bot->driverStorage()->find();
        $this->loggedUser = $data->get('user');
        $this->currency = $this->loggedUser['currency'];
        $this->askAmount();
    }

    private function askAmount() {
        $this->bot->typesAndWaits(.5);
        $this->askOption();
    }

    private function askOption() {
        $this->bot->typesAndWaits(.5);
        $this->ask('How much do you want withdraw from your account?', function(Answer $answer) {
            $validator = Validator::make(['amount' => $answer->getValue()], [
                'amount' => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                $this->askRetry();
                return;
            }
            $this->amount = $answer->getValue();
            $this->askCurrency();
        });
    }

    private function askCurrency() {
        $this->needExchange = false;
        $question = Question::create('Your default currency is '.$this->currency.'. Do you want use a different one?')
                ->addButtons([
                    Button::create('Nope')->value('no'),
                    Button::create('Yes, a different one')->value('yes'),
                ]);

        $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() === 'yes') {
                $this->needExchange = true;
                $this->askDifferentCurrency();
            } else {
                $this->askConfirmation();
            }
        });
    }

    private function askDifferentCurrency() {

        $data = $this->bot->driverStorage()->find();
        $currencies = $data->get('currencies');
        $buttons = [];

        foreach ($currencies as $currency) {
            if (! $currency['code'] !=  $this->currency) {
                $buttons[] = Button::create($currency['code'] . ' - '. $currency['country'])->value($currency['code']);
            }
        }

        $question = Question::create("Choose a desire currency for you withdraw.")
            ->addButtons($buttons);

        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
               $this->currency = $answer->getValue();
               $this->askConfirmation();
            }
        });
    }

    private function askConfirmation() {


        $question = Question::create('Great, let\'s confirm the transaction. Are you sure that want to withdraw ' . MoneyFormat::format($this->amount) .' in ' .$this->currency)
            ->addButtons([
                Button::create('Yes, proceed')->value('yes'),
                Button::create('Nope, allow me make a change')->value('no'),
                Button::create('Cancel deposit ')->value('cancel'),
            ]);

        $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() === 'yes') {
                $this->persistWithdraw();
            } elseif ($answer->getValue() === 'no') {
                $this->askAmount();
            } else {
                $this->bot->startConversation(new MenuOptionsConversation());
            }
        });
    }

    private function persistWithdraw() {

        //If the user provide a currency different of the default
        //then we need to perform money exchange
        if($this->needExchange) {
            $this->amount = $this->computedAmount();
            if ($this->amount == 0) {
                return ;
            }
        }

        //check if the user have a balance for the withdraw.
        $balance = $this->getCurrentBalance();
        if ($this->amount > $balance) {
            $this->bot->typesAndWaits(.5);
            $this->bot->reply('Whoops, you don\'t have enough balance in your account for this transaction. <br />Current balance: ' . MoneyFormat::format($balance) . $this->currency);
            $this->askNextOptions();
            return;
        }

        Transaction::createWithdrawFromIncomingMessage([
            'user_id' => $this->loggedUser['id'],
            'amount' => $this->amount,
            'currency' => $this->loggedUser['currency'],
        ]);

        $this->bot->typesAndWaits(.5);
        $this->bot->reply('Congrats! 🎉. Your withdraw was successfully.');
        $this->bot->typesAndWaits(1.5);
        $this->bot->reply('Type "hey" and create another transaction.');
    }

    private function askRetry($option = 'amount') {

        $question = Question::create("That doesn't look like a valid {$option}. Please try again.")
            ->addButtons([
                Button::create('Yes, try again')->value('yes'),
                Button::create('Nope')->value('no'),
            ]);

        $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() === 'amount') {
                $this->askAmount();
            } elseif ($answer->getValue() === 'currency') {
                $this->askCurrency();
            } else {
                $this->bot->startConversation(new MenuOptionsConversation());
            }
        });
    }

    private function askNextOptions() {
        $question = Question::create("Would you like to try a different amount?")
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('Nope')->value('no'),
            ]);

        $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() === 'yes') {
                $this->askAmount();
            } else {
                $this->bot->startConversation(new MenuOptionsConversation());
            }
        });

    }

    private function getCurrentBalance() {
        return Transaction::balanceFor((int)$this->loggedUser['id']);
    }

    private function computedAmount() {
        try {
            $service = new ExchangeService();
            return $service->convert($this->currency, $this->loggedUser['currency'], $this->amount);
        } catch(Exception $e) {
            $this->bot->reply($e->getMessage());
            return 0;
        }
    }

}