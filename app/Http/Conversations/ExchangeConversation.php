<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use App\Transaction;
use GuzzleHttp\Client;
use App\Services\MoneyFormat;
use App\Services\ExchangeService;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Http\Conversations\MenuOptionsConversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class ExchangeConversation extends Conversation {
    protected $loggedUser;
    protected $amount;
    protected $fromCurrency;
    protected $toCurrency;
    protected $currencies;

    public function run()
    {
        $data = $this->bot->driverStorage()->find();
        $this->loggedUser = $data->get('user');
        $dataCurrencies = $this->bot->driverStorage()->find();
        $this->currencies = $dataCurrencies->get('currencies');
        $this->askFromCurrency();
    }

    private function askFromCurrency() {
        $buttons = [];

        foreach ($this->currencies as $currency) {

            $buttons[] = Button::create($currency['code'] . ' - '. $currency['country'])->value($currency['code']);
        }

        $question = Question::create('Choose the currency that your money is at the moment.')
            ->addButtons($buttons);

        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
               $this->fromCurrency = $answer->getValue();
               $this->bot->reply("You choose {$this->fromCurrency} as the currency from you want to convert you money.");
               $this->askToCurrency();
            }
        });
    }

    private function askToCurrency() {
        $buttons = [];

        foreach ($this->currencies as $currency) {
            if ($currency['code'] != $this->fromCurrency) {
                $buttons[] = Button::create($currency['code'] . ' - '. $currency['country'])->value($currency['code']);
            }
        }

        $question = Question::create('Choose the currency that want to convert money into.')
            ->addButtons($buttons);

        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
               $this->toCurrency = $answer->getValue();
               $this->askAmount();
            }
        });
    }

    private function askAmount() {
        $this->bot->typesAndWaits(.5);
        $this->askOption();
    }

    private function askOption() {
        $this->bot->typesAndWaits(.5);
        $this->ask("How much do you want convert from {$this->fromCurrency} to {$this->toCurrency}?", function(Answer $answer) {
            $validator = Validator::make(['amount' => $answer->getValue()], [
                'amount' => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                $this->askRetry();
                return;
            }
            $this->amount = $answer->getValue();
            $this->askConfirmation();
        });
    }

    private function askConfirmation() {

        $question = Question::create('Great, we just need a confirmation of the transaction. Are you sure that want convert '. MoneyFormat::format($this->amount) .$this->fromCurrency .' to ' . $this->toCurrency)
            ->addButtons([
                Button::create('Yes, proceed')->value('yes'),
                Button::create('Nope, allow me make a change')->value('no'),
                Button::create('Cancel deposit ')->value('cancel'),
            ]);

        $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() === 'yes') {
                $this->convertMoney();
            } elseif ($answer->getValue() === 'no') {
                $this->askAmount();
            } else {
                $this->bot->startConversation(new MenuOptionsConversation());
            }
        });
    }

    private function askNextOptions() {
        $question = Question::create("Want try a different amount?")
            ->addButtons([
                Button::create('Yes, try again')->value('yes'),
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

    private function convertMoney() {

        try  {
            $service = new ExchangeService();
            $newAmount = $service->convert($this->fromCurrency, $this->toCurrency, $this->amount);

            Transaction::createExchangeFromIncomingMessage([
                'user_id' => $this->loggedUser['id'],
                'amount' => $newAmount,
                'currency' => $this->toCurrency,
            ]);

            $this->bot->typesAndWaits(.5);
            $this->bot->reply('Congrats! 🎉. The money convertion was successfully, your returned amount is: ' . MoneyFormat::format($newAmount) . $this->toCurrency);
        }catch (Exception $e) {
            $this->bot->reply($e->getMessage);
            return;
        }

    }

}