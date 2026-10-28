<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up()
    {
        $name = 'vehicle_modification.view_history';
        if (! Permission::where('name', $name)->exists()) {
            Permission::create(['name' => $name]);
        }

        $roles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($roles as $role) {
            try {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down()
    {
        Permission::where('name', 'vehicle_modification.view_history')->delete();
    }
};
