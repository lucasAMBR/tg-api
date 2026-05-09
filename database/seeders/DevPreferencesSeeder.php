<?php

namespace Database\Seeders;

use App\Models\DevProfile;
use App\Models\RecommendationPreference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevPreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devs = DevProfile::all();

        foreach($devs as $dev)
        {
            $preferences = $dev->recommendation_preference;

            if(! $preferences){
                $new_preference = RecommendationPreference::create([
                    'dev_profile_id' => $dev->id
                ]);
            }
        }
    }
}
