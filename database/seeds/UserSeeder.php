<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name'      => 'news',
            'username'  => 'news',
            'email'     => 'news@gmail.com',
            'password'  => Hash::make('news'),
            'api_token' => Str::random(60),
            'roles'     => 'ADMIN'
        ]);
    }
}
