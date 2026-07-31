@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col p-6 bg-slate-900 text-white min-h-screen">
    <div class="mb-6 flex justify-between items-center border-b border-slate-700 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-emerald-400">📊 Reportes Fiscales DGII (606, 607, 608)</h1>
            <p class="text-slate-400 text-sm">Generación de archivos informáticos para la Oficina Virtual (OFV) de la DGII</p>
        </div>
    </div>

    <form action="{{ route('ns.dashboard.dgii-reports-generate') }}" method="POST" class="bg-slate-800 p-6 rounded-lg border border-slate-700 space-y-4 max-w-3xl mb-8">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Tipo de Formato</label>
                <select name="report_type" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                    <option value="607" {{ ($reportType ?? '') === '607' ? 'selected' : '' }}>Formato 607 (Ventas de Bienes y Servicios)</option>
                    <option value="608" {{ ($reportType ?? '') === '608' ? 'selected' : '' }}>Formato 608 (Comprobantes Anulados)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Período Fiscal (AAAAMM)</label>
                <input type="text" name="period" value="{{ $currentPeriod ?? date('Ym') }}" placeholder="Ej: 202607" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none" required>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" name="format" value="view" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded font-semibold transition text-sm">
                    👁️ Vista Previa
                </button>
                <button type="submit" name="format" value="txt" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded font-semibold transition text-sm">
                    ⬇️ Descargar .TXT DGII
                </button>
            </div>
        </div>
    </form>

    @if(isset($reportData))
        <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                <h2 class="text-lg font-bold text-emerald-400">Resumen Formato {{ $reportType }} (Período {{ $reportData['period'] }})</h2>
                <div class="text-sm text-slate-300 space-x-4">
                    <span>Total Registros: <strong>{{ $reportData['total_records'] }}</strong></span>
                    @if(isset($reportData['total_amount']))
                        <span>Total Ventas: <strong>RD$ {{ $reportData['total_amount'] }}</strong></span>
                        <span>Total ITBIS: <strong>RD$ {{ $reportData['total_tax'] }}</strong></span>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono text-slate-300">
                    <thead class="bg-slate-950 text-slate-400 uppercase">
                        <tr>
                            @if($reportType === '607')
                                <th class="px-3 py-2">RNC / Cédula</th>
                                <th class="px-3 py-2">NCF</th>
                                <th class="px-3 py-2">Fecha</th>
                                <th class="px-3 py-2">Monto Facturado</th>
                                <th class="px-3 py-2">ITBIS Facturado</th>
                            @elseif($reportType === '608')
                                <th class="px-3 py-2">NCF Anulado</th>
                                <th class="px-3 py-2">Fecha Anulación</th>
                                <th class="px-3 py-2">Tipo Anulación</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($reportData['rows'] as $r)
                            <tr class="hover:bg-slate-700/50">
                                @if($reportType === '607')
                                    <td class="px-3 py-2">{{ $r['rnc_buyer'] ?: 'CONSUMIDOR FINAL' }}</td>
                                    <td class="px-3 py-2 text-emerald-400">{{ $r['ncf'] }}</td>
                                    <td class="px-3 py-2">{{ $r['issue_date'] }}</td>
                                    <td class="px-3 py-2">RD$ {{ $r['total_amount'] }}</td>
                                    <td class="px-3 py-2">RD$ {{ $r['itbis_amount'] }}</td>
                                @elseif($reportType === '608')
                                    <td class="px-3 py-2 text-red-400">{{ $r['ncf'] }}</td>
                                    <td class="px-3 py-2">{{ $r['cancel_date'] }}</td>
                                    <td class="px-3 py-2">{{ $r['cancel_type'] }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-slate-500">No hay registros para este período fiscal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
