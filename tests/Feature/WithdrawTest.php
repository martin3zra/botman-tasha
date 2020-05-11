<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use App\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WithdrawTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function a_user_can_perform_a_deposit()
    {
        //create a user
        $user = factory(User::class)->create();

        $this->makeDeposit(1000, $user->id, $user->currency);

        $balance =  $this->getBalance($user->id);
        $this->assertGreaterThan(0, $balance);

        $amount = 500;
        $this->assertLessThan($balance, $amount);

        Transaction::createWithdrawFromIncomingMessage([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $user->currency,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $user->currency,
            'type' => 'withdraw',
        ]);
    }

    private function getBalance($userID) {
        //check the balance and assert is equal to deposit amount
        return Transaction::balanceFor($userID);
    }

    private function makeDeposit($amount, $userID, $currency) {

        Transaction::createDepositFromIncomingMessage([
            'user_id' => $userID,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }
}
