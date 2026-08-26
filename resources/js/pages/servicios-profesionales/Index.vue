<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { fmtFecha } from '@/lib/fecha';
import * as serviciosProfesionales from '@/routes/servicios-profesionales';

type TipoServicio = 'RIGGER' | 'OPERADOR_AUDIO' | 'OPERADOR_VIDEO' | 'OPERADOR_LUZ' | 'OTRO';

interface EventoRef { id: number; nombre: string }

interface Servicio {
    id: number;
    nombre: string;
    apellidos: string | null;
    tipo: TipoServicio;
    evento_id: number | null;
    concepto: string;
    monto: string;
    fecha: string;
    autoriza: string | null;
    evento: EventoRef | null;
}

defineProps<{
    servicios: Servicio[];
    eventos: EventoRef[];
}>();

const tipoOpciones: { value: TipoServicio; label: string }[] = [
    { value: 'RIGGER',          label: 'Rigger' },
    { value: 'OPERADOR_AUDIO',  label: 'Operador Audio' },
    { value: 'OPERADOR_VIDEO',  label: 'Operador Video' },
    { value: 'OPERADOR_LUZ',    label: 'Operador Luz' },
    { value: 'OTRO',            label: 'Otro' },
];

const tipoBadgeClass: Record<TipoServicio, string> = {
    RIGGER:          'bg-purple-100 text-purple-800',
    OPERADOR_AUDIO:  'bg-blue-100 text-blue-800',
    OPERADOR_VIDEO:  'bg-cyan-100 text-cyan-800',
    OPERADOR_LUZ:    'bg-orange-100 text-orange-800',
    OTRO:            'bg-slate-100 text-slate-700',
};

const tipoLabel = (t: TipoServicio) => tipoOpciones.find(o => o.value === t)?.label ?? t;

const today    = new Date().toISOString().split('T')[0];
const showForm = ref(false);

const form = useForm({
    nombre:     '',
    apellidos:  '',
    tipo:       'RIGGER' as TipoServicio,
    evento_id:  '' as string,
    concepto:   '',
    monto:      '' as string,
    fecha:      today,
    autoriza:   '',
});

const intentado = ref(false);

const erroresForm = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    if (!form.nombre.trim()) {
        e.nombre = 'El nombre es obligatorio.';
    }

    if (!form.concepto.trim()) {
        e.concepto = 'El concepto es obligatorio.';
    }

    if (!form.monto) {
        e.monto = 'El monto es obligatorio.';
    }

    if (!form.fecha) {
        e.fecha = 'La fecha es obligatoria.';
    }

    return e;
});

const msg = (campo: string): string => {
    const cliente = erroresForm.value[campo];

    if (intentado.value && cliente) {
        return cliente;
    }

    return (form.errors as Record<string, string>)[campo] ?? '';
};

watch(() => showForm, (open) => {
    if (open) {
        intentado.value = false;
    }
});

const submit = () => {
    intentado.value = true;

    if (Object.keys(erroresForm.value).length > 0) {
        return;
    }

    form.post(serviciosProfesionales.store.url(), {
        onSuccess: () => {
            showForm.value = false;
            intentado.value = false;
            form.reset();
            form.fecha = today;
            form.tipo = 'RIGGER';
        },
    });
};
</script>

