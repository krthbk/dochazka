<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('team_members')->insert([
            ['name' => 'Jana'],
            ['name' => 'František'],
            ['name' => 'Pavla'],
            ['name' => 'Kristina'],
            ['name' => 'Petr'],
            ['name' => 'Lenka'],
            ['name' => 'Šárka'],
            ['name' => 'Jindra'],
            ['name' => 'Míla'],
        ]);
    }
}
