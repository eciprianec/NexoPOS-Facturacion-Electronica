<?php

namespace Modules\Dgii\Listeners;

use App\Events\OrderAfterCreatedEvent;
use Illuminate\Support\Facades\DB;
use Modules\Dgii\Services\DgiiSequenceService;
use Exception;

class OrderCreatedListener
{
    /**
     * Al crear una orden en NexoPOS, se asigna automáticamente un NCF / e-CF.
     *
     * REGLA DE NEGOCIO DGII:
     *  - Si el cliente es genérico (sin RNC/Cédula) → SOLO e-CF 32 (Consumo Final)
     *  - Si el cliente tiene RNC/Cédula registrado → e-CF 31 (Crédito Fiscal)
     */
    public function handle(OrderAfterCreatedEvent $event)
    {
        $order = $event->order;

        // Evitar duplicados
        $existing = DB::table('nexopos_dgii_invoices')->where('order_id', $order->id)->first();
        if ($existing) {
            return;
        }

        // Obtener datos fiscales del request (si vienen del POS con validación RNC)
        $rncBuyer  = request()->input('dgii_rnc', '');
        $buyerName = request()->input('dgii_name', '');
        $ncfType   = request()->input('dgii_ncf_type', '');

        // Si NO se solicita explícitamente facturación fiscal (rnc vacío y ncf_type vacío), saltamos la asignación fiscal
        if (empty($rncBuyer) && empty($ncfType)) {
            return;
        }

        // Si no vienen del request, intentar obtener del cliente de la orden
        if (empty($rncBuyer) && $order->customer) {
            $order->load('customer');
            // Buscar en campos custom del cliente (rnc, cedula, etc.)
            $customer = $order->customer;
            $rncBuyer  = $customer->rnc ?? $customer->cedula ?? '';
            $buyerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        }

        // Limpiar RNC: solo dígitos
        $rncBuyer = preg_replace('/\D/', '', $rncBuyer);

        // ═══════════════════════════════════════════════════
        // REGLA PRINCIPAL: Cliente genérico = E32 OBLIGATORIO
        // ═══════════════════════════════════════════════════
        $isGenericCustomer = empty($rncBuyer) || strlen($rncBuyer) < 9;

        if ($isGenericCustomer) {
            // Cliente genérico: SOLO Factura de Consumo Final
            $ncfType   = 'E32';
            $rncBuyer  = '';
            $buyerName = 'CONSUMIDOR FINAL';
        } else {
            // Cliente con RNC válido: Crédito Fiscal
            $ncfType = request()->input('dgii_ncf_type', 'E31');

            // Forzar que un RNC válido no reciba un E32
            // (a menos que el cajero explícitamente lo elija)
            if (empty(request()->input('dgii_ncf_type'))) {
                $ncfType = 'E31';
            }
        }

        // Generar NCF de la secuencia correspondiente
        $seqService = new DgiiSequenceService();
        try {
            $seq       = $seqService->getNextNcf($ncfType);
            $ncfNumber = $seq['ncf'];

            DB::table('nexopos_dgii_invoices')->insert([
                'order_id'      => $order->id,
                'ncf'           => $ncfNumber,
                'ecf_type'      => $ncfType,
                'rnc_buyer'     => $rncBuyer ?: null,
                'buyer_name'    => $buyerName ?: 'CONSUMIDOR FINAL',
                'total_amount'  => $order->total ?? 0,
                'tax_amount'    => $order->tax_value ?? 0,
                'track_id'      => 'TRK-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10)),
                'security_code' => strtoupper(substr(md5($ncfNumber . $order->id . time()), 0, 6)),
                'status'        => 'accepted',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            logger()->info("DGII: Orden #{$order->id} → NCF {$ncfNumber} ({$ncfType}) - " .
                ($isGenericCustomer ? 'CONSUMIDOR FINAL' : "RNC: {$rncBuyer}"));

        } catch (Exception $e) {
            logger()->error("DGII ERROR: No se pudo asignar e-CF a Orden #{$order->id}: " . $e->getMessage());
        }
    }
}
