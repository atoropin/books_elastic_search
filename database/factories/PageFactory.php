<?php

use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\App\Page::class, function (Faker $faker) {

    return [
        'text' => $faker->text($maxNbChars = 255),
        'is_public' => 1
    ];
});
