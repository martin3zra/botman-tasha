<?php

namespace App\Http\Conversations;

use App\User;
use Validator;
use App\Currency;
use App\Transaction;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Http\Conversations\MenuOptionsConversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class DepositConversation extends Conversation {
    protected $loggedUser;
    protected $amount;
    protected $currency;

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
        $this->ask('How much do you want put in your account?', function(Answer $answer) {
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
        $question = Question::create('Your default currency is '.$this->currency.'. Do you want use a different one?')
                ->addButtons([
                    Button::create('Nope')->value('no'),
                    Button::create('Yes, a different one')->value('yes'),
                ]);

        $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() === 'yes') {
                $this->askDifferentCurrency();
            } else {
                $this->askConfirmation();
            }
        });
    }

    private function askDifferentCurrency() {

        $buttons = [];
        $currencies = Currency::all();

        foreach ($currencies as $currency) {
            $buttons[] = Button::create($currency->code . ' - '. $currency->country)->value($currency->code);
        }

        $question = Question::create("Choose a desire currency for you deposit.")
            ->addButtons($buttons);

        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
               $this->currency = $answer->getValue();
               $this->askConfirmation();
            }
        });
    }

    private function askConfirmation() {


        $question = Question::create("Great, we just need a confirmation of the transaction. Are you sure that want deposit {$this->amount} in {$this->currency}")
            ->addButtons([
                Button::create('Yes, proceed')->value('yes'),
                Button::create('Nope, allow me make a change')->value('no'),
                Button::create('Cancel deposit ')->value('cancel'),
            ]);

        $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() === 'yes') {
                $this->persistDeposit();
            } elseif ($answer->getValue() === 'no') {
                $this->askAmount();
            } else {
                $this->bot->startConversation(new MenuOptionsConversation());
            }
        });
    }

    private function persistDeposit() {

        Transaction::createDepositFromIncomingMessage([
            'user_id' => $this->loggedUser['id'],
            'amount' => $this->amount,
            'currency' => $this->currency,
        ]);

        $this->bot->typesAndWaits(.5);
        $this->bot->reply('Congrats! 🎉. Your deposit was successfully.');
        $this->bot->typesAndWaits(1.5);
        $this->bot->startConversation(new MenuOptionsConversation());
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
}