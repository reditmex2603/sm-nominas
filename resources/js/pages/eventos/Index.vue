<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
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
import { useConfirm } from '@/composables/useConfirm';
import { fmtFecha } from '@/lib/fecha';

type Tamano = 'CHICO' | 'MEDIANO' | 'GRANDE';

interface Evento {
    id: number;
    nombre: string;
    lugar: string | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    tamano: Tamano;
    pago_por_evento_completo: string;
    nombre_contratante: string | null;
    telefono_contratante: string | null;
}

const props = defineProps<{ eventos: Evento[] }>();

const tamanoBadge: Record<Tamano, { label: string; class: string }> = {
    CHICO: { label: 'Chico', class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' },
    MEDIANO: { label: 'Mediano', class: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' },
    GRANDE: { label: 'Grande', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' },
};

const pagos = ref<Record<number, string>>({});
const guardandoPago = ref<number | null>(null);

// Sincroniza pagos con props.eventos — no solo al montar, sino cada vez que la lista cambia
// (ej. al crear un evento nuevo, Inertia recarga las props sin desmontar el componente, así
// que una inicialización única dejaría la fila nueva sin entrada y rompería el v-model).
watch(() => props.eventos, (lista) => {
    for (const e of lista) {
        if (!(e.id in pagos.value)) {
            pagos.value[e.id] = e.pago_por_evento_completo;
        }
    }
}, { immediate: true, deep: true });

const guardarPago = (evento: Evento) => {
    const valor = pagos.value[evento.id];

    if (valor === evento.pago_por_evento_completo || !Number(valor)) {
        return;
    }

    guardandoPago.value = evento.id;
    router.put(`/eventos/${evento.id}`, { pago_por_evento_completo: valor }, {
        preserveScroll: true,
        onFinish: () => {
 guardandoPago.value = null; 
},
    });
};

const { confirm } = useConfirm();
const eliminando = ref<number | null>(null);

const eliminar = async (evento: Evento) => {
    const ok = await confirm(`¿Eliminar el evento "${evento.nombre}"? Esta acción no se puede deshacer.`, {
        title: 'Eliminar evento',
    });

    if (!ok) {
return;
}

    eliminando.value = evento.id;
    router.delete(`/eventos/${evento.id}`, {
        preserveScroll: true,
        onFinish: () => {
 eliminando.value = null; 
},
    });
};

// ── Formulario de creación ─────────────────────────────────────────
const showCreate = ref(false);
const createForm = useForm({
    nombre: '',
    lugar: '',
    fecha_inicio: '',
    fecha_fin: '',
    tamano: 'MEDIANO' as Tamano,
    nombre_contratante: '',
    telefono_contratante: '',
    contacto_nombre: '',
    contacto_telefono: '',
    descripcion: '',
    observaciones_tecnicas: '',
    enlace_ubicacion: '',
});

const digitos = (v?: string): string => (v ?? '').replace(/\D/g, '');
const telefonoValido = (v: string): boolean => {
    const d = digitos(v);

    return d.length >= 10 && d.length <= 13;
};

const intentado = ref(false);

const erroresForm = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    if (!createForm.nombre.trim()) {
        e.nombre = 'El nombre es obligatorio.';
    }

    if (createForm.fecha_inicio && createForm.fecha_fin && createForm.fecha_fin < createForm.fecha_inicio) {
        e.fecha_fin = 'La fecha de fin no puede ser anterior al inicio.';
    }

    if (createForm.telefono_contratante && !telefonoValido(createForm.telefono_contratante)) {
        e.telefono_contratante = 'Teléfono inválido (10 a 13 dígitos).';
    }

    if (createForm.contacto_telefono && !telefonoValido(createForm.contacto_telefono)) {
        e.contacto_telefono = 'Teléfono inválido (10 a 13 dígitos).';
    }

    if (createForm.enlace_ubicacion && !/^https?:\/\/.+/i.test(createForm.enlace_ubicacion.trim())) {
        e.enlace_ubicacion = 'Ingresa una URL válida (https://…).';
    }

    return e;
});

const msg = (campo: string): string => {
    const cliente = erroresForm.value[campo];

    if (intentado.value && cliente) {
        return cliente;
    }

    return (createForm.errors as Record<string, string>)[campo] ?? '';
};

watch(() => showCreate, (open) => {
    if (open) {
        intentado.value = false;
    }
});

const submitCreate = () => {
    intentado.value = true;

    if (Object.keys(erroresForm.value).length > 0) {
        return;
    }

    createForm.post('/eventos', {
        onSuccess: () => {
            showCreate.value = false;
            intentado.value = false;
            createForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Eventos" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Eventos</h1>
                <p class="text-muted-foreground mt-1 text-sm">{{ eventos.length }} eventos</p>
            </div>

            <Dialog v-model:open="showCreate">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        Nuevo evento
                    </Button>
                </DialogTrigger>
                <DialogContent class="w-full max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Nuevo evento</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4" @submit.prevent="submitCreate">
                        <div class="space-y-1">
                            <Label>Nombre <span class="text-destructive">*</span></Label>
                            <Input v-model="createForm.nombre" maxlength="255" required />
                            <InputError :message="msg('nombre')" />
                        </div>
                        <div class="space-y-1">
                            <Label>Lugar</Label>
                            <Input v-model="createForm.lugar" maxlength="255" />
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Fecha inicio</Label>
                                <Input v-model="createForm.fecha_inicio" type="date" />
                                <InputError :message="msg('fecha_inicio')" />
                            </div>
                            <div class="space-y-1">
                                <Label>Fecha fin</Label>
                                <Input v-model="createForm.fecha_fin" type="date" />
                                <InputError :message="msg('fecha_fin')" />
                            </div>
                        </div>
                        <div class="space-y-1">
                            <Label>Tamaño</Label>
                            <Select v-model="createForm.tamano">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="CHICO">Chico</SelectItem>
                                    <SelectItem value="MEDIANO">Mediano</SelectItem>
                                    <SelectItem value="GRANDE">Grande</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="border-t pt-4">
                            <p class="text-muted-foreground mb-3 text-xs font-medium uppercase tracking-wide">Contratación y detalle</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <Label>Nombre del contratante</Label>
                                    <Input v-model="createForm.nombre_contratante" maxlength="255" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Teléfono del contratante</Label>
                                    <Input v-model="createForm.telefono_contratante" type="tel" inputmode="tel" maxlength="13" placeholder="10 dígitos" />
                                    <InputError :message="msg('telefono_contratante')" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Contacto del evento</Label>
                                    <Input v-model="createForm.contacto_nombre" maxlength="255" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Teléfono del contacto</Label>
                                    <Input v-model="createForm.contacto_telefono" type="tel" inputmode="tel" maxlength="13" placeholder="10 dígitos" />
                                    <InputError :message="msg('contacto_telefono')" />
                                </div>
                                <div class="space-y-1 sm:col-span-2">
                                    <Label>Enlace de la ubicación</Label>
                                    <Input v-model="createForm.enlace_ubicacion" type="url" placeholder="https://maps.app.goo.gl/..." maxlength="1000" />
                                    <InputError :message="msg('enlace_ubicacion')" />
                                </div>
                                <div class="space-y-1 sm:col-span-2">
                                    <Label>Descripción del evento</Label>
                                    <textarea
                                        v-model="createForm.descripcion"
                                        rows="3"
                                        maxlength="5000"
                                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                                    />
                                    <InputError :message="msg('descripcion')" />
                                </div>
                                <div class="space-y-1 sm:col-span-2">
                                    <Label>Observaciones técnicas</Label>
                                    <textarea
                                        v-model="createForm.observaciones_tecnicas"
                                        rows="2"
                                        maxlength="5000"
                                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                                    />
                                    <InputError :message="msg('observaciones_tecnicas')" />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showCreate = false">Cancelar</Button>
                            <Button type="submit" :disabled="createForm.processing" class="gap-1.5">
                                <Spinner v-if="createForm.processing" class="size-4" />
                                {{ createForm.processing ? 'Creando…' : 'Crear' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Tabla escritorio (≥ lg) -->
        <div class="hidden rounded-xl border overflow-x-auto lg:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium">Lugar</th>
                        <th class="px-4 py-3 text-left font-medium">Contratante</th>
                        <th class="px-4 py-3 text-left font-medium">Fechas</th>
                        <th class="px-4 py-3 text-left font-medium">Tamaño</th>
                        <th class="px-4 py-3 text-left font-medium">Pago</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="e in eventos" :key="e.id" class="transition-colors hover:bg-muted/50">
                        <td class="px-4 py-3 font-medium">{{ e.nombre }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ e.lugar || '—' }}</td>
                        <td class="px-4 py-3">
                            <template v-if="e.nombre_contratante || e.telefono_contratante">
                                <p class="font-medium">{{ e.nombre_contratante || '—' }}</p>
                                <p v-if="e.telefono_contratante" class="text-muted-foreground text-xs">{{ e.telefono_contratante }}</p>
                            </template>
                            <template v-else>—</template>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">
                            <template v-if="e.fecha_inicio || e.fecha_fin">
                                {{ fmtFecha(e.fecha_inicio) }} – {{ fmtFecha(e.fecha_fin) }}
                            </template>
                            <template v-else>—</template>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                :class="tamanoBadge[e.tamano].class"
                            >
                                {{ tamanoBadge[e.tamano].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Input
                                    v-model="pagos[e.id]"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    class="h-7 w-28 text-xs"
                                    :disabled="guardandoPago === e.id"
                                    @blur="guardarPago(e)"
                                    @keyup.enter="guardarPago(e)"
                                />
                                <Spinner v-if="guardandoPago === e.id" class="size-3.5 text-muted-foreground" />
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/eventos/${e.id}/asignacion`">
                                        <Eye class="size-3.5" />
                                        Ver
                                    </Link>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive"
                                    :disabled="eliminando === e.id"
                                    @click="eliminar(e)"
                                >
                                    <Spinner v-if="eliminando === e.id" class="size-3.5" />
                                    <Trash2 v-else class="size-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="eventos.length === 0">
                        <td colspan="7" class="text-muted-foreground px-4 py-8 text-center text-sm">
                            Sin eventos registrados
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cards móvil (< lg) -->
        <div class="flex flex-col gap-3 lg:hidden">
            <div v-if="eventos.length === 0" class="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                Sin eventos registrados
            </div>

            <div v-for="e in eventos" :key="e.id" class="rounded-xl border p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ e.nombre }}</p>
                        <p class="text-muted-foreground mt-0.5 text-xs">{{ e.lugar || 'Sin lugar' }}</p>
                    </div>
                    <span
                        class="flex-shrink-0 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium"
                        :class="tamanoBadge[e.tamano].class"
                    >
                        {{ tamanoBadge[e.tamano].label }}
                    </span>
                </div>

                <dl class="mt-3 space-y-1.5 text-sm">
                    <div v-if="e.nombre_contratante || e.telefono_contratante" class="flex items-start justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Contratante</dt>
                        <dd class="text-right">
                            {{ e.nombre_contratante || '—' }}
                            <span v-if="e.telefono_contratante" class="text-muted-foreground block text-xs">{{ e.telefono_contratante }}</span>
                        </dd>
                    </div>
                    <div v-if="e.fecha_inicio || e.fecha_fin" class="flex items-start justify-between gap-3">
                        <dt class="text-muted-foreground text-xs">Fechas</dt>
                        <dd class="text-right tabular-nums">{{ fmtFecha(e.fecha_inicio) }} – {{ fmtFecha(e.fecha_fin) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t pt-2">
                        <dt class="text-muted-foreground text-xs">Pago por evento</dt>
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="pagos[e.id]"
                                type="number"
                                step="0.01"
                                min="0"
                                inputmode="decimal"
                                class="h-7 w-24 text-xs"
                                :disabled="guardandoPago === e.id"
                                @blur="guardarPago(e)"
                                @keyup.enter="guardarPago(e)"
                            />
                            <Spinner v-if="guardandoPago === e.id" class="size-3.5 text-muted-foreground" />
                        </div>
                    </div>
                </dl>

                <div class="mt-3 flex gap-2">
                    <Button size="sm" variant="outline" class="gap-1.5" as-child>
                        <Link :href="`/eventos/${e.id}/asignacion`">
                            <Eye class="size-3.5" />
                            Ver asignación
                        </Link>
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        class="ml-auto text-destructive"
                        :disabled="eliminando === e.id"
                        @click="eliminar(e)"
                    >
                        <Spinner v-if="eliminando === e.id" class="size-3.5" />
                        <Trash2 v-else class="size-3.5" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>