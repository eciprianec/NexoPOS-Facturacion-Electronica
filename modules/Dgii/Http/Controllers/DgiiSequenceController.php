<?php

namespace Modules\Dgii\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DgiiSequenceController extends Controller
{
    public function index()
    {
        $sequences = DB::table('nexopos_dgii_sequences')->orderBy('type_code')->get();
        if ($sequences->isEmpty()) {
            $this->seedDefaults();
            $sequences = DB::table('nexopos_dgii_sequences')->orderBy('type_code')->get();
        }
        return view('Dgii::sequences', compact('sequences'));
    }

    public function save(Request $request)
    {
        $id = $request->input('id');
        $data = [
            'current_number' => $request->input('current_number', 1),
            'limit_number' => $request->input('limit_number', 99999999),
            'expiration_date' => $request->input('expiration_date'),
            'is_active' => $request->has('is_active') ? 1 : 0,
            'updated_at' => now(),
        ];

        DB::table('nexopos_dgii_sequences')->where('id', $id)->update($data);
        return redirect()->back()->with('success', 'Secuencia NCF/e-CF actualizada exitosamente.');
    }

    private function seedDefaults()
    {
        $defaults = [
            ['type_code' => 'E31', 'name' => 'Factura de Crédito Fiscal Electrónica (e-CF 31)', 'prefix' => 'E31', 'is_ecf' => 1],
            ['type_code' => 'E32', 'name' => 'Factura de Consumo Electrónica (e-CF 32)', 'prefix' => 'E32', 'is_ecf' => 1],
            ['type_code' => 'E33', 'name' => 'Nota de Débito Electrónica (e-CF 33)', 'prefix' => 'E33', 'is_ecf' => 1],
            ['type_code' => 'E34', 'name' => 'Nota de Crédito Electrónica (e-CF 34)', 'prefix' => 'E34', 'is_ecf' => 1],
            ['type_code' => 'E41', 'name' => 'Compras Electrónica (e-CF 41)', 'prefix' => 'E41', 'is_ecf' => 1],
            ['type_code' => 'E43', 'name' => 'Gastos Menores (e-CF 43)', 'prefix' => 'E43', 'is_ecf' => 1],
            ['type_code' => 'E44', 'name' => 'Regímenes Especiales (e-CF 44)', 'prefix' => 'E44', 'is_ecf' => 1],
            ['type_code' => 'E45', 'name' => 'Gubernamental Electrónica (e-CF 45)', 'prefix' => 'E45', 'is_ecf' => 1],
            ['type_code' => 'B01', 'name' => 'Factura de Crédito Fiscal (NCF B01)', 'prefix' => 'B01', 'is_ecf' => 0],
            ['type_code' => 'B02', 'name' => 'Factura de Consumo (NCF B02)', 'prefix' => 'B02', 'is_ecf' => 0],
        ];

        foreach ($defaults as $d) {
            DB::table('nexopos_dgii_sequences')->insertOrIgnore(array_merge($d, [
                'current_number' => 1,
                'limit_number' => 99999999,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
