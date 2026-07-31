<?php

namespace Modules\Dgii\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DgiiSettingsController extends Controller
{
    public function index()
    {
        $settings = DB::table('nexopos_dgii_settings')->first();
        return view('Dgii::settings', compact('settings'));
    }

    public function save(Request $request)
    {
        $data = [
            'rnc_emisor' => $request->input('rnc_emisor'),
            'razon_social' => $request->input('razon_social'),
            'nombre_comercial' => $request->input('nombre_comercial'),
            'environment' => $request->input('environment', 'testecf'),
            'auto_send_ecf' => $request->has('auto_send_ecf') ? 1 : 0,
            'default_ncf_type_consumer' => $request->input('default_ncf_type_consumer', 'E32'),
            'default_ncf_type_fiscal' => $request->input('default_ncf_type_fiscal', 'E31'),
            'updated_at' => now(),
        ];

        if ($request->hasFile('cert_file')) {
            $path = $request->file('cert_file')->store('dgii_certs', 'local');
            $data['cert_path'] = $path;
        }

        if ($request->filled('cert_password')) {
            $data['cert_password'] = encrypt($request->input('cert_password'));
        }

        $existing = DB::table('nexopos_dgii_settings')->first();
        if ($existing) {
            DB::table('nexopos_dgii_settings')->where('id', $existing->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('nexopos_dgii_settings')->insert($data);
        }

        return redirect()->back()->with('success', 'Configuración de la DGII guardada exitosamente.');
    }
}
