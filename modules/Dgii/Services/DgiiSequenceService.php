<?php

namespace Modules\Dgii\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class DgiiSequenceService
{
    /**
     * Get next NCF / e-CF number for specified type code (e.g. 'E31', 'E32', 'B01')
     */
    public function getNextNcf(string $typeCode = 'E32'): array
    {
        return DB::transaction(function () use ($typeCode) {
            $seq = DB::table('nexopos_dgii_sequences')
                ->where('type_code', $typeCode)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                // Initialize default sequence if not created yet
                $defaultPrefix = $typeCode;
                $seqId = DB::table('nexopos_dgii_sequences')->insertGetId([
                    'type_code' => $typeCode,
                    'name' => $this->getSequenceName($typeCode),
                    'prefix' => $defaultPrefix,
                    'current_number' => 1,
                    'limit_number' => 99999999,
                    'is_active' => 1,
                    'is_ecf' => str_starts_with($typeCode, 'E') ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $seq = DB::table('nexopos_dgii_sequences')->where('id', $seqId)->first();
            }

            if ($seq->current_number > $seq->limit_number) {
                throw new Exception("La secuencia NCF para {$typeCode} ha alcanzado su límite permitido ({$seq->limit_number}).");
            }

            $padding = str_starts_with($typeCode, 'E') ? 10 : 8;
            $formattedNumber = $seq->prefix . str_pad((string)$seq->current_number, $padding, '0', STR_PAD_LEFT);

            // Increment current sequence
            DB::table('nexopos_dgii_sequences')
                ->where('id', $seq->id)
                ->update([
                    'current_number' => $seq->current_number + 1,
                    'updated_at' => now(),
                ]);

            return [
                'ncf' => $formattedNumber,
                'prefix' => $seq->prefix,
                'number' => $seq->current_number,
                'is_ecf' => (bool)$seq->is_ecf,
                'type_code' => $typeCode,
            ];
        });
    }

    private function getSequenceName(string $typeCode): string
    {
        $names = [
            'E31' => 'Factura de Crédito Fiscal Electrónica (e-CF 31)',
            'E32' => 'Factura de Consumo Electrónica (e-CF 32)',
            'E33' => 'Nota de Débito Electrónica (e-CF 33)',
            'E34' => 'Nota de Crédito Electrónica (e-CF 34)',
            'E41' => 'Compras Electrónica (e-CF 41)',
            'E43' => 'Gastos Menores (e-CF 43)',
            'E44' => 'Regímenes Especiales (e-CF 44)',
            'E45' => 'Gubernamental Electrónica (e-CF 45)',
            'B01' => 'Factura de Crédito Fiscal (NCF B01)',
            'B02' => 'Factura de Consumo (NCF B02)',
        ];

        return $names[$typeCode] ?? "Comprobante {$typeCode}";
    }
}
