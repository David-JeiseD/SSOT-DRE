<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear Roles
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleEncargado = Role::create(['name' => 'encargado']);
        $roleConsultor = Role::create(['name' => 'consultor']);
 
        // Crear un usuario Administrador
        $adminUser = User::factory()->create([
            'name' => 'Administrador General',
            'email' => 'admin@drehuanuco.gob.pe',
            'password' => bcrypt('admin123'), // Cambia 'password' por una contraseña segura
        ]);
        
        // Asignar el rol de 'admin' al usuario
        $adminUser->assignRole($roleAdmin);
    }
}
