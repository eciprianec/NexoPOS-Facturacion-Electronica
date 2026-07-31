@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col p-6 bg-slate-900 text-white min-h-screen">
    <div class="mb-6 flex justify-between items-center border-b border-slate-700 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-emerald-400">⚙️ Configuración DGII - Facturación Electrónica</h1>
            <p class="text-slate-400 text-sm">Parámetros del emisor y certificado digital e-CF para la República Dominicana</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-900/50 border border-emerald-500 rounded text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('ns.dashboard.dgii-settings-save') }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
        @csrf
        
        <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 space-y-4">
            <h2 class="text-lg font-semibold text-emerald-300 border-b border-slate-700 pb-2">Datos del Contribuyente (Emisor)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">RNC Emisor</label>
                    <input type="text" name="rnc_emisor" value="{{ $settings->rnc_emisor ?? '' }}" placeholder="Ej: 101000000" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Razón Social</label>
                    <input type="text" name="razon_social" value="{{ $settings->razon_social ?? '' }}" placeholder="Ej: MI EMPRESA SRL" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Nombre Comercial</label>
                <input type="text" name="nombre_comercial" value="{{ $settings->nombre_comercial ?? '' }}" placeholder="Ej: MI TIENDA POS" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
            </div>
        </div>

        <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 space-y-4">
            <h2 class="text-lg font-semibold text-emerald-300 border-b border-slate-700 pb-2">Entorno DGII y Certificado Digital PKCS#12</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Ambiente DGII</label>
                    <select name="environment" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                        <option value="testecf" {{ ($settings->environment ?? '') === 'testecf' ? 'selected' : '' }}>Pruebas (TestECF)</option>
                        <option value="certecf" {{ ($settings->environment ?? '') === 'certecf' ? 'selected' : '' }}>Certificación (CertECF)</option>
                        <option value="ecf" {{ ($settings->environment ?? '') === 'ecf' ? 'selected' : '' }}>Producción (e-CF)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Certificado Digital (.p12 / .pfx)</label>
                    <input type="file" name="cert_file" accept=".p12,.pfx" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                    @if(!empty($settings->cert_path))
                        <p class="text-xs text-emerald-400 mt-1">✓ Certificado cargado actualmente</p>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Contraseña del Certificado</label>
                <input type="password" name="cert_password" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center space-x-2">
                    <input type="checkbox" name="auto_send_ecf" value="1" {{ ($settings->auto_send_ecf ?? 1) ? 'checked' : '' }} class="form-checkbox text-emerald-500 rounded bg-slate-900 border-slate-700">
                    <span class="text-sm text-slate-300">Enviar automáticamente los e-CF a la DGII al cobrar la venta en el POS</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-2 rounded font-semibold transition">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>
@endsection
