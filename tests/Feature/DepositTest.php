<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use App\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepositTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function a_user_can_perform_a_deposit()
    {
        //create a user
        $user = factory(User::class)->create();
        $this->assertDatabaseHas('users', [
            'phone' => $user->phone,
        ]);

        //pass throught the required data
        $amount = 500;
        Transaction::createDepositFromIncomingMessage([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $user->currency,
        ]);

        //assert that this data is in the database
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $user->currency,
        ]);

        //check the balance and assert is equal to deposit amount
        $balance = Transaction::balanceFor($user->id);

        $this->assertEquals($balance, $amount);
    }
}
