@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col p-6 bg-slate-900 text-white min-h-screen">
    <div class="mb-6 flex justify-between items-center border-b border-slate-700 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-emerald-400">🔢 Secuencias de Comprobantes NCF / e-CF</h1>
            <p class="text-slate-400 text-sm">Control de números consecutivos de comprobantes fiscales electrónicos para la DGII</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-900/50 border border-emerald-500 rounded text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-slate-800 rounded-lg border border-slate-700">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-slate-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Tipo de Comprobante</th>
                    <th class="px-4 py-3">Prefijo</th>
                    <th class="px-4 py-3">Secuencia Actual</th>
                    <th class="px-4 py-3">Límite</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                @foreach($sequences as $seq)
                    <tr class="hover:bg-slate-700/50">
                        <td class="px-4 py-3 font-mono font-bold text-emerald-400">{{ $seq->type_code }}</td>
                        <td class="px-4 py-3">{{ $seq->name }}</td>
                        <td class="px-4 py-3 font-mono">{{ $seq->prefix }}</td>
                        <td class="px-4 py-3 font-mono font-bold text-white">{{ $seq->current_number }}</td>
                        <td class="px-4 py-3 font-mono text-slate-400">{{ $seq->limit_number }}</td>
                        <td class="px-4 py-3">
                            @if($seq->is_active)
                                <span class="px-2 py-1 bg-emerald-900/80 text-emerald-300 rounded-full text-xs font-semibold">ACTIVO</span>
                            @else
                                <span class="px-2 py-1 bg-red-900/80 text-red-300 rounded-full text-xs font-semibold">INACTIVO</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <form action="{{ route('ns.dashboard.dgii-sequences-save') }}" method="POST" class="flex items-center space-x-2">
                                @csrf
                                <input type="hidden" name="id" value="{{ $seq->id }}">
                                <input type="number" name="current_number" value="{{ $seq->current_number }}" class="w-24 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs text-white">
                                <button type="submit" class="bg-slate-700 hover:bg-slate-600 text-white px-3 py-1 rounded text-xs">Actualizar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
