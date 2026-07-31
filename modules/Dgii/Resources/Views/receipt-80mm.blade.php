<?php
use Illuminate\Support\Facades\DB;

$settings = DB::table('nexopos_dgii_settings')->first();
$dgiiInv = DB::table('nexopos_dgii_invoices')->where('order_id', $order->id)->first();

$typeCode = $dgiiInv->ecf_type ?? 'E32';
$ncfNumber = $dgiiInv->ncf ?? 'E320000000001';

$ncfTitles = [
    'E31' => ['title' => 'FACTURA DE CRÉDITO FISCAL ELECTRÓNICA', 'val' => 'VÁLIDO PARA CRÉDITO FISCAL'],
    'E32' => ['title' => 'FACTURA DE CONSUMO ELECTRÓNICA', 'val' => 'CONSUMIDOR FINAL'],
    'E33' => ['title' => 'NOTA DE DÉBITO ELECTRÓNICA', 'val' => 'NOTA DE DÉBITO'],
    'E34' => ['title' => 'NOTA DE CRÉDITO ELECTRÓNICA', 'val' => 'NOTA DE CRÉDITO'],
    'E41' => ['title' => 'COMPRAS ELECTRÓNICA', 'val' => 'REGISTRO DE COMPRAS'],
    'E43' => ['title' => 'GASTOS MENORES ELECTRÓNICA', 'val' => 'GASTOS MENORES'],
    'E44' => ['title' => 'REGÍMENES ESPECIALES ELECTRÓNICA', 'val' => 'REGIMEN ESPECIAL DE TRIBUTACIÓN'],
    'E45' => ['title' => 'GUBERNAMENTAL ELECTRÓNICA', 'val' => 'COMPROBANTE GUBERNAMENTAL'],
    'B01' => ['title' => 'FACTURA DE CRÉDITO FISCAL', 'val' => 'VÁLIDO PARA CRÉDITO FISCAL'],
    'B02' => ['title' => 'FACTURA DE CONSUMO', 'val' => 'CONSUMIDOR FINAL'],
];

$titleInfo = $ncfTitles[$typeCode] ?? ['title' => "COMPROBANTE {$typeCode}", 'val' => 'VALOR FISCAL'];

$rncEmisor = $settings->rnc_emisor ?? '101000000';
$razonSocial = $settings->razon_social ?? ns()->option->get('ns_store_name', 'MI EMPRESA POS');
$nombreComercial = $settings->nombre_comercial ?? '';
$rncBuyer = $dgiiInv->rnc_buyer ?? ($order->customer->rnc ?? '');
$buyerName = $dgiiInv->buyer_name ?? ($order->customer->name ?? 'CLIENTE CONTADO');
$securityCode = $dgiiInv->security_code ?? 'A1B2C3';

