<template>
    <div>
        <div class="shadow-lg w-[95vw] md:w-[65vw] lg:w-[55vw] ns-box" v-if="loaded">
            <div class="border-b ns-box-header p-2 text-fontcolor flex justify-between items-center">
                <h3 class="font-semibold">{{ title }}</h3>
                <div class="flex items-center gap-2">
                    <button 
                        v-if="isCloseAction"
                        type="button" 
                        class="px-3 py-1 bg-info-primary text-white font-bold rounded hover:bg-info-secondary text-sm flex items-center gap-1 cursor-pointer"
                        @click="printPreClose()"
                    >
                        🖨️ Imprimir Pre-Cierre (80mm)
                    </button>
                    <ns-close-button @click="close()"></ns-close-button>
                </div>
            </div>
            <div class="p-3">
                <!-- Botón Imprimir Pre-Cierre (Solo Cierre) -->
                <div v-if="isCloseAction">
                    <button 
                        type="button" 
                        class="w-full py-2.5 mb-3 bg-info-primary text-white font-bold text-base rounded shadow hover:bg-info-secondary cursor-pointer flex items-center justify-center gap-2"
                        @click="printPreClose()"
                    >
                        🖨️ IMPRIMIR PRE-CIERRE (80mm) - VER DESGLOSE
                    </button>
                </div>

                <!-- Modos de Conteo (Apertura o Cierre) -->
                <div v-if="hasBreakdownSupport" class="flex border-b mb-3">
                    <button 
                        type="button" 
                        class="px-4 py-2 font-bold text-sm border-b-2 cursor-pointer transition-colors"
                        :class="showBreakdown ? 'border-primary-tertiary text-primary-tertiary' : 'border-transparent text-gray-500'"
                        @click="showBreakdown = true"
                    >
                        💵 Desglose por Billetes/Monedas
                    </button>
                    <button 
                        type="button" 
                        class="px-4 py-2 font-bold text-sm border-b-2 cursor-pointer transition-colors"
                        :class="!showBreakdown ? 'border-primary-tertiary text-primary-tertiary' : 'border-transparent text-gray-500'"
                        @click="showBreakdown = false"
                    >
                        🔢 Teclado / Directo
                    </button>
                </div>

                <!-- Resumen de Esperado vs Contado -->
                <div>
                    <div v-if="isCloseAction && register !== null" class="mb-2 p-3 elevation-surface font-bold border text-right flex justify-between">
                        <span>{{ __( 'Efectivo Esperado en Caja' ) }} </span>
                        <span>{{ nsCurrency( register.balance ) }}</span>
                    </div>
                    <div class="mb-2 p-3 bg-success-primary border-success-tertiary border font-bold text-right flex justify-between">
                        <span>{{ isOpenAction ? __( 'Fondo Inicial de Apertura' ) : __( 'Efectivo Contado (Total)' ) }}</span>
                        <span class="text-xl">{{ nsCurrency( amount ) }}</span>
                    </div>

                    <!-- Alerta de Faltante / Cuadre para Cierre -->
                    <div v-if="isCloseAction && register !== null" class="mb-3">
                        <div v-if="cashShortage > 0.01" class="p-3 bg-red-600 text-white font-bold rounded text-center text-sm shadow">
                            🚨 FALTANTE DETECTADO: -{{ nsCurrency( cashShortage ) }}<br>
                            <span class="text-xs font-normal">No se permite cerrar la caja con faltante de efectivo. Verifique el dinero ingresado.</span>
                        </div>
                        <div v-else-if="cashShortage < -0.01" class="p-3 bg-blue-600 text-white font-bold rounded text-center text-sm shadow">
                            ℹ️ SOBRANTE EN CAJA: +{{ nsCurrency( Math.abs(cashShortage) ) }} (Cuadre permitido)
                        </div>
                        <div v-else class="p-3 bg-green-600 text-white font-bold rounded text-center text-sm shadow">
                            ✅ CUADRE PERFECTO DE CAJA
                        </div>
                    </div>
                </div>

                <!-- Vista de Desglose de Billetes y Monedas -->
                <div v-if="hasBreakdownSupport && showBreakdown" class="mb-3 border p-3 rounded bg-gray-50 dark:bg-gray-800">
                    <h4 class="font-bold text-sm mb-2 text-gray-700 dark:text-gray-200">Conteo de Billetes y Monedas:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-56 overflow-y-auto p-1">
                        <div v-for="(d, idx) in denominations" :key="idx" class="flex items-center justify-between bg-white dark:bg-gray-700 p-2 rounded border">
                            <span class="font-bold text-sm w-28">{{ d.label }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">cant:</span>
                                <input 
                                    type="number" 
                                    min="0" 
                                    v-model.number="d.qty" 
                                    @input="updateBreakdownTotal()" 
                                    class="w-20 p-1 border rounded text-right font-bold text-sm bg-white dark:bg-gray-900 text-black dark:text-white"
                                >
                            </div>
                            <span class="font-bold text-xs text-right w-24">{{ nsCurrency( (d.qty || 0) * d.value ) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Teclado Numérico Directo -->
                <div v-show="! ( hasBreakdownSupport && showBreakdown )" class="flex flex-col md:flex-row md:-mx-2">
                    <div class="md:px-2 md:w-1/2 w-full">
                        <ns-numpad :floating="true" @next="submit( $event )" :value="amount" @changed="definedValue( $event )"></ns-numpad>
                    </div>
                    <div class="md:px-2 md:w-1/2 w-full">
                        <ns-field v-for="(field,index) of fields" :field="field" :key="index"></ns-field>
                    </div>
                </div>

                <!-- Campo de Observación para Desglose -->
                <div v-if="hasBreakdownSupport && showBreakdown" class="mt-2">
                    <ns-field v-for="(field,index) of fields" :field="field" :key="index"></ns-field>
                    <button 
                        type="button" 
                        class="w-full py-3 mt-3 text-white font-bold text-base rounded shadow cursor-pointer transition-colors"
                        :class="(isCloseAction && cashShortage > 0.01) ? 'bg-gray-400 cursor-not-allowed' : 'bg-success-primary hover:bg-success-secondary'"
                        :disabled="isCloseAction && cashShortage > 0.01"
                        @click="submit( amount )"
                    >
                        <template v-if="isOpenAction">
                            ✅ CONFIRMAR Y ABRIR CAJA
                        </template>
                        <template v-else>
                            {{ cashShortage > 0.01 ? '⛔ CIERRE BLOQUEADO POR FALTANTE' : '✅ CONFIRMAR Y CERRAR CAJA' }}
                        </template>
                    </button>
                </div>
            </div>
        </div>
        <div class="h-full w-full flex items-center justify-center" v-if="! loaded">
            <ns-spinner></ns-spinner>
        </div>
    </div>
</template>
<script>
import FormValidation from '~/libraries/form-validation';
import popupCloser from '~/libraries/popup-closer';
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
import { __ } from '~/libraries/lang';
import { nsCurrency } from "~/filters/currency";

export default {
    components: {
        // ...
    },
    props: [ 'popup' ],
    data() {
        return {
            amount: 0,
            title: null,
            identifier: null,
            settingsSubscription: null,
            settings: null,
            action: null,
            register: null,
            loaded: false,
            register_id: null,
            validation: new FormValidation,
            fields: [],
            isSubmitting: false,
            showBreakdown: true,
            denominations: [
                { value: 2000, label: 'RD$ 2,000', qty: 0 },
                { value: 1000, label: 'RD$ 1,000', qty: 0 },
                { value: 500,  label: 'RD$ 500',   qty: 0 },
                { value: 200,  label: 'RD$ 200',   qty: 0 },
                { value: 100,  label: 'RD$ 100',   qty: 0 },
                { value: 50,   label: 'RD$ 50',    qty: 0 },
                { value: 25,   label: 'RD$ 25',    qty: 0 },
                { value: 10,   label: 'RD$ 10',    qty: 0 },
                { value: 5,    label: 'RD$ 5',     qty: 0 },
                { value: 1,    label: 'RD$ 1',     qty: 0 },
            ],
        }
    },
    computed: {
        isCloseAction() {
            return this.action === 'close' || this.action === 'closing' || this.identifier === 'ns.cash-registers-closing';
        },
        isOpenAction() {
            return this.action === 'open' || this.action === 'opening' || this.action === 'register-opening' || this.identifier === 'ns.cash-registers-opening';
        },
        hasBreakdownSupport() {
            return this.isCloseAction || this.isOpenAction;
        },
        cashShortage() {
            if ( ! this.register || this.register.balance === undefined ) return 0;
            const expected = parseFloat( this.register.balance || 0 );
            const entered = parseFloat( this.amount || 0 );
            return expected - entered;
        }
    },
    mounted() {
        this.title                  =   this.popup.params.title;
        this.identifier             =   this.popup.params.identifier;
        this.register               =   this.popup.params.register;
        this.action                 =   this.popup.params.action;
        this.register_id            =   this.popup.params.register_id;
        this.settingsSubscription   =   POS.settings.subscribe( settings => {
            this.settings           =   settings;
        });
        this.loadFields();
        this.popupCloser();
    },
    unmounted() {
        this.settingsSubscription.unsubscribe();
    },
    methods: {
        popupCloser,
        nsCurrency,
        __,

        definedValue( value ) {
            this.amount     =   value;
        },
        updateBreakdownTotal() {
            let total = 0;
            this.denominations.forEach( d => {
                const qty = parseInt( d.qty ) || 0;
                total += qty * d.value;
            });
            this.amount = total;
        },
        printPreClose() {
            const targetRegisterId = this.register_id || (this.settings && this.settings.register ? this.settings.register.id : null);
            if ( targetRegisterId ) {
                const activeDenoms = this.denominations.filter( d => parseInt( d.qty ) > 0 );
                sessionStorage.setItem( 'pos_temp_denominations', JSON.stringify( activeDenoms ) );
                const baseUrl = window.location.origin;
                window.open( `${baseUrl}/dashboard/cash-registers/z-report-thermal/${targetRegisterId}?autoprint=true`, '_blank', 'width=420,height=650' );
            } else {
                nsSnackBar.error( __( 'Unable to locate register ID' ) );
            }
        },
        close() {
            this.popup.close();
        },
        loadFields() {
            this.loaded     =   false;
            nsHttpClient.get( `/api/fields/${this.identifier}` )
                .subscribe( result => {
                    this.loaded     =   true;
                    this.fields     =   result;
                }, ( error ) => {
                    this.loaded     =   true;
                    return nsSnackBar.error( error.message, __( 'OKAY' ), { duration : false });
                });            
        },
        submit( amount ) {
            if ( ( this.action === 'close' || this.action === 'closing' || this.identifier === 'ns.cash-registers-closing' ) && this.register ) {
                const expected = parseFloat( this.register.balance || 0 );
                const entered = parseFloat( this.amount || 0 );
                if ( entered < expected ) {
                    const shortage = ( expected - entered ).toFixed(2);
                    return nsSnackBar.error( __( `No se permite cerrar la caja con faltante. Faltan RD$ ${shortage} en efectivo.` ) );
                }
            }

            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: this.popup.params.confirmMessage || __( 'Would you like to confirm your action.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.triggerSubmit();
                    }
                }
            })
        },
        triggerSubmit() {
            if ( this.isSubmitting ) {
                return;
            }

            if ( ( this.action === 'close' || this.action === 'closing' || this.identifier === 'ns.cash-registers-closing' ) && this.register ) {
                const expected = parseFloat( this.register.balance || 0 );
                const entered = parseFloat( this.amount || 0 );
                if ( entered < expected ) {
                    const shortage = ( expected - entered ).toFixed(2);
                    return nsSnackBar.error( __( `No se permite cerrar la caja con faltante. Faltan RD$ ${shortage} en efectivo.` ) );
                }
            }

            if ( this.validation.validateFields( this.fields ) === false ) {
                return nsSnackBar.error( __( 'Please fill all required fields' ));
            }

            this.isSubmitting    =   true;

            const fields    =   this.validation.extractFields( this.fields );
            fields.amount   =   this.amount === '' ? 0 : this.amount;
            fields.denominations = this.denominations.filter( d => parseInt( d.qty ) > 0 );

            const activeDenoms = fields.denominations;
            sessionStorage.setItem( 'pos_temp_denominations', JSON.stringify( activeDenoms ) );

            console.log({ fields })

            nsHttpClient.post( `/api/cash-registers/${this.action}/${this.register_id || this.settings.register.id}`, fields )
                .subscribe({
                    next: result => {
                        const targetRegisterId = this.register_id || (this.settings && this.settings.register ? this.settings.register.id : null);
                        if ( ( this.action === 'close' || this.action === 'closing' ) && targetRegisterId ) {
                            const baseUrl = window.location.origin;
                            window.open( `${baseUrl}/dashboard/cash-registers/z-report-thermal/${targetRegisterId}?autoprint=true`, '_blank', 'width=420,height=650' );
                        }
                        this.popup.params.resolve( result );
                        this.popup.close();
                        nsSnackBar.success( result.message );
                        this.isSubmitting    =   false;
                    }, 
                    error: ( error ) => {
                        nsSnackBar.error( error.message );
                        this.isSubmitting    =   false;
                    }
                });
        },
    }
}
</script>