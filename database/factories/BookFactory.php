<?php

use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\App\Book::class, function (Faker $faker) {
    $authors = \App\Author::all();
    $bookAuthor = $authors->random(1)->first();
    return [
        'title' => ucfirst($faker->sentence($nbWords = 3, $variableNbWords = true)),
        'author_id' => $bookAuthor->id,
        'pub_date' => $faker->dateTimeBetween()->format('Y-m-d'),
        'is_public' => 1
    ];
});
