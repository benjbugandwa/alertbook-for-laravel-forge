<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('alertbook.bootstrap_admin.name');
        $email = config('alertbook.bootstrap_admin.email');
        $password = config('alertbook.bootstrap_admin.password');

        if (! $name || ! $email || ! $password) {
            $this->command?->warn('Superadmin non créé : variables ALERTBOOK_BOOTSTRAP_ADMIN_* absentes.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
                // 'code_province' => 'PROV01',
                'code_province' => null,

                // ou une valeur existante dans provinces
            ]
        );

        $super = Role::where('slug', 'superadmin')->firstOrFail();

        $user->roles()->syncWithoutDetaching([$super->id]);
    }
}
