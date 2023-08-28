<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            User::factory()->count(10)->create()->each(function ($user) {
                $user->profile()->create([
                    'country' => fake()->country(),
                    'phone_number' => fake()->phoneNumber(),
                    'invite_link' => fake()->uuid(),
                    'token' => fake()->iosMobileToken(),
                    'bio' => fake()->sentence()
                ]);

                $user->profession()->create([
                    'profession' => fake()->word(),
                    'linkedin' => fake()->url(),
                    'twitter' => fake()->url(),
                    'facebook' => fake()->url(),
                    'instagram' => fake()->url(),
                    'youtube' => fake()->url(),
                    'website' => fake()->url(),
                ]);

                $business = $user->businesses()->create([
                    'name' => fake()->name(),
                    'description' => fake()->text(),
                    'tagline' => fake()->word(),
                    'industry_id' => 1,
                    'specialize' => fake()->word(),
                    'country' => fake()->country()
                ]);

                $business->businessProfile()->create([
                    'logo' => fake()->image(),
                    'linkedin' => fake()->url(),
                    'twitter' => fake()->url(),
                    'facebook' => fake()->url(),
                    'instagram' => fake()->url(),
                    'youtube' => fake()->url(),
                    'website' => fake()->url(),
                ]);

                $event = $user->events()->create([
                    'name' => fake()->name(),
                    'description' => fake()->text(),
                    'location' => fake()->address(),
                    'industry_id' => 1,
                    'specialize' => fake()->word(),
                    'type' => fake()->word(),
                    'country' => fake()->country(),
                ]);

                $event->eventProfile()->create([
                    'logo' => fake()->image(),
                    'linkedin' => fake()->url(),
                    'twitter' => fake()->url(),
                    'facebook' => fake()->url(),
                    'instagram' => fake()->url(),
                    'youtube' => fake()->url(),
                    'website' => fake()->url(),
                ]);

                $project = $user->projects()->create([
                    'name' => fake()->name(),
                    'description' => fake()->text(),
                    'industry_id' => 1,
                    'specialize' => fake()->word(),
                    'country' => fake()->country(),
                ]);

                $project->projectWorkspaceSponsors()->create([
                    'sponsors' => fake()->name()
                ]);

                $project->projectProfile()->create([
                    'logo' => fake()->image(),
                    'linkedin' => fake()->url(),
                    'twitter' => fake()->url(),
                    'facebook' => fake()->url(),
                    'instagram' => fake()->url(),
                    'youtube' => fake()->url(),
                    'website' => fake()->url(),
                ]);
            });
        });
    }
}
