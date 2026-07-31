<?php

namespace Modules\Dgii\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class DgiiReportService
{
    /**
     * Generate Formato 607 (Ventas de Bienes y Servicios)
     * Period format: 'YYYYMM' e.g. '202607'
     */
    public function generate607(string $period): array
    {
        $settings = DB::table('nexopos_dgii_settings')->first();
        $rncEmisor = $settings->rnc_emisor ?? '101000000';

        // Extract year and month
        $year = substr($period, 0, 4);
        $month = substr($period, 4, 2);

        $startDate = "{$year}-{$month}-01 00:00:00";
        $endDate = date('Y-m-t 23:59:59', strtotime($startDate));

        $invoices = DB::table('nexopos_dgii_invoices')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get();

        $rows = [];
        $totalAmountSum = 0;
        $totalTaxSum = 0;

        foreach ($invoices as $inv) {
            $rncBuyer = preg_replace('/[^\d]/', '', $inv->rnc_buyer ?? '');
            $idType = 3; // 1 = RNC, 2 = Cédula, 3 = Pasaporte / Consumidor Final
            if (strlen($rncBuyer) === 9) {
                $idType = 1;
            } elseif (strlen($rncBuyer) === 11) {
                $idType = 2;
            }

            $dateFormatted = date('Ymd', strtotime($inv->created_at));
            $monto = floatval($inv->total_amount);
            $itbis = floatval($inv->tax_amount);
            $montoBruto = max(0, $monto - $itbis);

            $totalAmountSum += $monto;
            $totalTaxSum += $itbis;

            // Formato 607 Campos (DGII Norma 07-2018)
            $rows[] = [
                'rnc_buyer' => $rncBuyer,
                'id_type' => $idType,
                'ncf' => $inv->ncf,
                'ncf_mod' => '',
                'income_type' => '01', // 01 = Ingresos por Operaciones (Ventas)
                'issue_date' => $dateFormatted,
                'retention_date' => '',
                'total_amount' => number_format($montoBruto, 2, '.', ''),
                'itbis_amount' => number_format($itbis, 2, '.', ''),
                'itbis_retained' => '0.00',
                'itbis_perceived' => '0.00',
                'isr_retained' => '0.00',
                'isr_perceived' => '0.00',
                'selective_tax' => '0.00',
                'other_taxes' => '0.00',
                'legal_tip' => '0.00',
                'cash' => number_format($monto, 2, '.', ''),
                'check_transfer' => '0.00',
                'credit_card' => '0.00',
                'credit' => '0.00',
                'gift_card' => '0.00',
                'permuta' => '0.00',
                'other_payment' => '0.00',
            ];
        }

        // Build .TXT string format for DGII
        $txtLines = [];
        $txtLines[] = "607|{$rncEmisor}|" . count($rows) . "|{$period}";
        foreach ($rows as $r) {
            $txtLines[] = implode('|', array_values($r));
        }
        $txtContent = implode("\r\n", $txtLines);

        return [
            'period' => $period,
            'rnc_emisor' => $rncEmisor,
            'total_records' => count($rows),
            'total_amount' => number_format($totalAmountSum, 2, '.', ''),
            'total_tax' => number_format($totalTaxSum, 2, '.', ''),
            'rows' => $rows,
            'txt_content' => $txtContent,
        ];
    }

    /**
     * Generate Formato 608 (Comprobantes Anulados)
     */
    public function generate608(string $period): array
    {
        $settings = DB::table('nexopos_dgii_settings')->first();
        $rncEmisor = $settings->rnc_emisor ?? '101000000';

        $year = substr($period, 0, 4);
        $month = substr($period, 4, 2);

        $startDate = "{$year}-{$month}-01 00:00:00";
        $endDate = date('Y-m-t 23:59:59', strtotime($startDate));

        $cancelledInvoices = DB::table('nexopos_dgii_invoices')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('status', 'cancelled')
            ->get();

        $rows = [];
        foreach ($cancelledInvoices as $inv) {
            $rows[] = [
                'ncf' => $inv->ncf,
                'cancel_date' => date('Ymd', strtotime($inv->updated_at)),
                'cancel_type' => '02', // 02 = Errores de Impresión / Sistema
            ];
        }

        $txtLines = [];
        $txtLines[] = "608|{$rncEmisor}|" . count($rows) . "|{$period}";
        foreach ($rows as $r) {
            $txtLines[] = implode('|', array_values($r));
        }

        return [
            'period' => $period,
            'rnc_emisor' => $rncEmisor,
            'total_records' => count($rows),
            'rows' => $rows,
            'txt_content' => implode("\r\n", $txtLines),
        ];
    }
}
