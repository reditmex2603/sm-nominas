<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

interface Parametro { id: number; clave: string; valor: string; descripcion: string | null }

const props = defineProps<{
    parametros: Record<string, Parametro>;
}>();

const form = useForm({
    pago_default_chico: props.parametros['pago_default_chico']?.valor ?? '1500',
    pago_default_mediano: props.parametros['pago_default_mediano']?.valor ?? '2500',
    pago_default_grande: props.parametros['pago_default_grande']?.valor ?? '3000',
    dias_bono_septimo: props.parametros['dias_bono_septimo']?.valor ?? '6',
    bono_evento_encargado_nivel1_mediano: props.parametros['bono_evento_encargado_nivel1_mediano']?.valor ?? '500',
    bono_evento_encargado_nivel1_grande: props.parametros['bono_evento_encargado_nivel1_grande']?.valor ?? '700',
    bono_evento_encargado_nivel2_mediano: props.parametros['bono_evento_encargado_nivel2_mediano']?.valor ?? '600',
    bono_evento_encargado_nivel2_grande: props.parametros['bono_evento_encargado_nivel2_grande']?.valor ?? '840',
    bono_evento_tecnico_nivel1_mediano: props.parametros['bono_evento_tecnico_nivel1_mediano']?.valor ?? '350',
    bono_evento_tecnico_nivel1_grande: props.parametros['bono_evento_tecnico_nivel1_grande']?.valor ?? '500',
    bono_evento_tecnico_nivel2_mediano: props.parametros['bono_evento_tecnico_nivel2_mediano']?.valor ?? '420',
    bono_evento_tecnico_nivel2_grande: props.parametros['bono_evento_tecnico_nivel2_grande']?.valor ?? '600',
    bono_evento_stagehand_nivel1_mediano: props.parametros['bono_evento_stagehand_nivel1_mediano']?.valor ?? '200',
    bono_evento_stagehand_nivel1_grande: props.parametros['bono_evento_stagehand_nivel1_grande']?.valor ?? '300',
    bono_evento_stagehand_nivel2_mediano: props.parametros['bono_evento_stagehand_nivel2_mediano']?.valor ?? '240',
    bono_evento_stagehand_nivel2_grande: props.parametros['bono_evento_stagehand_nivel2_grande']?.valor ?? '360',
});

type ClaveBonoEvento =
    'bono_evento_encargado_nivel1_mediano' | 'bono_evento_encargado_nivel1_grande' |
    'bono_evento_encargado_nivel2_mediano' | 'bono_evento_encargado_nivel2_grande' |
    'bono_evento_tecnico_nivel1_mediano' | 'bono_evento_tecnico_nivel1_grande' |
    'bono_evento_tecnico_nivel2_mediano' | 'bono_evento_tecnico_nivel2_grande' |
    'bono_evento_stagehand_nivel1_mediano' | 'bono_evento_stagehand_nivel1_grande' |
    'bono_evento_stagehand_nivel2_mediano' | 'bono_evento_stagehand_nivel2_grande';

const categoriasEventoRows: { label: string; mediano: ClaveBonoEvento; grande: ClaveBonoEvento }[] = [
    { label: 'Encargado de área — Nivel 1', mediano: 'bono_evento_encargado_nivel1_mediano', grande: 'bono_evento_encargado_nivel1_grande' },
    { label: 'Encargado de área — Nivel 2', mediano: 'bono_evento_encargado_nivel2_mediano', grande: 'bono_evento_encargado_nivel2_grande' },
    { label: 'Técnico — Nivel 1', mediano: 'bono_evento_tecnico_nivel1_mediano', grande: 'bono_evento_tecnico_nivel1_grande' },
    { label: 'Técnico — Nivel 2', mediano: 'bono_evento_tecnico_nivel2_mediano', grande: 'bono_evento_tecnico_nivel2_grande' },
    { label: 'Stagehand SM — Nivel 1', mediano: 'bono_evento_stagehand_nivel1_mediano', grande: 'bono_evento_stagehand_nivel1_grande' },
    { label: 'Stagehand SM — Nivel 2', mediano: 'bono_evento_stagehand_nivel2_mediano', grande: 'bono_evento_stagehand_nivel2_grande' },
];

