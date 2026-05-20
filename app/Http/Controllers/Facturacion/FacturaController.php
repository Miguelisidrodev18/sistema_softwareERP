<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facturacion\StoreFacturaRequest;
use App\Models\Client;
use App\Models\EmpresaConfig;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Services\SunatService;
use Illuminate\Support\Facades\DB;

class FacturaController extends Controller
{
    public function __construct(private SunatService $sunat) {}

    public function index()
    {
        $apiOk = $this->sunat->estaConfigurada();
        return view('facturacion.index', compact('apiOk'));
    }

    public function create()
    {
        $clientes = Client::activos()->orderBy('razon_social')
            ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social', 'nombre_comercial', 'direccion', 'ubigeo', 'email', 'telefono']);

        $cotizaciones = Quote::where('status', 'aceptado')
            ->whereNull('invoices') // no facturadas aún
            ->with('client')
            ->orderByDesc('created_at')
            ->get(['id', 'numero', 'client_id', 'total', 'moneda'])
            ->filter(fn($q) => !Invoice::where('quote_id', $q->id)->exists());

        $config  = EmpresaConfig::first();
        $igvPct  = $config?->igv_porcentaje ?? 18;
        $apiOk   = $this->sunat->estaConfigurada();

        return view('facturacion.create', compact('clientes', 'cotizaciones', 'config', 'igvPct', 'apiOk'));
    }

    public function store(StoreFacturaRequest $request)
    {
        $data   = $request->validated();
        $config = EmpresaConfig::first();
        $igvPct = $config?->igv_porcentaje ?? 18;
        $esFactura = $data['tipo_comprobante'] === Invoice::TIPO_FACTURA;

        // Calcular totales
        $subtotal = 0;
        $igvTotal = 0;
        $itemsCalc = [];

        foreach ($data['items'] as $i => $item) {
            $sub = round((float)$item['cantidad'] * (float)$item['precio_unitario'], 2);
            $igv = $item['tipo_afectacion'] === '10' ? round($sub * $igvPct / 100, 2) : 0;
            $subtotal += $sub;
            $igvTotal += $igv;
            $itemsCalc[] = array_merge($item, [
                'subtotal'       => $sub,
                'igv'            => $igv,
                'total'          => $sub + $igv,
                'igv_porcentaje' => $igvPct,
                'orden'          => $i,
            ]);
        }

        $invoice = DB::transaction(function () use ($data, $itemsCalc, $subtotal, $igvTotal) {
            $inv = Invoice::create([
                'tipo_comprobante' => $data['tipo_comprobante'],
                'serie'            => $data['serie'],
                'client_id'        => $data['client_id'],
                'quote_id'         => $data['quote_id'] ?? null,
                'fecha_emision'    => $data['fecha_emision'],
                'fecha_vencimiento'=> $data['fecha_vencimiento'] ?? null,
                'moneda'           => $data['moneda'],
                'subtotal'         => round($subtotal, 2),
                'igv'              => round($igvTotal, 2),
                'total'            => round($subtotal + $igvTotal, 2),
                'notas'            => $data['notas'] ?? null,
                'estado_sunat'     => 'borrador',
                'created_by'       => auth()->id(),
            ]);

            foreach ($itemsCalc as $item) {
                $inv->items()->create($item);
            }

            return $inv;
        });

        // Si la API está configurada, crear en la API SUNAT automáticamente
        if ($this->sunat->estaConfigurada()) {
            try {
                $invoice->load(['client', 'items']);
                $result = $esFactura
                    ? $this->sunat->crearFactura($invoice)
                    : $this->sunat->crearBoleta($invoice);

                $invoice->update([
                    'sunat_doc_id'    => $result['sunat_doc_id'],
                    'numero_completo' => $result['numero_completo'],
                    'correlativo'     => $result['correlativo'],
                    'estado_sunat'    => 'pendiente',
                ]);
            } catch (\Exception $e) {
                $invoice->update([
                    'estado_sunat'   => 'error',
                    'sunat_mensaje'  => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('facturacion.show', $invoice)
            ->with('success', 'Comprobante creado correctamente.');
    }

    public function show(Invoice $factura)
    {
        $factura->load(['client', 'quote', 'items', 'createdBy']);
        $apiOk = $this->sunat->estaConfigurada();
        return view('facturacion.show', compact('factura', 'apiOk'));
    }

    public function destroy(Invoice $factura)
    {
        if (!$factura->puedeBorrarse()) {
            return back()->with('error', 'Solo se pueden eliminar comprobantes en borrador o con error.');
        }
        $factura->delete();
        return redirect()->route('facturacion.index')->with('success', 'Comprobante eliminado.');
    }

    // ── Enviar a SUNAT ───────────────────────────────────────────────
    public function enviar(Invoice $factura)
    {
        if (!$factura->puedeEmitirse()) {
            return back()->with('error', 'Este comprobante no puede enviarse en su estado actual.');
        }

        $factura->update(['estado_sunat' => 'enviando']);

        try {
            $result = $factura->esFactura()
                ? $this->sunat->enviarFactura($factura->sunat_doc_id)
                : $this->sunat->enviarBoleta($factura->sunat_doc_id);

            $estadoApi = strtolower($result['data']['estado_sunat'] ?? 'error');
            $estadoMap = [
                'aceptado'  => 'aceptado',
                'pendiente' => 'pendiente',
                'rechazado' => 'rechazado',
            ];

            $factura->update([
                'estado_sunat'  => $estadoMap[$estadoApi] ?? 'pendiente',
                'sunat_mensaje' => $result['data']['sunat_descripcion'] ?? null,
                'emitido_at'    => now(),
            ]);

            return back()->with('success', 'Comprobante enviado a SUNAT.');
        } catch (\Exception $e) {
            $factura->update([
                'estado_sunat'  => 'error',
                'sunat_mensaje' => $e->getMessage(),
            ]);
            return back()->with('error', 'Error al enviar: ' . $e->getMessage());
        }
    }

    // ── Descargar PDF / XML / CDR ────────────────────────────────────
    public function descargarPdf(Invoice $factura)
    {
        return $this->descargar(
            $factura->esFactura()
                ? $this->sunat->descargarPdfFactura($factura->sunat_doc_id)
                : $this->sunat->descargarPdfBoleta($factura->sunat_doc_id),
            $factura->numero_completo . '.pdf',
            'application/pdf'
        );
    }

    public function descargarXml(Invoice $factura)
    {
        return $this->descargar(
            $factura->esFactura()
                ? $this->sunat->descargarXmlFactura($factura->sunat_doc_id)
                : $this->sunat->descargarXmlBoleta($factura->sunat_doc_id),
            $factura->numero_completo . '.xml',
            'application/xml'
        );
    }

    public function descargarCdr(Invoice $factura)
    {
        return $this->descargar(
            $factura->esFactura()
                ? $this->sunat->descargarCdrFactura($factura->sunat_doc_id)
                : $this->sunat->descargarCdrBoleta($factura->sunat_doc_id),
            'CDR-' . $factura->numero_completo . '.zip',
            'application/zip'
        );
    }

    private function descargar($response, string $filename, string $mime)
    {
        if (!$response->successful()) {
            return back()->with('error', 'No se pudo descargar el archivo.');
        }
        return response($response->body(), 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
