<?php

namespace Modules\Dgii\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Dgii\Services\DgiiReportService;

class DgiiReportsController extends Controller
{
    public function index()
    {
        $currentPeriod = date('Ym');
        return view('Dgii::reports', compact('currentPeriod'));
    }

    public function generate(Request $request, DgiiReportService $reportService)
    {
        $type = $request->input('report_type', '607');
        $period = $request->input('period', date('Ym'));
        $format = $request->input('format', 'view');

        if ($type === '607') {
            $data = $reportService->generate607($period);
        } elseif ($type === '608') {
            $data = $reportService->generate608($period);
        } else {
            return redirect()->back()->with('error', 'Tipo de reporte no soportado aún.');
        }

        if ($format === 'txt') {
            $filename = "DGII_F_{$type}_{$data['rnc_emisor']}_{$period}.txt";
            return response($data['txt_content'])
                ->header('Content-Type', 'text/plain; charset=utf-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        return view('Dgii::reports', [
            'currentPeriod' => $period,
            'reportType' => $type,
            'reportData' => $data,
        ]);
    }
}
