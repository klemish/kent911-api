<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        $now = \Carbon\Carbon::now();
        $job_names = [
            'kent911',
            'grpd',
            'grfd',
        ];

        foreach ($job_names as $job) {
            DB::table('job_last_seen')->insert([
                'job_name' => $job,
                'last_seen' => $now,
            ]);
        }
    }
}
