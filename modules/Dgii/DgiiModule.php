<?php

namespace Modules\Dgii;

use App\Classes\Hook;
use App\Events\OrderAfterCreatedEvent;
use Illuminate\Support\Facades\Event;
use Modules\Dgii\Listeners\OrderCreatedListener;

class DgiiModule
{
    public function __construct()
    {
        // Menú del Dashboard
        Hook::addFilter('ns-dashboard-menus', [$this, 'registerMenus']);

        // Interceptar el template de recibo: siempre usar el recibo fiscal DGII
        Hook::addFilter('ns-web-receipt-template', [$this, 'overrideReceiptTemplate']);

        // Interceptar la URL de impresión del POS
        Hook::addFilter('ns-pos-printing-url', [$this, 'overridePrintingUrl']);

        // Escuchar evento de orden creada para asignar NCF automáticamente
        Event::listen(OrderAfterCreatedEvent::class, [OrderCreatedListener::class, 'handle']);
    }

    /**
     * Reemplazar la plantilla de recibo estándar por la fiscal DGII
     */
    public function overrideReceiptTemplate($template)
    {
        return 'Dgii::_fiscal_receipt';
    }

    /**
     * Usar la ruta de recibo fiscal en el POS
     */
    public function overridePrintingUrl($url)
    {
        // Mantenemos la ruta original de NexoPOS, pero el template será el fiscal
        // gracias al hook ns-web-receipt-template
        return $url;
    }

    public function registerMenus($menus)
    {
        $menus['dgii'] = [
            'label' => 'Facturación DGII',
            'icon' => 'la-file-invoice-dollar',
            'permissions' => ['nexopos.read.settings'],
            'childrens' => [
                'settings' => [
                    'label' => 'Configuración DGII',
                    'href' => ns()->route('ns.dashboard.dgii-settings'),
                ],
                'sequences' => [
                    'label' => 'Secuencias NCF / e-CF',
                    'href' => ns()->route('ns.dashboard.dgii-sequences'),
                ],
                'invoices' => [
                    'label' => 'Histórico e-CF',
                    'href' => ns()->route('ns.dashboard.dgii-invoices'),
                ],
                'reports' => [
                    'label' => 'Reportes 606 / 607 / 608',
                    'href' => ns()->route('ns.dashboard.dgii-reports'),
                ],
            ]
        ];

        return $menus;
    }
}
