<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;

class ExchangeService {
    private $fromCurrency;
    private $toCurrency;
    private $baseURL;
    private $apiKey;
    private $client;

    public function __construct() {

        //This class will be great if can inject in some manner, but the conversations objects
        //do not support DI and throw this exception: Serialization of 'Closure' is not allowed
        $this->baseURL = env('EXCHANGE_URL');
        $this->apiKey = env('EXCHANGE_KEY');
        //GuzzleHttp\Client
        $this->client = new Client();
    }

    public function convert($fromCurrency, $toCurrency, $amount = 0) {

        $this->fromCurrency = $fromCurrency;
        $this->toCurrency = $toCurrency;

        $response = $this->client->get($this->getEndpoint());
        if ($response->getReasonPhrase() != 'OK') {
            throw new Exception('Whoops. something went wrong...');
        }

        $data = json_decode($response->getBody(), true); // returns an array

        if (! boolval($data['success'])) {
            throw new Exception('Whoops. something went wrong... <br />Our third party API for rates converter has some issues at the moment. Can you try it later.');
        }

        //The API provided by Jobsity, whe I am using the free version doesn't allow me to convert money
        //between rates, so we use the latest API and fetch the rates using `EUR` as base currency
        //the we convert the given amount to `EUR` and the convert to desired currency

        if (! key_exists('rates', $data)) {
            throw new Exception('Whoops. something went wrong... We\'re unable to get the rates from the response.');
        }

        $rates = $data['rates'];

        if (! key_exists($this->fromCurrency, $rates)) {
            throw new Exception('Whoops. something went wrong... We\'re unable to get the '. $this->fromCurrency.' from the response.');
        }

        if (! key_exists($this->toCurrency, $rates)) {
            throw new Exception('Whoops. something went wrong... We\'re unable to get the '. $this->toCurrency.' from the response.');
        }

        //base value always will be EUR.
        $fromCurrency = $rates[$this->fromCurrency];
        $toCurrency = $rates[$this->toCurrency];

        return (($amount / $fromCurrency) * $toCurrency);
    }

    private function getEndpoint() {
        return "{$this->baseURL}/latest?access_key=$this->apiKey&symbols={$this->getSymbols()}&format=1";
    }

    private function getSymbols() {
        return "{$this->fromCurrency},{$this->toCurrency}";
    }
}
