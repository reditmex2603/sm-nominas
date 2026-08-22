<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Printer, Settings2, Trash2, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
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
import { useConfirm } from '@/composables/useConfirm';
import * as unidadPerfil from '@/routes/transportes/unidades/perfil';

interface Vehiculo { id: number; nombre: string; orden: number }
interface Distancia { id: number; nombre: string; es_standby: boolean; orden: number }

type Pertenencia = 'PROPIA' | 'RENTADA';

interface Unidad {
    id: number;
    marca: string;
    modelo: string;
    numero_placas: string | null;
    pertenencia: Pertenencia;
    transporte_vehiculo_id: number | null;
    vehiculo: { id: number; nombre: string } | null;
}

const props = defineProps<{
    vehiculos: Vehiculo[];
    distancias: Distancia[];
    tarifas: Record<number, Record<number, string>>;
    unidades: Unidad[];
}>();

const activeTab = ref<'tarifas' | 'unidades'>('tarifas');

const modoEdicion = ref(false);

// ── Edit state ────────────────────────────────────────────────────
interface EditVehiculo { id: number | null; nombre: string }
interface EditDistancia { id: number | null; nombre: string; es_standby: boolean }

const editVehiculos = ref<EditVehiculo[]>([]);
const editDistancias = ref<EditDistancia[]>([]);
// matrix[vi][di] = tarifa (as number)
const editMatrix = ref<number[][]>([]);

const entrarEdicion = () => {
    editVehiculos.value = props.vehiculos.map(v => ({ id: v.id, nombre: v.nombre }));
    editDistancias.value = props.distancias.map(d => ({ id: d.id, nombre: d.nombre, es_standby: d.es_standby }));
    editMatrix.value = props.vehiculos.map((v) =>
        props.distancias.map((d) => parseFloat(props.tarifas[v.id]?.[d.id] ?? '0')),
    );
    modoEdicion.value = true;
};

const cancelar = () => {
 modoEdicion.value = false; 
};

const agregarVehiculo = () => {
    editVehiculos.value.push({ id: null, nombre: '' });
    editMatrix.value.push(editDistancias.value.map(() => 0));
};

const agregarDistancia = () => {
    editDistancias.value.push({ id: null, nombre: '', es_standby: false });
    editMatrix.value.forEach(row => row.push(0));
};

const guardar = () => {
    const payload = {
        vehiculos: editVehiculos.value.map(v => ({ nombre: v.nombre })),
        distancias: editDistancias.value.map(d => ({ nombre: d.nombre, es_standby: d.es_standby })),
        tarifas: Object.fromEntries(
            editVehiculos.value.map((v, vi) => [
                v.nombre,
                Object.fromEntries(
                    editDistancias.value.map((d, di) => [d.nombre, editMatrix.value[vi][di] ?? 0]),
                ),
            ]),
        ),
    };

    router.post('/transportes/guardar', payload, {
        preserveScroll: true,
        onSuccess: () => {
            modoEdicion.value = false;
            toast.success('Tarifas actualizadas correctamente.');
        },
        onError: (errors) => {
            toast.error(Object.values(errors).join(' '));
        },
    });
};

// ── Gestionar modal ───────────────────────────────────────────────
const showGestionar = ref(false);
const selDistancia = ref('');
const selVehiculo = ref('');
const errMsg = ref('');

const distanciasOptions = computed(() => props.distancias);
const vehiculosOptions = computed(() => props.vehiculos);

const eliminarDistancia = () => {
    if (!selDistancia.value) {
return;
}

    router.delete(`/transportes/distancia/${selDistancia.value}`, {
        preserveScroll: true,
        onSuccess: () => {
 selDistancia.value = ''; 
},
        onError: (errors) => {
 errMsg.value = Object.values(errors).join(' '); 
},
    });
};

const eliminarVehiculo = () => {
    if (!selVehiculo.value) {
return;
}

    router.delete(`/transportes/vehiculo/${selVehiculo.value}`, {
        preserveScroll: true,
        onSuccess: () => {
 selVehiculo.value = ''; 
},
        onError: (errors) => {
 errMsg.value = Object.values(errors).join(' '); 
},
    });
};

