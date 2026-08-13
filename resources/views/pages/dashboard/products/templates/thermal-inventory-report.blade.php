<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previsualización de Reporte de Inventario (80mm)</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        
        /* Estilos para Pantalla (Vista de Previsualización) */
        @media screen {
            body {
                background-color: #0f172a;
                color: #f8fafc;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
            }
            .no-print-toolbar {
                position: sticky;
                top: 0;
                z-index: 100;
                background-color: #1e293b;
                border-bottom: 1px solid #334155;
                padding: 12px 24px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            }
            .toolbar-title {
                font-weight: 700;
                font-size: 16px;
                color: #f8fafc;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .btn {
                padding: 8px 16px;
                font-weight: 700;
                font-size: 14px;
                border-radius: 6px;
                border: none;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s ease;
            }
            .btn-primary {
                background-color: #10b981;
                color: #ffffff;
            }
            .btn-primary:hover {
                background-color: #059669;
            }
            .btn-secondary {
                background-color: #475569;
                color: #ffffff;
            }
            .btn-secondary:hover {
                background-color: #334155;
            }
            .preview-container {
                padding: 24px 12px;
                display: flex;
                justify-content: center;
            }
            .paper-receipt {
                background: #ffffff;
                color: #000000;
                width: 340px; /* Ancho de previsualización 80mm */
                min-height: 400px;
                padding: 16px;
                border-radius: 6px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
                font-family: 'Courier New', Courier, monospace;
                font-size: 11px;
                box-sizing: border-box;
            }
        }

        /* Estilos de Impresión Térmica (80mm) */
        @media print {
            .no-print-toolbar {
                display: none !important;
            }
            .preview-container {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }
            body, .paper-receipt {
                background: #ffffff !important;
                color: #000000 !important;
                width: 72mm !important;
                margin: 0 !important;
                padding: 4px !important;
                box-shadow: none !important;
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 11px !important;
            }
        }

        /* Formato Interno del Ticket */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .header {
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th {
            border-bottom: 1px solid #000;
            padding: 3px 0;
            font-size: 10px;
            text-transform: uppercase;
        }

        td {
            padding: 3px 0;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
        }

        .prod-name {
            font-weight: bold;
            word-break: break-word;
            padding-right: 4px;
        }

        .footer {
            margin-top: 12px;
            border-top: 1px dashed #000;
            padding-top: 6px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <!-- Barra de Herramientas Superior -->
    <div class="no-print-toolbar">
        <div class="toolbar-title">
            📋 Previsualización Reporte de Inventario (80mm)
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ ns()->url('/dashboard/products') }}" class="btn btn-secondary">
                ⬅️ Volver a Productos
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ IMPRIMIR REPORTE (80mm)
            </button>
        </div>
    </div>

    <!-- Contenedor del Papel Térmico (Previsualización) -->
    <div class="preview-container">
        <div class="paper-receipt">
            <div class="header text-center">
                <div class="title">{{ ns()->option->get('ns_store_name', 'NexoPOS') }}</div>
                <div class="font-bold">REPORTE DE INVENTARIO</div>
                <div style="font-size: 9px; margin-top: 2px;">Fecha: {{ $date }}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="text-left" style="width: 44%;">PRODUCTO</th>
                        <th class="text-center" style="width: 30%;">UNIDAD(STOCK)</th>
                        <th class="text-right" style="width: 26%;">PRECIO</th>
                    </tr>
                </thead>
                <tbody>
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
                                <tr>
                                    <td class="prod-name">{{ $product->name }}</td>
                                    <td class="text-center">{{ $unitName }} ({{ (int)$stock == $stock ? (int)$stock : number_format($stock, 2) }})</td>
                                    <td class="text-right">{{ ns()->currency->define( $price ) }}</td>
                                </tr>
                            @endforeach
                        @else
                            @php $totalItems++; @endphp
                            <tr>
                                <td class="prod-name">{{ $product->name }}</td>
                                <td class="text-center">Unid (0)</td>
                                <td class="text-right">{{ ns()->currency->define( 0 ) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="footer text-center">
                <div class="font-bold">TOTAL REGISTROS: {{ $totalItems }}</div>
                <div style="margin-top: 4px; font-size: 9px;">*** FIN DEL REPORTE ***</div>
            </div>
        </div>
    </div>

    @if( request()->query( 'autoprint' ) === 'true' )
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 400);
        });
    </script>
    @endif
</body>
</html>
