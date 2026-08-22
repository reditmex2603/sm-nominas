<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Plus, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
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
    router.put(`/eventos/${evento.id}`, { pago_por_evento_completo: pagos.value[evento.id] }, {
        preserveScroll: true,
    });
};

const { confirm } = useConfirm();

const eliminar = async (evento: Evento) => {
    const ok = await confirm(`¿Eliminar el evento "${evento.nombre}"? Esta acción no se puede deshacer.`, {
        title: 'Eliminar evento',
    });
    if (ok) router.delete(`/eventos/${evento.id}`, { preserveScroll: true });
};

// Create form
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

const submitCreate = () => {
    createForm.post('/eventos', {
        onSuccess: () => {
            showCreate.value = false;
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
                <DialogContent class="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Nuevo evento</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4" @submit.prevent="submitCreate">
                        <div class="space-y-1">
                            <Label>Nombre</Label>
                            <Input v-model="createForm.nombre" required />
                            <p v-if="createForm.errors.nombre" class="text-destructive text-xs">{{ createForm.errors.nombre }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Lugar</Label>
                            <Input v-model="createForm.lugar" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label>Fecha inicio</Label>
                                <Input v-model="createForm.fecha_inicio" type="date" />
                                <p v-if="createForm.errors.fecha_inicio" class="text-destructive text-xs">{{ createForm.errors.fecha_inicio }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Fecha fin</Label>
                                <Input v-model="createForm.fecha_fin" type="date" />
                                <p v-if="createForm.errors.fecha_fin" class="text-destructive text-xs">{{ createForm.errors.fecha_fin }}</p>
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
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <Label>Nombre del contratante</Label>
                                    <Input v-model="createForm.nombre_contratante" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Teléfono del contratante</Label>
                                    <Input v-model="createForm.telefono_contratante" type="tel" maxlength="20" placeholder="10 dígitos" />
                                    <p v-if="createForm.errors.telefono_contratante" class="text-destructive text-xs">{{ createForm.errors.telefono_contratante }}</p>
                                </div>
                                <div class="space-y-1">
                                    <Label>Contacto del evento</Label>
                                    <Input v-model="createForm.contacto_nombre" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Teléfono del contacto</Label>
                                    <Input v-model="createForm.contacto_telefono" type="tel" maxlength="20" placeholder="10 dígitos" />
                                    <p v-if="createForm.errors.contacto_telefono" class="text-destructive text-xs">{{ createForm.errors.contacto_telefono }}</p>
                                </div>
                                <div class="col-span-2 space-y-1">
                                    <Label>Enlace de la ubicación</Label>
                                    <Input v-model="createForm.enlace_ubicacion" type="url" placeholder="https://maps.app.goo.gl/..." />
                                    <p v-if="createForm.errors.enlace_ubicacion" class="text-destructive text-xs">{{ createForm.errors.enlace_ubicacion }}</p>
                                </div>
                                <div class="col-span-2 space-y-1">
                                    <Label>Descripción del evento</Label>
                                    <textarea
                                        v-model="createForm.descripcion"
                                        rows="3"
                                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                                    />
                                    <p v-if="createForm.errors.descripcion" class="text-destructive text-xs">{{ createForm.errors.descripcion }}</p>
                                </div>
                                <div class="col-span-2 space-y-1">
                                    <Label>Observaciones técnicas</Label>
                                    <textarea
                                        v-model="createForm.observaciones_tecnicas"
                                        rows="2"
                                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                                    />
                                    <p v-if="createForm.errors.observaciones_tecnicas" class="text-destructive text-xs">{{ createForm.errors.observaciones_tecnicas }}</p>
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showCreate = false">Cancelar</Button>
                            <Button type="submit" :disabled="createForm.processing">Crear</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div class="rounded-xl border overflow-x-auto">
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
                    <tr v-for="e in eventos" :key="e.id">
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
                                    class="h-7 w-28 text-xs"
                                    @blur="guardarPago(e)"
                                    @keyup.enter="guardarPago(e)"
                                />
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
                                <Button size="sm" variant="ghost" class="text-destructive" @click="eliminar(e)">
                                    <Trash2 class="size-3.5" />
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
    </div>
</template>
