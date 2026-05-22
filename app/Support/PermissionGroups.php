<?php

namespace App\Support;

class PermissionGroups
{
    // Agrupación de permisos por módulo para la UI de roles/usuarios
    public static function grupos(): array
    {
        return [
            'Clientes'       => ['clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar'],
            'Proyectos'      => ['proyectos.ver', 'proyectos.ver_asignados', 'proyectos.crear', 'proyectos.editar', 'proyectos.eliminar'],
            'Requerimientos' => ['requerimientos.ver', 'requerimientos.crear', 'requerimientos.editar'],
            'Sprints'        => ['sprints.ver', 'sprints.gestionar', 'sprints.daily'],
            'Cotizaciones'   => ['cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar', 'cotizaciones.eliminar', 'cotizaciones.aprobar', 'cotizaciones.pdf'],
            'Facturación'    => ['facturacion.ver', 'facturacion.emitir', 'facturacion.anular'],
            'Caja'           => ['caja.ver', 'caja.crear', 'caja.editar', 'caja.eliminar'],
            'Entregas'       => ['entregas.ver', 'entregas.crear', 'entregas.editar', 'entregas.eliminar'],
            'Reportes'       => ['reportes.ver', 'reportes.exportar'],
            'Configuración'  => ['configuracion.ver', 'configuracion.editar'],
            'Usuarios'       => ['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar'],
        ];
    }

    // Etiqueta legible de cada permiso
    public static function etiquetas(): array
    {
        return [
            'ver'           => 'Ver',
            'crear'         => 'Crear',
            'editar'        => 'Editar',
            'eliminar'      => 'Eliminar',
            'ver_asignados' => 'Ver asignados',
            'aprobar'       => 'Aprobar',
            'pdf'           => 'Descargar PDF',
            'emitir'        => 'Emitir',
            'anular'        => 'Anular',
            'registrar'     => 'Registrar',
            'exportar'      => 'Exportar',
            'gestionar'     => 'Gestionar',
            'daily'         => 'Daily report',
        ];
    }

    public static function etiqueta(string $permiso): string
    {
        $accion = last(explode('.', $permiso));
        return self::etiquetas()[$accion] ?? ucfirst($accion);
    }
}
