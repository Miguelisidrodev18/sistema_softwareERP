<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\UpdateEmpresaConfigRequest;
use App\Models\EmpresaConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ConfigController extends Controller
{
    public function index()
    {
        $config = EmpresaConfig::config();
        return view('configuracion.index', compact('config'));
    }

    public function update(UpdateEmpresaConfigRequest $request)
    {
        $config = EmpresaConfig::config();
        $data   = $request->safe()->except([
            'logo_sidebar', 'logo_login', 'logo_documentos',
            'delete_logo_sidebar', 'delete_logo_login', 'delete_logo_documentos',
        ]);

        // Procesar cada logo
        foreach (['logo_sidebar', 'logo_login', 'logo_documentos'] as $campo) {

            // 1. Eliminar si se marcó delete
            if ($request->boolean("delete_{$campo}") && $config->$campo) {
                Storage::disk('public')->delete($config->$campo);
                $data[$campo] = null;
            }

            // 2. Subir nueva imagen (reemplaza la actual)
            if ($request->hasFile($campo)) {
                if ($config->$campo) {
                    Storage::disk('public')->delete($config->$campo);
                }
                $data[$campo] = $request->file($campo)->store("logos/{$campo}", 'public');
            }
        }

        $config->fill($data);
        $config->save();

        // Limpiar caché de config de empresa
        Cache::forget('empresa_config');

        return redirect()
            ->route('configuracion.index')
            ->with('success', 'Configuración guardada correctamente.');
    }
}
