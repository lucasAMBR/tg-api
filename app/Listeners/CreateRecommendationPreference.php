<?php

namespace App\Listeners;

use App\Events\DevProfileCreated;
use App\Models\RecommendationPreference;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateRecommendationPreference
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DevProfileCreated $event): void
    {
        $dev_profile = $event->dev_profile;

        if($dev_profile->recommendation_preference){

        }else{
            RecommendationPreference::create([
                'dev_profile_id' => $event->dev_profile->id
            ]);
        }
    }
}
