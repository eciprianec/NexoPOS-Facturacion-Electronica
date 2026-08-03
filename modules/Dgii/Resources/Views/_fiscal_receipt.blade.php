<?php
/**
 * Recibo Térmico 80mm Universal — Módulo DGII para NexoPOS
 * =====================================================
 * Renderiza de forma dinámica y elegante según el tipo de venta:
 *  - Si es Fiscal: Factura de Consumo/Crédito Fiscal con NCF, QR, Estampa Digital.
 *  - Si no es Fiscal (estándar/genérica): Ticket normal limpio, ordenado y sin datos de la DGII.
 *
 * Variables disponibles: $order, $ordersService, $paymentTypes
 */

use App\Models\Order;
use App\Classes\Hook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

// ───── 1. OBTENER DATOS FISCALES DE ESTA ORDEN ─────
$dgiiInv = DB::table('nexopos_dgii_invoices')->where('order_id', $order->id)->first();
$dgiiSettings = DB::table('nexopos_dgii_settings')->first();

$esFiscal = !empty($dgiiInv);

// Datos del emisor
$rncEmisor       = $dgiiSettings->rnc_emisor ?? ns()->option->get('ns_store_rnc', '');
$razonSocial     = $dgiiSettings->razon_social ?? ns()->option->get('ns_store_name', 'Food Shop Hierro Express');
$nombreComercial = $dgiiSettings->nombre_comercial ?? '';
$direccion       = ns()->option->get('ns_store_address', '');
$telefono        = ns()->option->get('ns_store_phone', '');

// Datos del comprobante (Solo si es fiscal)
$ncfNumber     = '';
$typeCode      = 'E32';
$rncBuyer      = '';
$buyerName     = '';
$securityCode  = '';
$trackId       = '';

if ($esFiscal) {
    $ncfNumber     = $dgiiInv->ncf ?? '';
    $typeCode      = $dgiiInv->ecf_type ?? 'E32';
    $rncBuyer      = $dgiiInv->rnc_buyer ?? '';
    $buyerName     = $dgiiInv->buyer_name ?? '';
    $securityCode  = $dgiiInv->security_code ?? '';
    $trackId       = $dgiiInv->track_id ?? '';

    $isGenericCustomer = empty($rncBuyer);
    if ($isGenericCustomer) {
        $typeCode   = 'E32';
        $buyerName  = 'CONSUMIDOR FINAL';
    }
}

// ───── 2. MAPEO DE TIPOS DE COMPROBANTE ─────
$tiposComprobante = [
    'E31' => ['titulo' => 'FACTURA DE CRÉDITO FISCAL ELECTRÓNICA', 'indicador' => 'VÁLIDO PARA CRÉDITO FISCAL'],
    'E32' => ['titulo' => 'FACTURA DE CONSUMO ELECTRÓNICA', 'indicador' => 'CONSUMIDOR FINAL'],
    'E33' => ['titulo' => 'NOTA DE DÉBITO ELECTRÓNICA', 'indicador' => 'NOTA DE DÉBITO'],
    'E34' => ['titulo' => 'NOTA DE CRÉDITO ELECTRÓNICA', 'indicador' => 'NOTA DE CRÉDITO'],
    'E41' => ['titulo' => 'COMPROBANTE DE COMPRAS ELECTRÓNICO', 'indicador' => 'REGISTRO DE COMPRAS'],
    'E43' => ['titulo' => 'COMPROBANTE GASTOS MENORES ELECTRÓNICO', 'indicador' => 'GASTOS MENORES'],
    'E44' => ['titulo' => 'COMPROBANTE REGÍMENES ESPECIALES ELECTRÓNICO', 'indicador' => 'RÉGIMEN ESPECIAL'],
    'E45' => ['titulo' => 'COMPROBANTE GUBERNAMENTAL ELECTRÓNICO', 'indicador' => 'GUBERNAMENTAL'],
    'B01' => ['titulo' => 'FACTURA DE CRÉDITO FISCAL', 'indicador' => 'VÁLIDO PARA CRÉDITO FISCAL'],
    'B02' => ['titulo' => 'FACTURA DE CONSUMO', 'indicador' => 'CONSUMIDOR FINAL'],
];

$tipo = $tiposComprobante[$typeCode] ?? ['titulo' => 'COMPROBANTE FISCAL', 'indicador' => 'VALOR FISCAL'];

// ───── 3. CÁLCULOS ─────
$prefered_price = $order->settings?->where('key', 'ns_pos_prefered_price')->first()?->value;
$pos_vat        = $order->settings?->where('key', 'ns_pos_vat')->first()?->value;

$subtotalNeto   = $order->subtotal;
$itbis          = $order->tax_value ?? 0;
$totalFinal     = $order->total;

