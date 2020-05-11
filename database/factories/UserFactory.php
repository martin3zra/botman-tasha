<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use App\Currency;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'code' =>  Str::random(10),
        'first_name' => $faker->name,
        'last_name' => $faker->lastName,
        'phone' => $faker->e164PhoneNumber,
        'pin' => $faker->numberBetween(1000,9000),
        'currency' => factory(App\Currency::class),
    ];
});

$factory->define(Currency::class, function (Faker $faker) {
    return [
        'code' => $faker->currencyCode,
        'country' => Str::random(10),
    ];
});
