<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __( 'Ticket de Cierre de Caja' ) }} - {{ $register->name }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        @media print {
            body {
                width: 72mm;
                margin: 0 auto;
                padding: 2mm 0;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            background-color: #fff;
            width: 72mm;
            margin: 0 auto;
            padding: 10px;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 2px double #000;
            margin: 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        td, th {
            padding: 2px 0;
            vertical-align: top;
        }
        .btn-print {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 12px;
            font-family: sans-serif;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
        .alert-box {
            border: 1px solid #000;
            padding: 4px;
            margin: 6px 0;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="no-print text-center">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir Ticket (80mm)</button>
    </div>

    <!-- Encabezado -->
    <div class="text-center">
        <h2 class="font-bold uppercase" style="margin: 0 0 4px 0; font-size: 16px;">
            {{ ns()->option->get( 'ns_store_name', 'NexoPOS' ) }}
        </h2>
        <div style="font-size: 11px;">
            {{ ns()->option->get( 'ns_store_address', '' ) }}
            @if( ns()->option->get( 'ns_store_phone' ) )
                <br>Tel: {{ ns()->option->get( 'ns_store_phone' ) }}
            @endif
        </div>
        <div class="double-divider"></div>
        <h3 class="font-bold uppercase" style="margin: 4px 0; font-size: 14px;">
            REPORT Z - CIERRE DE CAJA
        </h3>
        <div style="font-size: 11px;">
            Impreso: {{ ns()->date->getNowFormatted() }}
        </div>
    </div>

    <div class="divider"></div>

    <!-- Información General -->
    <table>
        <tr>
            <td class="font-bold">Terminal / Caja:</td>
            <td class="text-right">{{ $register->name }}</td>
        </tr>
        <tr>
            <td class="font-bold">Cajero(a):</td>
            <td class="text-right">{{ $user ? ($user->first_name . ' ' . $user->last_name) : $cashier }}</td>
        </tr>
        <tr>
            <td class="font-bold">Apertura:</td>
            <td class="text-right">{{ $openedOn }}</td>
        </tr>
        <tr>
            <td class="font-bold">Cierre:</td>
            <td class="text-right">{{ $closedOn }}</td>
        </tr>
        <tr>
            <td class="font-bold">Duración:</td>
            <td class="text-right">{{ $sessionDuration }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Resumen de Ventas y Formas de Pago -->
    <div class="text-center font-bold uppercase" style="margin: 4px 0;">
        DESGLOSE DE VENTAS Y PAGOS
    </div>
    <div class="divider"></div>

    <table>
        @forelse( $payments as $payment )
            <tr>
                <td>{{ $payment->label }}:</td>
                <td class="text-right">{{ ns()->currency->define( $payment->total_amount ) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="text-center">No hay registros de pago</td>
            </tr>
        @endforelse
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Ventas Brutas:</td>
            <td class="text-right">{{ $totalGrossSales }}</td>
        </tr>
        <tr>
            <td>Descuentos (-):</td>
            <td class="text-right">{{ $totalDiscounts }}</td>
        </tr>
        @if( $totalShippings->toFloat() > 0 )
        <tr>
            <td>Envío / Delivery (+):</td>
            <td class="text-right">{{ $totalShippings }}</td>
        </tr>
        @endif
        <tr class="font-bold">
            <td>TOTAL VENTAS (NETO):</td>
            <td class="text-right">{{ $totalSales }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Movimientos de Efectivo y Cuadre de Caja -->
    <div class="text-center font-bold uppercase" style="margin: 4px 0;">
        CUADRE DE EFECTIVO (ARQUEO)
    </div>
    <div class="divider"></div>

    <table>
        <tr>
            <td>(+) Fondo Inicial (Apertura):</td>
            <td class="text-right">{{ $openingBalance }}</td>
        </tr>
        <tr>
            <td>(+) Ventas en Efectivo:</td>
            <td class="text-right">
                @php
                    $cashPaymentObj = collect($payments)->firstWhere('identifier', \App\Models\OrderPayment::PAYMENT_CASH);
                    $cashSalesValue = $cashPaymentObj ? $cashPaymentObj->total_amount : 0;
                @endphp
                {{ ns()->currency->define( $cashSalesValue ) }}
            </td>
        </tr>
        @if( (float) $totalCashIn > 0 )
        <tr>
            <td>(+) Entradas de Efectivo:</td>
            <td class="text-right">{{ $totalCashInFormatted }}</td>
        </tr>
        @endif
        @if( (float) $totalCashOut > 0 )
        <tr>
            <td>(-) Retiros / Salidas:</td>
            <td class="text-right">{{ $totalCashOutFormatted }}</td>
        </tr>
        @endif
        <tr>
            <td>(-) Cambio / Devueltas:</td>
            <td class="text-right">{{ $totalChangeFormatted }}</td>
        </tr>
        <tr class="double-divider"></tr>
        <tr class="font-bold">
            <td>EFECTIVO ESPERADO:</td>
            <td class="text-right">{{ $expectedCash }}</td>
        </tr>
        <tr class="font-bold">
            <td>EFECTIVO CONTADO (REAL):</td>
            <td class="text-right">{{ $declaredCash }}</td>
        </tr>
    </table>

    <!-- Resultado del Cuadre -->
    <div class="alert-box">
        @if( abs($rawCashDifference) < 0.01 )
            CUADRE PERFECTO ($0.00)
        @elseif( $rawCashDifference > 0 )
            SOBRANTE EN CAJA: +{{ $cashDifference }}
        @else
            FALTANTE EN CAJA: {{ $cashDifference }}
        @endif
    </div>

    <div id="denominations-container">
    @if( isset($denominations) && is_array($denominations) && count($denominations) > 0 )
        <div class="divider"></div>
        <div class="text-center font-bold uppercase" style="margin: 4px 0;">
            DESGLOSE BILLETES Y MONEDAS
        </div>
        <div class="divider"></div>
        <table>
            @php $denomTotal = 0; @endphp
            @foreach( $denominations as $d )
                @php 
                    $qty = (int)($d['qty'] ?? 0); 
                    $val = (float)($d['value'] ?? 0);
                    $sub = $qty * $val;
                    $denomTotal += $sub;
                @endphp
                @if( $qty > 0 )
                <tr>
                    <td>{{ $qty }} x {{ $d['label'] ?? ('RD$ ' . $val) }}:</td>
                    <td class="text-right">{{ ns()->currency->define( $sub ) }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="font-bold">
                <td>TOTAL CONTADO:</td>
                <td class="text-right">{{ ns()->currency->define( $denomTotal ) }}</td>
            </tr>
        </table>
    @endif
    </div>

    <br><br>

    <!-- Sección de Firmas -->
    <table>
        <tr>
            <td class="text-center" style="width: 48%;">
                _____________________<br>
                Firma Cajero(a)
            </td>
            <td class="text-center" style="width: 48%;">
                _____________________<br>
                Firma Supervisor
            </td>
        </tr>
    </table>

    <div class="text-center" style="margin-top: 15px; font-size: 10px;">
        *** FIN DEL REPORTE Z ***
    </div>

    <script>
        window.addEventListener('load', function() {
            const container = document.getElementById('denominations-container');
            const hasPhpDenom = {{ (isset($denominations) && is_array($denominations) && count($denominations) > 0) ? 'true' : 'false' }};
            
            if ( ! hasPhpDenom && container ) {
                const stored = sessionStorage.getItem('pos_temp_denominations');
                if ( stored ) {
                    try {
                        const denoms = JSON.parse(stored);
                        if ( Array.isArray(denoms) && denoms.length > 0 ) {
                            let html = '<div class="divider"></div><div class="text-center font-bold uppercase" style="margin: 4px 0;">DESGLOSE BILLETES Y MONEDAS</div><div class="divider"></div><table>';
                            let total = 0;
                            denoms.forEach(d => {
                                const q = parseInt(d.qty) || 0;
                                const v = parseFloat(d.value) || 0;
                                if ( q > 0 ) {
                                    const sub = q * v;
                                    total += sub;
                                    html += `<tr><td>${q} x ${d.label || ('RD$ ' + v)}:</td><td class="text-right">RD$ ${sub.toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`;
                                }
                            });
                            html += `<tr class="font-bold"><td>TOTAL CONTADO:</td><td class="text-right">RD$ ${total.toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr></table>`;
                            container.innerHTML = html;
                        }
                    } catch(e) {
                        console.error('Error rendering temp denominations:', e);
                    }
                }
            }

            @if( request()->query( 'autoprint' ) === 'true' )
                setTimeout(function() {
                    window.print();
                }, 300);
            @endif
        });
    </script>
</body>
</html>
