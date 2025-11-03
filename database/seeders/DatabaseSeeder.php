<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin (gizli sistem yöneticisi)
        $admin = User::firstOrCreate(
            ['email' => 'admin@collabspace.local'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('admin123'), // dev only
                'is_admin' => true,
            ]
        );

        // Normal kullanıcı
        $user = User::firstOrCreate(
            ['email' => 'berke@collabspace.local'],
            [
                'name' => 'Berke',
                'password' => Hash::make('collabpass'),
                'is_admin' => false,
            ]
        );

        // Örnek takım: Owner = $user
        if (! Team::where('name','My First Team')->exists()) {
            $team = Team::create([
                'name' => 'My First Team',
                'owner_id' => $user->id,
            ]);

            $team->users()->attach($user->id, ['role' => TeamRole::OWNER->value]);

            // Bir manager ve bir member ekleyelim
            $manager = User::firstOrCreate(
                ['email' => 'manager@collabspace.local'],
                [
                    'name' => 'Team Manager',
                    'password' => Hash::make('collabpass'),
                ]
            );
            $member = User::firstOrCreate(
                ['email' => 'member@collabspace.local'],
                [
                    'name' => 'Team Member',
                    'password' => Hash::make('collabpass'),
                ]
            );

            $team->users()->attach($manager->id, ['role' => TeamRole::MANAGER->value]);
            $team->users()->attach($member->id, ['role' => TeamRole::MEMBER->value]);
        }
    }
}
