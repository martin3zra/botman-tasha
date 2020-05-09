<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [
        'id',
    ];

    public static function balanceFor(int $userID) {
        //As we are using sqlite we prefer to compute the current balance
        //making use of laravel collection.
        $records = Transaction::where('user_id', $userID)->get();
        $total = $records->sum(function($trans) {

            if ($trans->type === 'withdraw') {
                return ($trans->amount * -1);
            }

            return $trans->amount;
        });

        return $total;
    }

    public static function createDepositFromIncomingMessage(array $data) {
        Transaction::createFromIncomingMessage($data, 'deposit');
    }

    public static function createWithdrawFromIncomingMessage(array $data) {
        Transaction::createFromIncomingMessage($data, 'withdraw');
    }

    public static function createFromIncomingMessage(array $data, $type = 'deposit') {
        Transaction::create([
            'user_id' => $data['user_id'],
            'type' => $type,
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'date' => now(),
        ]);
    }

}
