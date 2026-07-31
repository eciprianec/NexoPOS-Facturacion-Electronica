@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col p-6 bg-slate-900 text-white min-h-screen">
    <div class="mb-6 flex justify-between items-center border-b border-slate-700 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-emerald-400">📜 Histórico de Comprobantes e-CF</h1>
            <p class="text-slate-400 text-sm">Monitoreo de e-CF emitidos y respuestas de la DGII</p>
        </div>
    </div>

    <div class="overflow-x-auto bg-slate-800 rounded-lg border border-slate-700">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-slate-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">NCF / e-CF</th>
                    <th class="px-4 py-3">RNC Comprador</th>
                    <th class="px-4 py-3">Razón Social</th>
                    <th class="px-4 py-3">Monto Total</th>
                    <th class="px-4 py-3">ITBIS</th>
                    <th class="px-4 py-3">TrackID DGII</th>
                    <th class="px-4 py-3">Estado DGII</th>
                    <th class="px-4 py-3">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-700/50">
                        <td class="px-4 py-3 font-mono font-bold text-emerald-400">{{ $inv->ncf }}</td>
                        <td class="px-4 py-3 font-mono">{{ $inv->rnc_buyer ?: 'Consumidor Final' }}</td>
                        <td class="px-4 py-3">{{ $inv->buyer_name ?: 'CLIENTE CONTADO' }}</td>
                        <td class="px-4 py-3 font-mono">RD$ {{ number_format($inv->total_amount, 2) }}</td>
                        <td class="px-4 py-3 font-mono">RD$ {{ number_format($inv->tax_amount, 2) }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $inv->track_id ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @if($inv->status === 'accepted')
                                <span class="px-2 py-1 bg-emerald-900/80 text-emerald-300 rounded-full text-xs font-semibold">ACEPTADO</span>
                            @elseif($inv->status === 'rejected')
                                <span class="px-2 py-1 bg-red-900/80 text-red-300 rounded-full text-xs font-semibold">RECHAZADO</span>
                            @else
                                <span class="px-2 py-1 bg-amber-900/80 text-amber-300 rounded-full text-xs font-semibold">PENDIENTE</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $inv->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">No hay comprobantes fiscalizados emitidos aún.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
