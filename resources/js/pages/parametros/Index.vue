<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
    form.put('/parametros', { preserveScroll: true });
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

                <div class="grid grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <Label>Chico</Label>
                        <Input
                            v-model="form.pago_default_chico"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <p v-if="form.errors.pago_default_chico" class="text-destructive text-xs">
                            {{ form.errors.pago_default_chico }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <Label>Mediano</Label>
                        <Input
                            v-model="form.pago_default_mediano"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <p v-if="form.errors.pago_default_mediano" class="text-destructive text-xs">
                            {{ form.errors.pago_default_mediano }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <Label>Grande</Label>
                        <Input
                            v-model="form.pago_default_grande"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <p v-if="form.errors.pago_default_grande" class="text-destructive text-xs">
                            {{ form.errors.pago_default_grande }}
                        </p>
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
                            <p v-if="form.errors[row.mediano]" class="text-destructive text-xs">
                                {{ form.errors[row.mediano] }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <Input
                                v-model="form[row.grande]"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-28"
                            />
                            <p v-if="form.errors[row.grande]" class="text-destructive text-xs">
                                {{ form.errors[row.grande] }}
                            </p>
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
                    <p v-if="form.errors.dias_bono_septimo" class="text-destructive text-xs">
                        {{ form.errors.dias_bono_septimo }}
                    </p>
                </div>
            </fieldset>

            <Button type="submit" :disabled="form.processing">
                Guardar parámetros
            </Button>
        </form>
    </div>
</template>
