<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EmpresaConfig extends Model
{
    protected $table = 'empresa_config';

    protected $fillable = [
        'ruc', 'razon_social', 'nombre_comercial',
        'direccion', 'ubigeo', 'email', 'telefono', 'web',
        'logo_sidebar', 'logo_login', 'logo_documentos',
        'igv_porcentaje', 'moneda', 'serie_boleta', 'serie_factura',
        'sunat_modo', 'nubefact_url', 'nubefact_token',
        'certificado_pfx_path', 'certificado_pfx_clave',
    ];

    protected $casts = [
        'igv_porcentaje'       => 'decimal:2',
        'certificado_pfx_clave'=> 'encrypted',
    ];

    // ── Singleton ────────────────────────────────────────────────────

    public static function config(): static
    {
        return static::firstOrNew(['id' => 1]);
    }

    // ── Accessors de URL para logos ──────────────────────────────────

    public function logoSidebarUrl(): ?string
    {
        return $this->logo_sidebar
            ? Storage::disk('public')->url($this->logo_sidebar)
            : null;
    }

    public function logoLoginUrl(): ?string
    {
        return $this->logo_login
            ? Storage::disk('public')->url($this->logo_login)
            : null;
    }

    public function logoDocumentosUrl(): ?string
    {
        return $this->logo_documentos
            ? Storage::disk('public')->url($this->logo_documentos)
            : null;
    }

    // ── Helper IGV ───────────────────────────────────────────────────

    public function igvDecimal(): float
    {
        return (float) $this->igv_porcentaje / 100;
    }
}