<template>
    <Head title="Servicios Profesionales" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Servicios Profesionales</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ servicios.length }} registros · Solo creación (no editable)
                </p>
            </div>

            <Button @click="showForm = true">
                <Plus class="size-4" />
                Nuevo servicio
            </Button>
        </div>

        <Dialog :open="showForm" @update:open="showForm = $event">
            <DialogContent class="w-full max-w-md">
                <DialogHeader>
                    <DialogTitle>Registrar servicio profesional</DialogTitle>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-1">
                            <Label>Nombre <span class="text-destructive">*</span></Label>
                            <Input v-model="form.nombre" maxlength="255" required />
                            <InputError :message="msg('nombre')" />
                        </div>
                        <div class="space-y-1">
                            <Label>Apellidos</Label>
                            <Input v-model="form.apellidos" maxlength="255" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <Label>Tipo <span class="text-destructive">*</span></Label>
                        <Select v-model="form.tipo">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="op in tipoOpciones" :key="op.value" :value="op.value">
                                    {{ op.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1">
                        <Label>Evento</Label>
                        <Select v-model="form.evento_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Sin evento" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Sin evento</SelectItem>
                                <SelectItem v-for="e in eventos" :key="e.id" :value="String(e.id)">
                                    {{ e.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1">
                        <Label>Concepto <span class="text-destructive">*</span></Label>
                        <Input v-model="form.concepto" placeholder="Descripción del servicio" maxlength="500" required />
                        <InputError :message="msg('concepto')" />
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-1">
                            <Label>Monto <span class="text-destructive">*</span></Label>
                            <Input v-model="form.monto" type="number" step="0.01" min="0" inputmode="decimal" required />
                            <InputError :message="msg('monto')" />
                        </div>
                        <div class="space-y-1">
                            <Label>Fecha <span class="text-destructive">*</span></Label>
                            <Input v-model="form.fecha" type="date" required />
                            <InputError :message="msg('fecha')" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <Label>Autoriza</Label>
                        <Input v-model="form.autoriza" placeholder="Nombre de quien autoriza" maxlength="255" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showForm = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing" class="gap-1.5">
                            <Spinner v-if="form.processing" class="size-4" />
                            {{ form.processing ? 'Registrando…' : 'Registrar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Tabla (solo lectura) — escritorio -->
        <div class="hidden overflow-x-auto rounded-xl border lg:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">Evento</th>
                        <th class="px-4 py-3 text-left font-medium">Concepto</th>
                        <th class="px-4 py-3 text-right font-medium">Monto</th>
                        <th class="px-4 py-3 text-left font-medium">Autoriza</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="servicios.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            Sin servicios profesionales registrados.
                        </td>
                    </tr>
                    <tr v-for="s in servicios" :key="s.id" class="transition-colors hover:bg-muted/30">
                        <td class="px-4 py-3 whitespace-nowrap tabular-nums">{{ fmtFecha(s.fecha) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                            {{ s.apellidos ? `${s.apellidos}, ` : '' }}{{ s.nombre }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="tipoBadgeClass[s.tipo]"
                            >
                                {{ tipoLabel(s.tipo) }}
                            </span>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 whitespace-nowrap">
                            {{ s.evento?.nombre ?? '—' }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3 max-w-xs">{{ s.concepto }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium">
                            ${{ parseFloat(s.monto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3">{{ s.autoriza ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cards móvil (< lg) -->
        <div class="flex flex-col gap-3 lg:hidden">
            <div v-if="servicios.length === 0" class="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                Sin servicios profesionales registrados.
            </div>

            <div v-for="s in servicios" :key="s.id" class="rounded-xl border p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ s.apellidos ? `${s.apellidos}, ` : '' }}{{ s.nombre }}</p>
                        <p class="text-muted-foreground mt-0.5 text-xs tabular-nums">{{ fmtFecha(s.fecha) }} · {{ s.evento?.nombre ?? 'Sin evento' }}</p>
                    </div>
                    <span
                        class="flex-shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                        :class="tipoBadgeClass[s.tipo]"
                    >
                        {{ tipoLabel(s.tipo) }}
                    </span>
                </div>

                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Concepto</dt>
                        <dd class="text-right">{{ s.concepto }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Autoriza</dt>
                        <dd class="text-right">{{ s.autoriza ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t pt-2">
                        <dt class="text-muted-foreground text-xs">Monto</dt>
                        <dd class="font-medium tabular-nums">
                            ${{ parseFloat(s.monto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>