// Generate QR Code URL
$qrUrl = "https://ecf.dgii.gov.do/ecf/consulta?RncEmisor={$rncEmisor}&RncComprador={$rncBuyer}&eNCF={$ncfNumber}&MontoTotal={$order->total}&CodigoSeguridad={$securityCode}";
$qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrUrl);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo Térmico 80mm - {{ $ncfNumber }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace, sans-serif;
            width: 78mm;
            margin: 0 auto;
            padding: 4px;
            color: #000;
            background: #fff;
            font-size: 11px;
            line-height: 1.25;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .border-top { border-top: 1px dashed #000; padding-top: 4px; margin-top: 4px; }
        .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 4px; }
        .table-items { width: 100%; border-collapse: collapse; margin: 4px 0; }
        .table-items th { border-bottom: 1px solid #000; font-size: 10px; }
        .table-items td { vertical-align: top; font-size: 10.5px; padding: 2px 0; }
        .badge {
            display: block;
            background: #000;
            color: #fff;
            padding: 2px 4px;
            font-weight: bold;
            font-size: 11px;
            margin: 4px 0;
        }
        .qr-container { text-align: center; margin-top: 6px; }
        .qr-container img { width: 110px; height: 110px; }
    </style>
</head>
<body onload="if(requestQuery('autoprint')==='true') window.print();">

    <!-- HEADER EMISOR -->
    <div class="text-center border-bottom">
        <div class="font-bold text-sm uppercase">{{ $razonSocial }}</div>
        @if($nombreComercial)
            <div>{{ $nombreComercial }}</div>
        @endif
        <div class="font-bold">RNC: {{ $rncEmisor }}</div>
        <div>{{ ns()->option->get('ns_store_address', 'Santo Domingo, Rep. Dom.') }}</div>
        <div>TEL: {{ ns()->option->get('ns_store_phone', '809-000-0000') }}</div>
    </div>

    <!-- HEADER COMPROBANTE DGII -->
    <div class="text-center border-bottom">
        <div class="font-bold text-xs uppercase">{{ $titleInfo['title'] }}</div>
        <div class="badge text-center">{{ $titleInfo['val'] }}</div>
        <div class="font-bold" style="font-size: 13px;">NCF: {{ $ncfNumber }}</div>
        <div>Vencimiento NCF: 31/12/2026</div>
        <div>Fecha: {{ date('d/m/Y h:i A', strtotime($order->created_at)) }}</div>
        <div>Orden #: {{ $order->code }}</div>
    </div>

    <!-- DATOS DEL COMPRADOR -->
    <div class="border-bottom">
        <div><span class="font-bold">RNC/CED COMFRADOR:</span> {{ $rncBuyer ?: 'N/A (CONSUMIDOR FINAL)' }}</div>
        <div><span class="font-bold">RAZÓN SOCIAL:</span> {{ $buyerName }}</div>
    </div>

    <!-- TABLA PRODUCTOS -->
    <table class="table-items">
        <thead>
            <tr>
                <th class="text-left">CANT x DESCRIPCIÓN</th>
                <th class="text-right">PRECIO</th>
                <th class="text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->products as $item)
                <tr>
                    <td class="text-left">
                        {{ (int)$item->quantity }} x {{ $item->name }}
                    </td>
                    <td class="text-right">RD${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">RD${{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTALES Y DESGLOSE TRIBUTARIO -->
    <div class="border-top border-bottom">
        <table width="100%">
            <tr>
                <td>Subtotal Neto:</td>
                <td class="text-right">RD$ {{ number_format($order->subtotal - $order->tax_value, 2) }}</td>
            </tr>
            @if($order->discount > 0)
            <tr>
                <td>Descuento:</td>
                <td class="text-right">-RD$ {{ number_format($order->discount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="font-bold">ITBIS Facturado (18%):</td>
                <td class="text-right font-bold">RD$ {{ number_format($order->tax_value, 2) }}</td>
            </tr>
            <tr style="font-size: 13px;">
                <td class="font-bold">TOTAL A PAGAR:</td>
                <td class="text-right font-bold">RD$ {{ number_format($order->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- FORMA DE PAGO -->
    <div class="border-bottom text-xs">
        @foreach($order->payments as $p)
            <div><span class="font-bold">FORMA DE PAGO:</span> {{ strtoupper($p->identifier) }} - RD$ {{ number_format($p->value, 2) }}</div>
        @endforeach
        @if($order->change > 0)
            <div><span class="font-bold">CAMBIO:</span> RD$ {{ number_format($order->change, 2) }}</div>
        @endif
    </div>

    <!-- ESTAMPA DIGITAL e-CF Y CÓDIGO QR DGII -->
    <div class="qr-container">
        <div class="font-bold text-xs">ESTAMPA DIGITAL e-CF</div>
        <div class="text-xs">Cod. Seguridad: <span class="font-bold">{{ $securityCode }}</span></div>
        <div class="text-xs">TrackID: {{ $dgiiInv->track_id ?? 'N/A' }}</div>
        <img src="{{ $qrImageUrl }}" alt="QR Code DGII">
        <div class="text-xs text-center" style="font-size: 8.5px; margin-top: 2px;">Consulte su comprobante en https://ecf.dgii.gov.do</div>
    </div>

    <div class="text-center border-top" style="margin-top: 8px; font-size: 10px;">
        ¡GRACIAS POR SU COMPRA!
    </div>

    <script>
        function requestQuery(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }
    </script>
</body>
</html>