// QR DGII (Solo si es fiscal)
$qrImageUrl = '';
if ($esFiscal && $ncfNumber) {
    $qrUrl = "https://ecf.dgii.gov.do/ecf/consulta?RncEmisor={$rncEmisor}&RncComprador={$rncBuyer}&eNCF={$ncfNumber}&MontoTotal={$totalFinal}&CodigoSeguridad={$securityCode}";
    $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . urlencode($qrUrl);
}
?>

<div id="dgii-fiscal-receipt" class="dgii-receipt-wrapper">
<style> @media print { body, html, * { color: #000 !important; font-weight: 800 !important; font-family: 'Arial', sans-serif !important; } .text-sm { font-size: 14px !important; } .text-xs { font-size: 12px !important; } .text-gray-500, .text-gray-600, .text-gray-700 { color: #000 !important; } border, .border-b, .border-dashed { border-color: #000 !important; } } 
    /* Estilos para impresión térmica 80mm */
    @page {
        margin: 0;
        size: 80mm auto;
    }
    @media print {
        body { margin: 0; padding: 0; }
        .dgii-receipt-wrapper { width: 76mm !important; }
        .no-print { display: none !important; }
    }
    .dgii-receipt-wrapper {
        font-family: 'Courier New', Courier, monospace;
        width: 76mm;
        margin: 0 auto;
        padding: 2mm;
        color: #000;
        background: #fff;
        font-size: 11px;
        line-height: 1.3;
        box-sizing: border-box;
    }
    .dgii-receipt-wrapper * { box-sizing: border-box; }

    /* Encabezado empresa */
    .r-header {
        text-align: center;
        padding-bottom: 4px;
        border-bottom: 1px dashed #000;
        margin-bottom: 4px;
    }
    .r-header .empresa-nombre {
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .r-header .empresa-comercial {
        font-size: 11px;
        font-style: italic;
    }
    .r-header .empresa-rnc {
        font-weight: bold;
        font-size: 12px;
        margin: 2px 0;
    }
    .r-header .empresa-datos {
        font-size: 10px;
        color: #333;
    }

    /* Badge tipo comprobante */
    .r-tipo-badge {
        text-align: center;
        padding: 4px 2px;
        border-bottom: 1px dashed #000;
        margin-bottom: 4px;
    }
    .r-tipo-titulo {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .r-tipo-indicador {
        display: block;
        background: #000;
        color: #fff;
        font-weight: bold;
        font-size: 11px;
        padding: 3px 4px;
        margin: 3px 0;
        text-align: center;
        letter-spacing: 0.5px;
    }
    .r-ncf-number {
        font-size: 13px;
        font-weight: bold;
        letter-spacing: 1px;
        margin: 2px 0;
    }
    .r-ncf-vencimiento {
        font-size: 9px;
        color: #555;
    }

    /* Info orden y comprador */
    .r-info-block {
        padding: 3px 0;
        border-bottom: 1px dashed #000;
        margin-bottom: 4px;
        font-size: 10.5px;
    }
    .r-info-block .r-row {
        display: flex;
        justify-content: space-between;
        padding: 1px 0;
    }
    .r-info-block .r-label {
        font-weight: bold;
        font-size: 10px;
        color: #333;
    }
    .r-info-block .r-value {
        text-align: right;
        font-size: 10.5px;
    }

    /* Tabla de productos */
    .r-products { width: 100%; border-collapse: collapse; margin: 2px 0; }
    .r-products thead th {
        font-size: 9.5px;
        text-transform: uppercase;
        border-bottom: 1px solid #000;
        padding: 2px 0;
        text-align: left;
        font-weight: bold;
    }
    .r-products thead th:last-child { text-align: right; }
    .r-products tbody td {
        font-size: 10.5px;
        padding: 2px 0;
        vertical-align: top;
        border-bottom: 1px dotted #ccc;
    }
    .r-products tbody td:last-child { text-align: right; white-space: nowrap; }
    .r-product-qty { font-size: 9.5px; color: #555; }

    /* Totales */
    .r-totals {
        border-top: 1px dashed #000;
        border-bottom: 1px dashed #000;
        padding: 3px 0;
        margin: 4px 0;
    }
    .r-totals .r-total-row {
        display: flex;
        justify-content: space-between;
        padding: 1.5px 0;
        font-size: 10.5px;
    }
    .r-totals .r-total-row.r-gran-total {
        font-size: 13px;
        font-weight: bold;
        border-top: 1px solid #000;
        padding-top: 3px;
        margin-top: 2px;
    }
    .r-totals .r-total-row .r-total-label { font-weight: bold; }

    /* Pagos */
    .r-payments {
        padding: 3px 0;
        border-bottom: 1px dashed #000;
        margin-bottom: 4px;
    }
    .r-payments .r-pay-title {
        font-size: 9.5px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 2px;
    }
    .r-payments .r-pay-row {
        display: flex;
        justify-content: space-between;
        font-size: 10.5px;
        padding: 1px 0;
    }

    /* Pie fiscal / QR */
    .r-fiscal-footer {
        text-align: center;
        padding: 4px 0;
        margin-top: 4px;
    }
    .r-fiscal-footer .r-ecf-label {
        font-size: 9.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .r-fiscal-footer .r-ecf-detail {
        font-size: 9px;
        color: #555;
        margin: 1px 0;
    }
    .r-fiscal-footer .r-qr-img {
        display: block;
        width: 100px;
        height: 100px;
        margin: 4px auto;
    }
    .r-fiscal-footer .r-dgii-url {
        font-size: 7.5px;
        color: #888;
    }

    /* Pie agradecimiento */
    .r-gracias {
        text-align: center;
        font-size: 11px;
        font-weight: bold;
        padding: 6px 0 2px;
        border-top: 1px dashed #000;
        margin-top: 4px;
    }
    .r-nota-footer {
        text-align: center;
        font-size: 9px;
        color: #777;
        margin-top: 2px;
    }
</style>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  ENCABEZADO DE LA EMPRESA                     ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    <div class="r-header">
        @if ( ns()->option->get( 'ns_invoice_receipt_logo' ) )
            <img src="{{ ns()->option->get( 'ns_invoice_receipt_logo' ) }}" alt="{{ $razonSocial }}" style="max-width: 60mm; max-height: 18mm; margin-bottom: 2px;">
        @endif
        <div class="empresa-nombre">{{ $razonSocial }}</div>
        @if($nombreComercial && $nombreComercial !== $razonSocial)
            <div class="empresa-comercial">{{ $nombreComercial }}</div>
        @endif
        
        @if($esFiscal && $rncEmisor)
            <div class="empresa-rnc">RNC: {{ $rncEmisor }}</div>
        @endif
        
        @if($direccion)
            <div class="empresa-datos">{{ $direccion }}</div>
        @endif
        @if($telefono)
            <div class="empresa-datos">Tel: {{ $telefono }}</div>
        @endif
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  TIPO DE COMPROBANTE (SOLO FISCAL)            ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    @if($esFiscal)
        <div class="r-tipo-badge">
            <div class="r-tipo-titulo">{{ $tipo['titulo'] }}</div>
            <span class="r-tipo-indicador">{{ $tipo['indicador'] }}</span>
            @if($ncfNumber)
                <div class="r-ncf-number">NCF: {{ $ncfNumber }}</div>
            @endif
            <div class="r-ncf-vencimiento">Venc. NCF: 31/12/{{ date('Y') }}</div>
        </div>
    @else
        <div class="r-tipo-badge" style="border-bottom: none; padding: 2px 0;">
            <div class="r-tipo-titulo" style="font-size: 11px; letter-spacing: 1px;">TICKET DE VENTA</div>
        </div>
    @endif

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  DATOS DE LA ORDEN Y CAJERO                   ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    <div class="r-info-block">
        <div class="r-row">
            <span class="r-label">Orden:</span>
            <span class="r-value">{{ $order->code }}</span>
        </div>
        <div class="r-row">
            <span class="r-label">Fecha:</span>
            <span class="r-value">{{ date('d/m/Y h:i A', strtotime($order->created_at)) }}</span>
        </div>
        @if($order->user)
        <div class="r-row">
            <span class="r-label">Cajero:</span>
            <span class="r-value">{{ $order->user->username ?? '' }}</span>
        </div>
        @endif
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  DATOS DEL COMPRADOR (CLIENTE)                ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    <div class="r-info-block">
        @if($esFiscal)
            @if(!$isGenericCustomer)
                <div class="r-row">
                    <span class="r-label">RNC/Cédula:</span>
                    <span class="r-value">{{ $rncBuyer }}</span>
                </div>
                <div class="r-row">
                    <span class="r-label">Razón Social:</span>
                    <span class="r-value">{{ $buyerName }}</span>
                </div>
            @else
                <div class="r-row">
                    <span class="r-label">Cliente:</span>
                    <span class="r-value">CONSUMIDOR FINAL</span>
                </div>
            @endif
        @else
            <div class="r-row">
                <span class="r-label">Cliente:</span>
                <span class="r-value">{{ $order->customer ? trim($order->customer->first_name . ' ' . $order->customer->last_name) : 'CLIENTE CONTADO' }}</span>
            </div>
        @endif
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  DETALLE DE PRODUCTOS                         ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    <table class="r-products">
        <thead>
            <tr>
                <th style="width: 55%;">Descripción</th>
                <th style="width: 20%; text-align: right;">Precio</th>
                <th style="width: 25%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach( Hook::filter( 'ns-receipt-products', $order->combinedProducts ) as $product )
            <tr>
                <td>
                    {{ $product->name }}
                    <br><span class="r-product-qty">{{ (int)$product->quantity }} x {{ ns()->currency->define( $product->unit_price ) }}</span>
                </td>
                <td style="text-align: right;">{{ ns()->currency->define( $product->unit_price ) }}</td>
                <td style="text-align: right;">{{ ns()->currency->define( $product->total_price ) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  DESGLOSE DE TOTALES                          ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    <div class="r-totals">
        @if ( ns()->option->get( 'ns_invoice_show_subtotal', 'yes' ) === 'yes' )
        <div class="r-total-row">
            <span class="r-total-label">Subtotal:</span>
            <span>{{ ns()->currency->define( $order->subtotal ) }}</span>
        </div>
        @endif

        @if ( $order->discount > 0 )
        <div class="r-total-row">
            <span class="r-total-label">Descuento{{ $order->discount_type === 'percentage' ? ' ('.$order->discount_percentage.'%)' : '' }}:</span>
            <span>-{{ ns()->currency->define( $order->discount ) }}</span>
        </div>
        @endif

        @if( $itbis > 0 )
        <div class="r-total-row">
            <span class="r-total-label">ITBIS (18%):</span>
            <span>{{ ns()->currency->define( $itbis ) }}</span>
        </div>
        @endif

        @if ( $order->shipping > 0 )
        <div class="r-total-row">
            <span class="r-total-label">Envío:</span>
            <span>{{ ns()->currency->define( $order->shipping ) }}</span>
        </div>
        @endif

        <div class="r-total-row r-gran-total">
            <span class="r-total-label">Total:</span>
            <span>{{ ns()->currency->define( $order->total ) }}</span>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  FORMAS DE PAGO                               ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    @if ( ns()->option->get( 'ns_invoice_show_payment_rows', 'yes' ) === 'yes' )
    <div class="r-payments">
        <div class="r-pay-title">Forma de Pago</div>
        @foreach( $order->payments as $payment )
        <div class="r-pay-row">
            <span>{{ $paymentTypes[ $payment[ 'identifier' ] ] ?? __( 'Otro' ) }}</span>
            <span>{{ ns()->currency->define( $payment[ 'value' ] ) }}</span>
        </div>
        @endforeach
        <div class="r-pay-row" style="font-weight: bold;">
            <span>Recibido:</span>
            <span>{{ ns()->currency->define( $order->tendered ) }}</span>
        </div>
        @if ( in_array( $order->payment_status, [ 'paid' ]) && $order->change > 0 )
        <div class="r-pay-row" style="font-weight: bold;">
            <span>Cambio:</span>
            <span>{{ ns()->currency->define( $order->change ) }}</span>
        </div>
        @endif
        @if ( $order->payment_status === 'partially_paid' )
        <div class="r-pay-row" style="font-weight: bold; color: #922b21;">
            <span>Pendiente:</span>
            <span>{{ ns()->currency->define( abs( $order->change ) ) }}</span>
        </div>
        @endif
    </div>
    @endif

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  NOTA DE LA ORDEN                             ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    @if( $order->note_visibility === 'visible' && $order->note )
    <div style="font-size: 10px; text-align: center; padding: 3px 0; border-bottom: 1px dashed #000;">
        <strong>Nota:</strong> {{ $order->note }}
    </div>
    @endif

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  ESTAMPA DIGITAL DGII (SOLO FISCAL)           ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    @if($esFiscal && $ncfNumber)
    <div class="r-fiscal-footer">
        <div class="r-ecf-label">Estampa Digital e-CF</div>
        @if($securityCode)
            <div class="r-ecf-detail">Código Seguridad: <strong>{{ $securityCode }}</strong></div>
        @endif
        @if($trackId)
            <div class="r-ecf-detail">TrackID: {{ $trackId }}</div>
        @endif
        <img class="r-qr-img" src="{{ $qrImageUrl }}" alt="QR DGII">
        <div class="r-dgii-url">Consulte en: https://ecf.dgii.gov.do</div>
    </div>
    @endif

    <!-- ════════════════════════════════════════════════════ -->
    <!-- ░░  PIE DEL RECIBO                               ░░ -->
    <!-- ════════════════════════════════════════════════════ -->
    <div class="r-gracias">
        ¡GRACIAS POR SU COMPRA!
    </div>
    @if( ns()->option->get( 'ns_invoice_receipt_footer' ) )
    <div class="r-nota-footer">
        {{ ns()->option->get( 'ns_invoice_receipt_footer' ) }}
    </div>
    @endif

</div>
@includeWhen( request()->query( 'autoprint' ) === 'true', '/pages/dashboard/orders/templates/_autoprint' )
