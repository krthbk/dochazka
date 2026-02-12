<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Jana',
                'email' => 'jana@dochazka.cz',
                'location' => 'Nadřízená',
                'is_supervisor' => true,
                'color' => '#EF4444', // red-500
            ],
            [
                'name' => 'Kristi',
                'email' => 'kristi@dochazka.cz',
                'location' => 'Ostrava',
                'color' => '#3B82F6', // blue-500
            ],
            [
                'name' => 'Pája',
                'email' => 'paja@dochazka.cz',
                'location' => 'Ostrava',
                'color' => '#10B981', // green-500
            ],
            [
                'name' => 'Jindra',
                'email' => 'jindra@dochazka.cz',
                'location' => 'Ústí nad Labem',
                'color' => '#F59E0B', // amber-500
            ],
            [
                'name' => 'Míla',
                'email' => 'mila@dochazka.cz',
                'location' => 'Ústí nad Labem',
                'color' => '#8B5CF6', // violet-500
            ],
            [
                'name' => 'Šárka',
                'email' => 'sarka@dochazka.cz',
                'location' => 'Ostrava',
                'color' => '#EC4899', // pink-500
            ],
            [
                'name' => 'Petr',
                'email' => 'petr@dochazka.cz',
                'location' => 'Ústí nad Labem',
                'color' => '#14B8A6', // teal-500
            ],
            [
                'name' => 'František',
                'email' => 'frantisek@dochazka.cz',
                'location' => 'Ostrava',
                'color' => '#F97316', // orange-500
            ],
            [
                'name' => 'Lenka',
                'email' => 'lenka@dochazka.cz',
                'location' => 'Ústí nad Labem',
                'color' => '#06B6D4', // cyan-500
            ],
        ];

        foreach ($users as $userData) {
            User::create([
                ...$userData,
                'password' => Hash::make('password'), // Pro testování
                'email_verified_at' => now(),
            ]);
        }
    }
}