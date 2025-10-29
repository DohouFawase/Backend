<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'description' => 'Administrator'
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user'
        ], [
            'description' => 'Regular user'
        ]);

        // If a user with id=1 exists, assign admin role
        $u = User::find(1);
        if ($u) {
            // assign by uuid
            $u->role_id = $admin->id;
            $u->save();
        }
    }
}
