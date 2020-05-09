<?php

use App\Currency;
use Illuminate\Database\Seeder;

class CurrenciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = [
            ['code' => 'USD', 'country' => 'United States'],
            ['code' => 'DOP', 'country' => 'Dominican Republic'],
            ['code' => 'EUR', 'country' => 'France'],
            ['code' => 'ARS', 'country' => 'Argentina'],
            ['code' => 'AWG', 'country' => 'Aruba'],
            ['code' => 'BRL', 'country' => 'Brazil'],
        ];

        for ($i = 1; $i < count($currencies); $i++)
        Currency::create($currencies[$i]);
    }
}
