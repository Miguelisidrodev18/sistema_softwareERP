<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuotePayment;

class QuotePaymentController extends Controller
{
    // ── Generar plan por defecto (40/30/30) ──────────────────────────
    public function generarPlan(Quote $cotizacion)
    {
        $cotizacion->generarPlanDefault();
        return back()->with('success', 'Plan de cobros generado: 40% anticipo + 30% + 30%.');
    }

    // ── Crear cuota personalizada ────────────────────────────────────
    public function store(Quote $cotizacion)
    {
        $data = request()->validate([
            'nombre'            => ['required', 'string', 'max:150'],
            'porcentaje'        => ['required', 'numeric', 'min:0.01', 'max:100'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'notas'             => ['nullable', 'string'],
        ]);

        $data['monto'] = round((float) $cotizacion->total * $data['porcentaje'] / 100, 2);
        $data['orden'] = $cotizacion->payments()->max('orden') + 1;
        $data['estado'] = 'pendiente';

        $cotizacion->payments()->create($data);

        return back()->with('success', 'Cuota agregada.');
    }

    // ── Editar cuota ─────────────────────────────────────────────────
    public function update(Quote $cotizacion, QuotePayment $pago)
    {
        $data = request()->validate([
            'nombre'            => ['required', 'string', 'max:150'],
            'porcentaje'        => ['required', 'numeric', 'min:0.01', 'max:100'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'notas'             => ['nullable', 'string'],
        ]);

        $data['monto'] = round((float) $cotizacion->total * $data['porcentaje'] / 100, 2);
        $pago->update($data);

        return back()->with('success', 'Cuota actualizada.');
    }

    // ── Marcar como pagado ────────────────────────────────────────────
    public function marcarPagado(Quote $cotizacion, QuotePayment $pago)
    {
        $data = request()->validate([
            'metodo_pago' => ['nullable', 'string', 'max:80'],
            'fecha_pago'  => ['nullable', 'date'],
            'notas'       => ['nullable', 'string'],
        ]);

        $pago->update([
            'estado'      => 'pagada',
            'fecha_pago'  => $data['fecha_pago'] ? $data['fecha_pago'] : now(),
            'metodo_pago' => $data['metodo_pago'],
            'notas'       => $data['notas'] ?? $pago->notas,
        ]);

        // Si todas las cuotas están pagadas, actualizar cotización
        if ($cotizacion->payments()->where('estado', '!=', 'pagada')->doesntExist()) {
            $cotizacion->update(['status' => 'facturado']);
        }

        return back()->with('success', 'Cuota marcada como pagada.');
    }

    // ── Desmarcar pago ────────────────────────────────────────────────
    public function desmarcarPagado(Quote $cotizacion, QuotePayment $pago)
    {
        $pago->update([
            'estado'     => 'pendiente',
            'fecha_pago' => null,
        ]);
        return back()->with('success', 'Pago revertido a pendiente.');
    }

    // ── Eliminar cuota ────────────────────────────────────────────────
    public function destroy(Quote $cotizacion, QuotePayment $pago)
    {
        $pago->delete();
        return back()->with('success', 'Cuota eliminada.');
    }
}
