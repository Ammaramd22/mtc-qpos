<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up()
    {
        $perms = [
            'capital_account.access',
            'capital_account.manage_partners',
            'capital_account.manage_entries',
        ];

        foreach ($perms as $name) {
            $permission = Permission::where('name', $name)->first();
            if (empty($permission)) {
                Permission::create(['name' => $name]);
            }
        }

        // Give Admin#* roles access
        $roles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($roles as $role) {
            foreach ($perms as $name) {
                try {
                    if (! $role->hasPermissionTo($name)) {
                        $role->givePermissionTo($name);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }
    }

    public function down()
    {
        $perms = [
            'capital_account.access',
            'capital_account.manage_partners',
            'capital_account.manage_entries',
        ];
        foreach ($perms as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
