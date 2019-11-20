<?php

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LaratrustSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return  void
     */
    public function run()
    {
        $this->command->info('Truncating User, Role and Permission tables');
        $this->truncateLaratrustTables();

        $config = config('laratrust_seeder.role_structure');
        $userPermission = config('laratrust_seeder.permission_structure');
        $mapPermission = collect(config('laratrust_seeder.permissions_map'));

        foreach ($config as $key => $modules) {
            $role = \App\Models\Role::create([
                'name' => $key,
                'display_name' => ucwords(str_replace('_', ' ', $key)),
                'description' => ucwords(str_replace('_', ' ', $key))
            ]);
            $permissions = [];

            $this->command->info('Creating Role '. strtoupper($key));

            foreach ($modules as $module => $value) {
                foreach (explode(',', $value) as $p => $perm) {
                    $permissionValue = $mapPermission->get($perm);
                    $permissions[] = \App\Models\Permission::firstOrCreate([
                        'name' => $permissionValue . '-' . $module,
                        'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                        'description' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                    ])->id;

                    $this->command->info('Creating Permission to '.$permissionValue.' for '. $module);
                }
            }

            $role->permissions()->sync($permissions);
            $this->command->info("Creating '{$key}' user");

            $user = \App\Models\User::create([
                'name' => ucwords(str_replace('_', ' ', $key)),
                'email' => $key.'@'.strtolower(env('APP_NAME')).'.com',
                'password' => '123456',
            ]);

            $user->attachRole($role);
        }

        if (!empty($userPermission)) {
            foreach ($userPermission as $key => $modules) {
                foreach ($modules as $module => $value) {
                    $user = \App\Models\User::create([
                        'name' => ucwords(str_replace('_', ' ', $key)),
                        'email' => $key.'@'.strtolower(env('APP_NAME')).'.com',
                        'password' => '123456',
                        'remember_token' => Str::random(10),
                    ]);
                    $permissions = [];

                    foreach(explode(',', $value) as $p => $perm){
                        $permissionValue = $mapPermission->get($perm);
                        $permissions[] = \App\Models\Permission::firstOrCreate([
                            'name' => $permissionValue . '-' . $module,
                            'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                            'description' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                        ])->id;

                        $this->command->info('Creating Permission to '.$permissionValue.' for '. $module);
                    }
                }

                $user->permissions()->sync($permissions);
            }
        }
    }

    /**
     * Truncates all the laratrust tables and the users table
     *
     * @return    void
     */
    public function truncateLaratrustTables()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('permission_role')->truncate();
        DB::table('permission_user')->truncate();
        DB::table('role_user')->truncate();
        \App\Models\User::truncate();
        \App\Models\Role::truncate();
        \App\Models\Permission::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
