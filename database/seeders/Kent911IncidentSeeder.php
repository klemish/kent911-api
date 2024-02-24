<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Kent911IncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
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