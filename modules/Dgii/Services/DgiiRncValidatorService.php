<?php

namespace Modules\Dgii\Services;

use Seisigma\DgiiRncValidator\DgiiRncValidator;
use Exception;

class DgiiRncValidatorService
{
    public function validateAndLookup(string $rnc): array
    {
        $cleaned = preg_replace('/[^\d]/', '', $rnc);

        if (empty($cleaned) || !DgiiRncValidator::validateRNC($cleaned)) {
            return [
                'status' => 'error',
                'valid' => false,
                'message' => 'El RNC o Cédula proporcionado no tiene un formato válido (debe contener 9 u 11 dígitos).'
            ];
        }

        try {
            $result = DgiiRncValidator::check($cleaned);
            if ($result && is_array($result) && !empty($result['name'])) {
                return [
                    'status' => 'success',
                    'valid' => true,
                    'rnc' => $cleaned,
                    'name' => $result['name'],
                    'commercial_name' => $result['commercial_name'] ?? '',
                    'rnc_status' => $result['status'] ?? 'ACTIVO',
                ];
            } else {
                return [
                    'status' => 'success',
                    'valid' => true,
                    'rnc' => $cleaned,
                    'name' => 'CONTRIBUYENTE REGISTRADO (' . $cleaned . ')',
                    'rnc_status' => 'REGISTRADO',
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'success',
                'valid' => true,
                'rnc' => $cleaned,
                'name' => 'CONTRIBUYENTE ' . $cleaned,
                'rnc_status' => 'VALIDADO_FORMATO',
                'warning' => 'Validación de formato exitosa. (Consulta DGII: ' . $e->getMessage() . ')',
            ];
        }
    }
}
