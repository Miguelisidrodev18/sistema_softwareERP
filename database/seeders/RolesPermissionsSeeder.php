<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Clientes
            'clientes.ver',
            'clientes.crear',
            'clientes.editar',
            'clientes.eliminar',

            // Proyectos
            'proyectos.ver',
            'proyectos.crear',
            'proyectos.editar',
            'proyectos.eliminar',
            'proyectos.ver_asignados',

            // Requerimientos
            'requerimientos.ver',
            'requerimientos.crear',
            'requerimientos.editar',

            // Cotizaciones
            'cotizaciones.ver',
            'cotizaciones.crear',
            'cotizaciones.editar',
            'cotizaciones.eliminar',
            'cotizaciones.aprobar',
            'cotizaciones.pdf',

            // Facturación SUNAT
            'facturacion.ver',
            'facturacion.emitir',
            'facturacion.anular',

            // Caja
            'caja.ver',
            'caja.crear',
            'caja.editar',
            'caja.eliminar',

            // Entregas de proyectos
            'entregas.ver',
            'entregas.crear',
            'entregas.editar',
            'entregas.eliminar',

            // Reportes
            'reportes.ver',
            'reportes.exportar',

            // Configuración
            'configuracion.ver',
            'configuracion.editar',

            // Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            // Sprints / Scrum
            'sprints.ver',       // ver boards, sprint list, daily reports del equipo
            'sprints.gestionar', // crear, editar, activar, cerrar sprints
            'sprints.daily',     // registrar daily standup propio
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // --- Roles ---

        $superAdmin     = Role::firstOrCreate(['name' => 'super-admin',    'guard_name' => 'web']);
        $administrativo = Role::firstOrCreate(['name' => 'administrativo', 'guard_name' => 'web']);
        $ventas         = Role::firstOrCreate(['name' => 'ventas',         'guard_name' => 'web']);
        $desarrollador  = Role::firstOrCreate(['name' => 'desarrollador',  'guard_name' => 'web']);
        $practicante    = Role::firstOrCreate(['name' => 'practicante',    'guard_name' => 'web']);

        // Super admin: todos los permisos
        $superAdmin->syncPermissions(Permission::all());

        $superAdmin->syncPermissions(Permission::all());

        $administrativo->syncPermissions([
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
            'proyectos.ver', 'proyectos.crear', 'proyectos.editar', 'proyectos.eliminar',
            'requerimientos.ver', 'requerimientos.crear', 'requerimientos.editar',
            'cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar',
            'cotizaciones.eliminar', 'cotizaciones.aprobar', 'cotizaciones.pdf',
            'facturacion.ver', 'facturacion.emitir', 'facturacion.anular',
            'caja.ver', 'caja.crear', 'caja.editar', 'caja.eliminar',
            'entregas.ver', 'entregas.crear', 'entregas.editar', 'entregas.eliminar',
            'reportes.ver', 'reportes.exportar',
            'configuracion.ver',
            'usuarios.ver',
            'sprints.ver', 'sprints.gestionar', 'sprints.daily',
        ]);

        $ventas->syncPermissions([
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'proyectos.ver',
            'cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar',
            'cotizaciones.aprobar', 'cotizaciones.pdf',
            'entregas.ver',
            'reportes.ver',
        ]);

        $desarrollador->syncPermissions([
            'proyectos.ver_asignados',
            'requerimientos.ver', 'requerimientos.crear', 'requerimientos.editar',
            'sprints.ver', 'sprints.daily',
        ]);

        $practicante->syncPermissions([
            'proyectos.ver_asignados',
            'requerimientos.ver', 'requerimientos.editar',
            'sprints.ver', 'sprints.daily',
        ]);

        $this->command->info('Roles y permisos creados correctamente.');
        $this->command->table(
            ['Rol', 'Permisos'],
            [
                ['super-admin',    Permission::count() . ' (todos)'],
                ['administrativo', $administrativo->permissions()->count()],
                ['ventas',         $ventas->permissions()->count()],
                ['desarrollador',  $desarrollador->permissions()->count()],
                ['practicante',    $practicante->permissions()->count()],
            ]
        );
    }
}