const formatPeso = (val: string | number) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(Number(val));

// ─── Unidades de transporte (flotilla: marca/modelo/placas/documentos) ─────
const pertenenciaBadge: Record<Pertenencia, { label: string; class: string }> = {
    PROPIA: { label: 'Propia', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' },
    RENTADA: { label: 'Rentada', class: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' },
};

const showCreateUnidad = ref(false);
const createUnidadForm = useForm({
    marca: '',
    modelo: '',
    numero_placas: '',
    pertenencia: 'PROPIA' as Pertenencia,
    transporte_vehiculo_id: '' as string,
});

const submitCreateUnidad = () => {
    createUnidadForm.post('/transportes/unidades', {
        onSuccess: () => {
            showCreateUnidad.value = false;
            createUnidadForm.reset();
        },
    });
};

interface EditUnidad {
    marca: string;
    modelo: string;
    numero_placas: string;
    pertenencia: Pertenencia;
    transporte_vehiculo_id: string;
}

const editUnidades = ref<Record<number, EditUnidad>>({});

// Sincroniza editUnidades con props.unidades — no solo al montar, sino cada vez que la lista
// cambia (ej. al crear una unidad nueva, Inertia recarga las props sin desmontar el componente,
// así que una inicialización única dejaría la fila nueva sin entrada y rompería el v-model).
watch(() => props.unidades, (lista) => {
    for (const u of lista) {
        if (!(u.id in editUnidades.value)) {
            editUnidades.value[u.id] = {
                marca: u.marca,
                modelo: u.modelo,
                numero_placas: u.numero_placas ?? '',
                pertenencia: u.pertenencia,
                transporte_vehiculo_id: u.transporte_vehiculo_id ? String(u.transporte_vehiculo_id) : '',
            };
        }
    }
}, { immediate: true, deep: true });

const guardarUnidad = (u: Unidad) => {
    router.put(`/transportes/unidades/${u.id}`, { ...editUnidades.value[u.id] }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Unidad actualizada.'),
        onError: (errors) => toast.error(Object.values(errors).join(' ')),
    });
};

const { confirm } = useConfirm();

const eliminarUnidad = async (u: Unidad) => {
    const ok = await confirm(`¿Eliminar la unidad "${u.marca} ${u.modelo}"? Esta acción no se puede deshacer.`, {
        title: 'Eliminar unidad',
    });

    if (ok) {
router.delete(`/transportes/unidades/${u.id}`, { preserveScroll: true });
}
};

const abrirPerfilUnidad = (u: Unidad) => {
    window.open(unidadPerfil.imprimir.url({ unidad: u.id }), '_blank', 'noopener');
};
</script>

<template>
    <Head title="Transportes" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Transportes</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Tarifas por vehículo y distancia
                </p>
            </div>

            <div v-if="activeTab === 'tarifas'" class="flex gap-2">
                <!-- Gestionar modal -->
                <Dialog v-model:open="showGestionar" @update:open="errMsg = ''">
                    <DialogTrigger as-child>
                        <Button variant="outline" :disabled="modoEdicion">
                            <Settings2 class="size-4" />
                            Gestionar
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-sm">
                        <DialogHeader>
                            <DialogTitle>Gestionar tabla</DialogTitle>
                        </DialogHeader>

                        <p v-if="errMsg" class="text-destructive text-sm">{{ errMsg }}</p>

                        <div class="grid gap-4">
                            <div class="space-y-2">
                                <Label>Eliminar distancia (columna)</Label>
                                <div class="flex gap-2">
                                    <Select v-model="selDistancia">
                                        <SelectTrigger class="flex-1">
                                            <SelectValue placeholder="Seleccionar..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="d in distanciasOptions"
                                                :key="d.id"
                                                :value="String(d.id)"
                                            >
                                                {{ d.nombre }}
                                                <span v-if="d.es_standby" class="text-muted-foreground ml-1 text-xs">(STANDBY)</span>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        variant="destructive"
                                        :disabled="!selDistancia"
                                        @click="eliminarDistancia"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label>Eliminar vehículo (fila)</Label>
                                <div class="flex gap-2">
                                    <Select v-model="selVehiculo">
                                        <SelectTrigger class="flex-1">
                                            <SelectValue placeholder="Seleccionar..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="v in vehiculosOptions"
                                                :key="v.id"
                                                :value="String(v.id)"
                                            >
                                                {{ v.nombre }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        variant="destructive"
                                        :disabled="!selVehiculo"
                                        @click="eliminarVehiculo"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <Button v-if="!modoEdicion" @click="entrarEdicion">
                    <Pencil class="size-4" />
                    Editar
                </Button>

                <template v-else>
                    <Button variant="outline" @click="cancelar">
                        <X class="size-4" />
                        Cancelar
                    </Button>
                    <Button @click="guardar">Guardar</Button>
                </template>
            </div>

            <Dialog v-else-if="activeTab === 'unidades'" v-model:open="showCreateUnidad">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        Nueva unidad
                    </Button>
                </DialogTrigger>
                <DialogContent class="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Nueva unidad de transporte</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4" @submit.prevent="submitCreateUnidad">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label>Marca</Label>
                                <Input v-model="createUnidadForm.marca" required />
                                <p v-if="createUnidadForm.errors.marca" class="text-destructive text-xs">{{ createUnidadForm.errors.marca }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Modelo</Label>
                                <Input v-model="createUnidadForm.modelo" required />
                                <p v-if="createUnidadForm.errors.modelo" class="text-destructive text-xs">{{ createUnidadForm.errors.modelo }}</p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label>Número de placas</Label>
                            <Input v-model="createUnidadForm.numero_placas" placeholder="Opcional" />
                        </div>

                        <div class="space-y-1">
                            <Label>Pertenencia</Label>
                            <Select v-model="createUnidadForm.pertenencia">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="PROPIA">Propia</SelectItem>
                                    <SelectItem value="RENTADA">Rentada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label>Categoría (tarifa)</Label>
                            <Select v-model="createUnidadForm.transporte_vehiculo_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Sin asignar" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="v in vehiculos" :key="v.id" :value="String(v.id)">{{ v.nombre }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showCreateUnidad = false">Cancelar</Button>
                            <Button type="submit" :disabled="createUnidadForm.processing">Crear</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Tabs nav -->
        <div class="flex gap-1 border-b overflow-x-auto">
            <button
                class="relative px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === 'tarifas'
                    ? 'text-foreground border-b-2 border-primary -mb-px bg-transparent'
                    : 'text-muted-foreground hover:text-foreground'"
                @click="activeTab = 'tarifas'"
            >
                Tarifas
            </button>
            <button
                class="relative px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === 'unidades'
                    ? 'text-foreground border-b-2 border-primary -mb-px bg-transparent'
                    : 'text-muted-foreground hover:text-foreground'"
                @click="activeTab = 'unidades'"
            >
                Unidades
            </button>
        </div>

        <template v-if="activeTab === 'tarifas'">
        <!-- Tabla en modo lectura -->
        <div v-if="!modoEdicion" class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Vehículo</th>
                        <th
                            v-for="d in distancias"
                            :key="d.id"
                            class="px-4 py-3 text-center font-medium whitespace-nowrap"
                        >
                            {{ d.nombre }}
                            <span v-if="d.es_standby" class="text-muted-foreground ml-1 text-xs">(STANDBY)</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="v in vehiculos" :key="v.id">
                        <td class="px-4 py-3 font-medium">{{ v.nombre }}</td>
                        <td
                            v-for="d in distancias"
                            :key="d.id"
                            class="px-4 py-3 text-center tabular-nums"
                        >
                            {{ formatPeso(tarifas[v.id]?.[d.id] ?? 0) }}
                        </td>
                    </tr>
                    <tr v-if="vehiculos.length === 0">
                        <td :colspan="distancias.length + 1" class="text-muted-foreground px-4 py-8 text-center text-sm">
                            Sin datos. Agrega vehículos y distancias en modo edición.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Tabla en modo edición -->
        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">
                            <Input
                                value="Vehículo"
                                disabled
                                class="h-7 w-28 bg-transparent text-xs font-medium"
                            />
                        </th>
                        <th v-for="(d, di) in editDistancias" :key="di" class="px-2 py-2">
                            <div class="flex flex-col gap-1 items-center">
                                <Input
                                    v-model="editDistancias[di].nombre"
                                    class="h-7 w-28 text-xs text-center"
                                    placeholder="Distancia"
                                />
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input
                                        v-model="editDistancias[di].es_standby"
                                        type="checkbox"
                                        class="rounded"
                                    />
                                    Standby
                                </label>
                            </div>
                        </th>
                        <th class="px-2 py-2">
                            <Button size="sm" variant="outline" class="text-xs" @click="agregarDistancia">
                                + Distancia
                            </Button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="(v, vi) in editVehiculos" :key="vi">
                        <td class="px-3 py-2">
                            <Input
                                v-model="editVehiculos[vi].nombre"
                                class="h-7 w-28 text-xs"
                                placeholder="Vehículo"
                            />
                        </td>
                        <td v-for="(d, di) in editDistancias" :key="di" class="px-2 py-2 text-center">
                            <Input
                                v-model.number="editMatrix[vi][di]"
                                type="number"
                                step="0.01"
                                min="0"
                                class="h-7 w-24 text-xs text-center tabular-nums"
                            />
                        </td>
                        <td />
                    </tr>
                    <tr>
                        <td class="px-3 py-2">
                            <Button size="sm" variant="outline" class="text-xs" @click="agregarVehiculo">
                                + Vehículo
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        </template>

        <template v-else-if="activeTab === 'unidades'">
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Marca</th>
                        <th class="px-4 py-3 text-left font-medium">Modelo</th>
                        <th class="px-4 py-3 text-left font-medium">Placas</th>
                        <th class="px-4 py-3 text-left font-medium">Pertenencia</th>
                        <th class="px-4 py-3 text-left font-medium">Categoría (tarifa)</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="unidades.length === 0">
                        <td colspan="6" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            Sin unidades registradas.
                        </td>
                    </tr>
                    <tr v-for="u in unidades" :key="u.id">
                        <td class="px-4 py-3">
                            <Input v-model="editUnidades[u.id].marca" class="h-7 w-28 text-xs" />
                        </td>
                        <td class="px-4 py-3">
                            <Input v-model="editUnidades[u.id].modelo" class="h-7 w-28 text-xs" />
                        </td>
                        <td class="px-4 py-3">
                            <Input v-model="editUnidades[u.id].numero_placas" class="h-7 w-24 text-xs" />
                        </td>
                        <td class="px-4 py-3">
                            <Select v-model="editUnidades[u.id].pertenencia">
                                <SelectTrigger class="h-7 w-28 text-xs" :class="pertenenciaBadge[editUnidades[u.id].pertenencia].class">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="PROPIA" class="text-xs">Propia</SelectItem>
                                    <SelectItem value="RENTADA" class="text-xs">Rentada</SelectItem>
                                </SelectContent>
                            </Select>
                        </td>
                        <td class="px-4 py-3">
                            <Select v-model="editUnidades[u.id].transporte_vehiculo_id">
                                <SelectTrigger class="h-7 w-32 text-xs">
                                    <SelectValue placeholder="Sin asignar" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="v in vehiculos" :key="v.id" :value="String(v.id)" class="text-xs">{{ v.nombre }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button size="sm" variant="outline" @click="guardarUnidad(u)">
                                    <Pencil class="size-3.5" />
                                    Guardar
                                </Button>
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/transportes/unidades/${u.id}`">
                                        <Eye class="size-3.5" />
                                        Detalle
                                    </Link>
                                </Button>
                                <Button size="sm" variant="ghost" class="text-muted-foreground hover:text-foreground" title="Imprimir perfil" @click="abrirPerfilUnidad(u)">
                                    <Printer class="size-3.5" />
                                </Button>
                                <Button size="sm" variant="ghost" class="text-destructive" @click="eliminarUnidad(u)">
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        </template>
    </div>
</template>
