<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class AsistenciasDesarrolladorSeeder extends Seeder
{
    // ── Configuración ──────────────────────────────────────────────────
    const FECHA_INICIO = '2026-06-01';
    const HORA_ENTRADA = '08:45:00';
    const HORA_SALIDA  = '18:00:00';
    const ROL          = 'desarrollador';

    // Usuarios que NO deben tener asistencia registrada hoy (nombre exacto)
    const EXCLUIR_HOY  = ['Carlo Mayta'];
    // ───────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $usuarios = User::role(self::ROL)->get();

        if ($usuarios->isEmpty()) {
            $this->command->warn('No se encontraron usuarios con el rol "' . self::ROL . '".');
            return;
        }

        $hoy    = Carbon::today();
        $inicio = Carbon::parse(self::FECHA_INICIO);
        $period = CarbonPeriod::create($inicio, $hoy);

        $creados  = 0;
        $omitidos = 0;

        foreach ($usuarios as $usuario) {
            $excluirHoy = in_array($usuario->name, self::EXCLUIR_HOY);
            $this->command->info("Procesando: {$usuario->name}" . ($excluirHoy ? ' (sin registro hoy)' : ''));

            foreach ($period as $fecha) {
                // Saltar domingos
                if ($fecha->dayOfWeek === Carbon::SUNDAY) {
                    continue;
                }

                // Saltar hoy para usuarios excluidos
                if ($excluirHoy && $fecha->isToday()) {
                    $this->command->line("  · {$fecha->format('d/m/Y')} omitido (excluido hoy)");
                    continue;
                }

                $fechaStr = $fecha->format('Y-m-d');

                foreach (['entrada' => self::HORA_ENTRADA, 'salida' => self::HORA_SALIDA] as $tipo => $hora) {
                    $existe = Asistencia::where('user_id', $usuario->id)
                        ->whereDate('fecha', $fechaStr)
                        ->where('tipo', $tipo)
                        ->exists();

                    if ($existe) {
                        $omitidos++;
                        continue;
                    }

                    Asistencia::create([
                        'user_id'           => $usuario->id,
                        'tipo'              => $tipo,
                        'fecha'             => $fechaStr,
                        'hora'              => $hora,
                        'latitud'           => 0,
                        'longitud'          => 0,
                        'distancia_metros'  => 0,
                        'radio_configurado' => 0,
                        'justificada'       => false,
                        'observaciones'     => 'Registro manual administrativo',
                    ]);

                    $creados++;
                }
            }
        }

        $this->command->info("Listo: {$creados} registros creados, {$omitidos} ya existían (omitidos).");
    }
}
