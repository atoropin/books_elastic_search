<?php

use Illuminate\Database\Seeder;

class PagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $books = \App\Book::all();

        foreach ($books as $book) {
            for ($i = 1; $i <= rand(25, 50); $i++) {
                factory(App\Page::class, 1)->create(['book_id' => $book->id, 'number' => $i])->make();
            }
        }
    }
}
