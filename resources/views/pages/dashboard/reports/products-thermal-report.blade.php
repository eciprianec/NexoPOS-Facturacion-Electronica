@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            @include( '../common/dashboard/title' )
        </div>
        <div class="p-4 flex-auto">
            <div class="ns-box border rounded p-4 elevation-surface">
                <!-- Encabezado del Panel -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 pb-3 border-b gap-3">
                    <div>
                        <h3 class="font-bold text-lg">{{ __( 'Reporte de Inventario (Stock y Precios)' ) }}</h3>
                        <p class="text-sm text-gray-500">{{ __( 'Listado de productos con unidad, stock y precio de venta para punto de venta 80mm.' ) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="printThermalTicket()" type="button" class="px-4 py-2.5 bg-success-primary text-white font-bold rounded shadow hover:bg-success-secondary text-sm flex items-center gap-2 cursor-pointer transition-colors">
                            🖨️ {{ __( 'IMPRIMIR TICKET (80mm)' ) }}
                        </button>
                    </div>
                </div>

                <!-- Filtro de Búsqueda Rápida -->
                <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-3">
                    <div class="w-full md:w-1/3">
                        <input type="text" id="reportSearchInput" onkeyup="filterReportTable()" placeholder="🔍 Buscar producto..." class="w-full p-2.5 border rounded text-sm bg-white dark:bg-gray-800 font-medium border-gray-300 dark:border-gray-600 focus:outline-hidden">
                    </div>
                    <div class="text-xs font-semibold text-gray-500">
                        Fecha: {{ $date }}
                    </div>
                </div>

                <!-- Tabla Interactiva del Panel de NexoPOS -->
                <div class="overflow-x-auto border rounded bg-white dark:bg-gray-800">
                    <table class="w-full text-sm text-left" id="reportTable">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-xs uppercase font-bold text-gray-700 dark:text-gray-200 border-b">
                            <tr>
                                <th class="p-3">{{ __( 'PRODUCTO' ) }}</th>
                                <th class="p-3 text-center">{{ __( 'UNIDAD (STOCK)' ) }}</th>
                                <th class="p-3 text-right">{{ __( 'PRECIO DE VENTA' ) }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @php $totalItems = 0; @endphp
                            @foreach( $products as $product )
                                @if( $product->unit_quantities && $product->unit_quantities->count() > 0 )
                                    @foreach( $product->unit_quantities as $uq )
                                        @php 
                                            $totalItems++;
                                            $unitName = $uq->unit ? $uq->unit->name : 'Unid';
                                            $stock = (float) $uq->quantity;
                                            $price = $uq->sale_price_edit ?? $uq->sale_price ?? 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="p-3 font-semibold text-gray-800 dark:text-gray-200">{{ $product->name }}</td>
                                            <td class="p-3 text-center font-bold">
                                                <span class="px-2.5 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 rounded-full text-xs">
                                                    {{ $unitName }} ({{ (int)$stock == $stock ? (int)$stock : number_format($stock, 2) }})
                                                </span>
                                            </td>
                                            <td class="p-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                                {{ ns()->currency->define( $price ) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    @php $totalItems++; @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="p-3 font-semibold text-gray-800 dark:text-gray-200">{{ $product->name }}</td>
                                        <td class="p-3 text-center text-gray-400">Unid (0)</td>
                                        <td class="p-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ ns()->currency->define( 0 ) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Resumen de Tabla -->
                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 border rounded flex justify-between items-center text-sm font-bold text-gray-700 dark:text-gray-200">
                    <span>{{ __( 'TOTAL DE REGISTROS' ) }}</span>
                    <span class="text-base text-primary-tertiary">{{ $totalItems }} {{ __( 'Productos' ) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function filterReportTable() {
        const input = document.getElementById('reportSearchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('reportTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByTagName('td')[0];
            if (td) {
                const txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    }

    function printThermalTicket() {
        const url = '{{ ns()->url("/dashboard/products/thermal-inventory-report?autoprint=true") }}';
        window.open( url, '_blank', 'width=420,height=650' );
    }
</script>
@endsection