const guardar = () => {
    intentado.value = true;

    if (Object.keys(errores.value).length > 0) {
        return;
    }

    form.put('/parametros', { preserveScroll: true });
};

// ── Validación cliente (montos ≥ 0, bono 7° día entre 1 y 6) ──────
const intentado = ref(false);

const CAMPOS_MONTO = [
    'pago_default_chico', 'pago_default_mediano', 'pago_default_grande',
    ...categoriasEventoRows.flatMap(r => [r.mediano, r.grande]),
] as const;

const errores = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    for (const clave of CAMPOS_MONTO) {
        const v = Number(form[clave]);

        if (form[clave] === '' || Number.isNaN(v) || v < 0) {
            e[clave] = 'Debe ser un número mayor o igual a 0.';
        }
    }

    const dias = Number(form.dias_bono_septimo);

    if (!Number.isInteger(dias) || dias < 1 || dias > 6) {
        e.dias_bono_septimo = 'Debe ser un entero entre 1 y 6.';
    }

    return e;
});

const msg = (campo: string): string => {
    const cliente = errores.value[campo];

    if (intentado.value && cliente) {
        return cliente;
    }

    return (form.errors as Record<string, string>)[campo] ?? '';
};
</script>

<template>
    <Head title="Parámetros del sistema" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold">Parámetros del sistema</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Configuración global de la aplicación
            </p>
        </div>

        <form class="max-w-xl space-y-6" @submit.prevent="guardar">
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="text-sm font-medium px-1">Pago por defecto al crear evento</legend>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="space-y-1">
                        <Label>Chico</Label>
                        <Input
                            v-model="form.pago_default_chico"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <InputError :message="msg('pago_default_chico')" />
                    </div>
                    <div class="space-y-1">
                        <Label>Mediano</Label>
                        <Input
                            v-model="form.pago_default_mediano"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <InputError :message="msg('pago_default_mediano')" />
                    </div>
                    <div class="space-y-1">
                        <Label>Grande</Label>
                        <Input
                            v-model="form.pago_default_grande"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <InputError :message="msg('pago_default_grande')" />
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="text-sm font-medium px-1">Extra por día de evento (Base), según categoría, nivel y tamaño del evento</legend>

                <div class="grid grid-cols-[1fr_auto_auto] items-center gap-x-4 gap-y-2">
                    <span></span>
                    <Label class="text-muted-foreground text-center text-xs">Mediano</Label>
                    <Label class="text-muted-foreground text-center text-xs">Grande</Label>

                    <template v-for="row in categoriasEventoRows" :key="row.label">
                        <Label class="whitespace-nowrap">{{ row.label }}</Label>
                        <div class="space-y-1">
                            <Input
                                v-model="form[row.mediano]"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-28"
                            />
                            <InputError :message="msg(row.mediano)" />
                        </div>
                        <div class="space-y-1">
                            <Input
                                v-model="form[row.grande]"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-28"
                            />
                            <InputError :message="msg(row.grande)" />
                        </div>
                    </template>
                </div>
                <p class="text-muted-foreground text-xs">
                    Se suma una vez por cada día calificado de evento (montaje/show/desmontaje) en el período, ponderado por el % de etapas trabajadas; en traslape también se prorratea por el % que capture el admin. Los eventos Chico nunca generan este extra.
                </p>
            </fieldset>

            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="text-sm font-medium px-1">Bono de 7° día (base)</legend>

                <div class="space-y-1 max-w-xs">
                    <Label>Días requeridos lunes–sábado para generar bono</Label>
                    <Input
                        v-model="form.dias_bono_septimo"
                        type="number"
                        min="1"
                        max="6"
                        step="1"
                    />
                    <p class="text-muted-foreground text-xs">
                        Valor habitual: 6 (semana completa L–S)
                    </p>
                    <InputError :message="msg('dias_bono_septimo')" />
                </div>
            </fieldset>

            <Button type="submit" :disabled="form.processing" class="gap-1.5">
                <Spinner v-if="form.processing" class="size-4" />
                {{ form.processing ? 'Guardando…' : 'Guardar parámetros' }}
            </Button>
        </form>
    </div>
</template>
