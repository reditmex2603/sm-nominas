<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Check, Copy, IdCard, Pencil, Plus, RefreshCw, Trash2 } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
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

type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

const CATEGORIAS = ['Encargado de área', 'Técnico', 'Stagehand SM'] as const;
const NIVELES = [1, 2] as const;

interface Colaborador {
    id: number;
    nombre: string;
    apellidos: string;
    tipo: TipoColaborador;
    categoria: string | null;
    nivel: number | null;
    compensacion_pct: number;
    sueldo_diario: string | null;
    extra_dia_adicional: string | null;
    token: string | null;
    perfil_pendiente: boolean;
}

const props = defineProps<{ colaboradores: Colaborador[] }>();

const tipoBadge: Record<TipoColaborador, { label: string; class: string }> = {
    'COLABORADOR BASE': { label: 'Base', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' },
    'FREELANCE': { label: 'Freelance', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    'CONDUCTOR': { label: 'Conductor', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    'CONDUCTOR BASE': { label: 'Conductor base', class: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
};

interface EditRow {
    categoria: string;
    nivel: number | '';
    compensacion_pct: number;
    sueldo_diario: string;
    extra_dia_adicional: string;
}

const editState = ref<Record<number, EditRow>>({});

// Sincroniza editState con props.colaboradores — no solo al montar, sino cada vez que la lista
// cambia (ej. al crear un colaborador nuevo, Inertia recarga las props sin desmontar el
// componente, así que una inicialización única dejaría la fila nueva sin entrada y rompería
// el v-model de esa fila).
watch(() => props.colaboradores, (lista) => {
    for (const c of lista) {
        if (!(c.id in editState.value)) {
            editState.value[c.id] = {
                categoria: c.categoria ?? '',
                nivel: c.nivel ?? '',
                compensacion_pct: c.compensacion_pct ?? 0,
                sueldo_diario: c.sueldo_diario ?? '',
                extra_dia_adicional: c.extra_dia_adicional ?? '',
            };
        }
    }
}, { immediate: true, deep: true });

// Base exige categoría + nivel para poder guardar cualquier cambio de la fila.
const faltaCategoriaONivel = (c: Colaborador): boolean =>
    c.tipo === 'COLABORADOR BASE' && (!editState.value[c.id].categoria || !editState.value[c.id].nivel);

// ── Operaciones por fila (feedback de carga) ───────────────────────
const busy = ref<{ id: number; op: 'save' | 'delete' | 'token' } | null>(null);

const filaOcupada = (id: number, op: 'save' | 'delete' | 'token'): boolean =>
    busy.value?.id === id && busy.value?.op === op;

const guardar = (colaborador: Colaborador) => {
    busy.value = { id: colaborador.id, op: 'save' };
    router.put(`/colaboradores/${colaborador.id}`, { ...editState.value[colaborador.id] }, {
        preserveScroll: true,
        onFinish: () => {
 busy.value = null; 
},
    });
};

const { confirm } = useConfirm();

const eliminar = async (colaborador: Colaborador) => {
    const ok = await confirm(`¿Eliminar a ${colaborador.nombre} ${colaborador.apellidos}? Esta acción no se puede deshacer.`, {
        title: 'Eliminar colaborador',
    });

    if (!ok) {
return;
}

    busy.value = { id: colaborador.id, op: 'delete' };
    router.delete(`/colaboradores/${colaborador.id}`, {
        preserveScroll: true,
        onFinish: () => {
 busy.value = null; 
},
    });
};

// ── Formulario de alta ─────────────────────────────────────────────
const showAdd = ref(false);
const addForm = useForm({
    nombre: '',
    apellidos: '',
    tipo: 'COLABORADOR BASE' as TipoColaborador,
    categoria: '',
    nivel: '' as number | '',
    sueldo_diario: '' as string,
    extra_dia_adicional: '' as string,
});

const addErrors = reactive({
    nombre: '',
    apellidos: '',
    sueldo_diario: '',
    categoria: '',
    nivel: '',
});

const validarCampoAlta = (campo: keyof typeof addErrors) => {
    switch (campo) {
        case 'nombre':
            addErrors.nombre = !addForm.nombre.trim() ? 'El nombre es obligatorio.'
                : addForm.nombre.trim().length > 255 ? 'Máximo 255 caracteres.' : '';
            break;
        case 'apellidos':
            addErrors.apellidos = !addForm.apellidos.trim() ? 'Los apellidos son obligatorios.'
                : addForm.apellidos.trim().length > 255 ? 'Máximo 255 caracteres.' : '';
            break;
        case 'sueldo_diario':
            addErrors.sueldo_diario = addForm.tipo === 'CONDUCTOR BASE' && !Number(addForm.sueldo_diario)
                ? 'El sueldo diario es obligatorio para conductores base.' : '';
            break;
        case 'categoria':
            addErrors.categoria = addForm.tipo === 'COLABORADOR BASE' && !addForm.categoria ? 'Selecciona una categoría.' : '';
            break;
        case 'nivel':
            addErrors.nivel = addForm.tipo === 'COLABORADOR BASE' && !addForm.nivel ? 'Selecciona un nivel.' : '';
            break;
    }
};

const validarAlta = (): boolean => {
    (Object.keys(addErrors) as Array<keyof typeof addErrors>).forEach(validarCampoAlta);

    return Object.values(addErrors).every((v) => !v);
};

watch(() => addForm.nombre, () => {
 addErrors.nombre = ''; 
});
watch(() => addForm.apellidos, () => {
 addErrors.apellidos = ''; 
});
watch(() => addForm.sueldo_diario, () => {
 addErrors.sueldo_diario = ''; 
});
watch(() => addForm.categoria, () => {
 addErrors.categoria = ''; 
});
watch(() => addForm.nivel, () => {
 addErrors.nivel = ''; 
});
watch(() => showAdd, (open) => {
    if (open) {
        Object.keys(addErrors).forEach((k) => {
 addErrors[k as keyof typeof addErrors] = ''; 
});
    }
});

// Base exige categoría + nivel para poder crear el colaborador.
const addFormInvalido = computed(() =>
    addForm.tipo === 'COLABORADOR BASE' && (!addForm.categoria || !addForm.nivel)
    || (addForm.tipo === 'CONDUCTOR BASE' && !Number(addForm.sueldo_diario)),
);

const submitAdd = () => {
    if (!validarAlta()) {
        return;
    }

    addForm.post('/colaboradores', {
        onSuccess: () => {
            showAdd.value = false;
            addForm.reset();
        },
    });
};

// ─── Link de asistencia ────────────────────────────────────────────
const linkCopiado = ref<number | null>(null);

const copiarLink = (c: Colaborador) => {
    if (!c.token) {
return;
}

    const url = `${window.location.origin}/asistencia/${c.token}`;
    navigator.clipboard.writeText(url).then(() => {
        linkCopiado.value = c.id;
        toast.success('Enlace de asistencia copiado al portapapeles.');
        setTimeout(() => {
 if (linkCopiado.value === c.id) {
linkCopiado.value = null;
} 
}, 2000);
    });
};

const regenerarToken = async (c: Colaborador) => {
    const ok = await confirm(`¿Regenerar el enlace de ${c.nombre} ${c.apellidos}? El enlace anterior dejará de funcionar.`, {
        title: 'Regenerar enlace',
        confirmLabel: 'Regenerar',
    });

    if (!ok) {
return;
}

    busy.value = { id: c.id, op: 'token' };
    router.post(`/colaboradores/${c.id}/token/regenerar`, {}, {
        preserveScroll: true,
        onFinish: () => {
 busy.value = null; 
},
    });
};
</script>

<template>
    <Head title="Colaboradores" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Colaboradores</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ colaboradores.length }} registros
                </p>
            </div>

            <Dialog v-model:open="showAdd">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        Nuevo colaborador
                    </Button>
                </DialogTrigger>
                <DialogContent class="w-full max-w-md sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nuevo colaborador</DialogTitle>
                    </DialogHeader>

                    <form class="grid gap-4" @submit.prevent="submitAdd">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Nombre <span class="text-destructive">*</span></Label>
                                <Input v-model="addForm.nombre" maxlength="255" required @blur="validarCampoAlta('nombre')" />
                                <InputError :message="addErrors.nombre || addForm.errors.nombre" />
                            </div>
                            <div class="space-y-1">
                                <Label>Apellidos <span class="text-destructive">*</span></Label>
                                <Input v-model="addForm.apellidos" maxlength="255" required @blur="validarCampoAlta('apellidos')" />
                                <InputError :message="addErrors.apellidos || addForm.errors.apellidos" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label>Tipo</Label>
                            <Select v-model="addForm.tipo">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="COLABORADOR BASE">Colaborador Base</SelectItem>
                                    <SelectItem value="FREELANCE">Freelance</SelectItem>
                                    <SelectItem value="CONDUCTOR">Conductor</SelectItem>
                                    <SelectItem value="CONDUCTOR BASE">Conductor base</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <template v-if="addForm.tipo === 'COLABORADOR BASE' || addForm.tipo === 'CONDUCTOR BASE'">
                            <div class="space-y-1">
                                <Label>Sueldo diario <span v-if="addForm.tipo === 'CONDUCTOR BASE'" class="text-destructive">*</span></Label>
                                <Input
                                    v-model.number="addForm.sueldo_diario"
                                    type="number" step="0.01" min="0"
                                    @blur="validarCampoAlta('sueldo_diario')"
                                />
                                <InputError :message="addErrors.sueldo_diario || addForm.errors.sueldo_diario" />
                            </div>
                        </template>

                        <div v-if="addForm.tipo === 'COLABORADOR BASE'" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Categoría <span class="text-destructive">*</span></Label>
                                <Select v-model="addForm.categoria">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="cat in CATEGORIAS" :key="cat" :value="cat">{{ cat }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="addErrors.categoria || addForm.errors.categoria" />
                            </div>
                            <div class="space-y-1">
                                <Label>Nivel <span class="text-destructive">*</span></Label>
                                <Select :model-value="addForm.nivel ? String(addForm.nivel) : ''" @update:model-value="(v) => addForm.nivel = Number(v)">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="n in NIVELES" :key="n" :value="String(n)">Nivel {{ n }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="addErrors.nivel || addForm.errors.nivel" />
                            </div>
                        </div>

                        <div v-if="addForm.tipo === 'FREELANCE'" class="space-y-1">
                            <Label>Extra día adicional</Label>
                            <Input v-model.number="addForm.extra_dia_adicional" type="number" step="0.01" min="0" />
                            <InputError :message="addForm.errors.extra_dia_adicional" />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showAdd = false">Cancelar</Button>
                            <Button type="submit" :disabled="addForm.processing || addFormInvalido" class="gap-1.5">
                                <Spinner v-if="addForm.processing" class="size-4" />
                                {{ addForm.processing ? 'Guardando…' : 'Guardar' }}
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
                        <th class="px-4 py-3 text-left font-medium w-16">ID</th>
                        <th class="px-4 py-3 text-left font-medium">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">Categoría</th>
                        <th class="px-4 py-3 text-left font-medium">Nivel</th>
                        <th class="px-4 py-3 text-left font-medium">Sueldo/día</th>
                        <th class="px-4 py-3 text-left font-medium">Extra día</th>
                        <th class="px-4 py-3 text-left font-medium">Compensación</th>
                        <th class="px-4 py-3 text-left font-medium">Enlace asistencia</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="c in colaboradores"
                        :key="c.id"
                        :class="(c.tipo === 'CONDUCTOR' || c.tipo === 'CONDUCTOR BASE') ? 'opacity-60' : ''"
                    >
                        <td class="px-4 py-3 text-muted-foreground text-xs w-16">
                            {{ c.id }}
                        </td>
                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                            {{ c.apellidos }}, {{ c.nombre }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                :class="tipoBadge[c.tipo].class"
                            >
                                {{ tipoBadge[c.tipo].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <Select v-if="c.tipo === 'COLABORADOR BASE'" v-model="editState[c.id].categoria">
                                <SelectTrigger class="h-7 w-36 text-xs" :class="!editState[c.id].categoria ? 'border-destructive' : ''">
                                    <SelectValue placeholder="Sin asignar" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="cat in CATEGORIAS" :key="cat" :value="cat" class="text-xs">{{ cat }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <Select
                                v-if="c.tipo === 'COLABORADOR BASE'"
                                :model-value="editState[c.id].nivel ? String(editState[c.id].nivel) : ''"
                                @update:model-value="(v) => editState[c.id].nivel = Number(v)"
                            >
                                <SelectTrigger class="h-7 w-24 text-xs" :class="!editState[c.id].nivel ? 'border-destructive' : ''">
                                    <SelectValue placeholder="Sin asignar" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="n in NIVELES" :key="n" :value="String(n)" class="text-xs">Nivel {{ n }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <Input
                                v-if="c.tipo === 'COLABORADOR BASE' || c.tipo === 'CONDUCTOR BASE'"
                                v-model.number="editState[c.id].sueldo_diario"
                                type="number" step="0.01" min="0"
                                class="h-7 w-24 text-xs"
                                :class="c.tipo === 'CONDUCTOR BASE' && !editState[c.id].sueldo_diario ? 'border-destructive' : ''"
                            />
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <Input
                                v-if="c.tipo === 'FREELANCE'"
                                v-model.number="editState[c.id].extra_dia_adicional"
                                type="number" step="0.01" min="0"
                                class="h-7 w-24 text-xs"
                            />
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div v-if="c.tipo === 'COLABORADOR BASE'" class="flex items-center gap-1">
                                <Input
                                    v-model.number="editState[c.id].compensacion_pct"
                                    type="number" step="1" min="0" max="100"
                                    class="h-7 w-16 text-xs"
                                />
                                <span class="text-muted-foreground text-xs">%</span>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <!-- Enlace de asistencia -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <Button
                                    v-if="c.token"
                                    size="sm"
                                    variant="ghost"
                                    class="h-7 px-2 text-xs"
                                    :class="linkCopiado === c.id ? 'text-emerald-600' : 'text-blue-600'"
                                    :title="`/asistencia/${c.token}`"
                                    @click="copiarLink(c)"
                                >
                                    <Check v-if="linkCopiado === c.id" class="size-3.5 mr-1" />
                                    <Copy v-else class="size-3.5 mr-1" />
                                    {{ linkCopiado === c.id ? 'Copiado' : 'Copiar link' }}
                                </Button>
                                <Button
                                    v-if="c.token"
                                    size="sm"
                                    variant="ghost"
                                    class="h-7 px-2 text-muted-foreground"
                                    title="Regenerar enlace"
                                    :disabled="filaOcupada(c.id, 'token')"
                                    @click="regenerarToken(c)"
                                >
                                    <Spinner v-if="filaOcupada(c.id, 'token')" class="size-3.5" />
                                    <RefreshCw v-else class="size-3.5" />
                                </Button>
                                <span v-else class="text-muted-foreground text-xs">—</span>
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    as-child
                                    :title="c.perfil_pendiente ? 'Perfil pendiente por completar' : 'Perfil completo'"
                                >
                                    <Link :href="`/colaboradores/${c.id}/perfil`">
                                        <span
                                            class="size-2 rounded-full"
                                            :class="c.perfil_pendiente ? 'bg-amber-500' : 'bg-emerald-500'"
                                        />
                                        <IdCard class="size-3.5" />
                                        Perfil
                                    </Link>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="gap-1.5"
                                    :disabled="c.tipo === 'CONDUCTOR' || c.tipo === 'CONDUCTOR BASE' || faltaCategoriaONivel(c) || filaOcupada(c.id, 'save')"
                                    :title="faltaCategoriaONivel(c) ? 'Asigna categoría y nivel para poder guardar' : undefined"
                                    @click="guardar(c)"
                                >
                                    <Spinner v-if="filaOcupada(c.id, 'save')" class="size-3.5" />
                                    <Pencil v-else class="size-3.5" />
                                    Guardar
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive"
                                    :disabled="filaOcupada(c.id, 'delete')"
                                    @click="eliminar(c)"
                                >
                                    <Spinner v-if="filaOcupada(c.id, 'delete')" class="size-3.5" />
                                    <Trash2 v-else class="size-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="colaboradores.length === 0" class="text-muted-foreground px-4 py-10 text-center text-sm">
                Sin colaboradores registrados.
            </div>
        </div>

        <!-- Cards móvil (< lg) -->
        <div class="flex flex-col gap-3 lg:hidden">
            <div
                v-for="c in colaboradores"
                :key="c.id"
                class="rounded-xl border p-4"
                :class="(c.tipo === 'CONDUCTOR' || c.tipo === 'CONDUCTOR BASE') ? 'opacity-70' : ''"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ c.apellidos }}, {{ c.nombre }}</p>
                        <p class="text-muted-foreground mt-0.5 text-xs">
                            ID {{ c.id }}
                            <span
                                class="ml-1 inline-flex items-center rounded-full border px-1.5 py-0.5 text-[10px] font-medium"
                                :class="tipoBadge[c.tipo].class"
                            >
                                {{ tipoBadge[c.tipo].label }}
                            </span>
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-1">
                        <span
                            class="size-2 rounded-full"
                            :class="c.perfil_pendiente ? 'bg-amber-500' : 'bg-emerald-500'"
                            :title="c.perfil_pendiente ? 'Perfil pendiente por completar' : 'Perfil completo'"
                        />
                        <Button
                            v-if="c.token"
                            size="sm"
                            variant="ghost"
                            class="h-7 px-2 text-xs"
                            :class="linkCopiado === c.id ? 'text-emerald-600' : 'text-blue-600'"
                            @click="copiarLink(c)"
                        >
                            <Check v-if="linkCopiado === c.id" class="size-3.5 mr-1" />
                            <Copy v-else class="size-3.5 mr-1" />
                            {{ linkCopiado === c.id ? 'Copiado' : 'Copiar' }}
                        </Button>
                        <Button
                            v-if="c.token"
                            size="sm"
                            variant="ghost"
                            class="h-7 px-2 text-muted-foreground"
                            :disabled="filaOcupada(c.id, 'token')"
                            @click="regenerarToken(c)"
                        >
                            <Spinner v-if="filaOcupada(c.id, 'token')" class="size-3.5" />
                            <RefreshCw v-else class="size-3.5" />
                        </Button>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div v-if="c.tipo === 'COLABORADOR BASE'" class="space-y-1">
                        <Label class="text-muted-foreground text-xs">Categoría</Label>
                        <Select v-model="editState[c.id].categoria">
                            <SelectTrigger class="w-full" :class="!editState[c.id].categoria ? 'border-destructive' : ''">
                                <SelectValue placeholder="Sin asignar" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="cat in CATEGORIAS" :key="cat" :value="cat">{{ cat }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div v-if="c.tipo === 'COLABORADOR BASE'" class="space-y-1">
                        <Label class="text-muted-foreground text-xs">Nivel</Label>
                        <Select
                            :model-value="editState[c.id].nivel ? String(editState[c.id].nivel) : ''"
                            @update:model-value="(v) => editState[c.id].nivel = Number(v)"
                        >
                            <SelectTrigger class="w-full" :class="!editState[c.id].nivel ? 'border-destructive' : ''">
                                <SelectValue placeholder="Sin asignar" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="n in NIVELES" :key="n" :value="String(n)">Nivel {{ n }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div v-if="c.tipo === 'COLABORADOR BASE' || c.tipo === 'CONDUCTOR BASE'" class="space-y-1">
                        <Label class="text-muted-foreground text-xs">Sueldo diario</Label>
                        <Input
                            v-model.number="editState[c.id].sueldo_diario"
                            type="number" step="0.01" min="0"
                            :class="c.tipo === 'CONDUCTOR BASE' && !editState[c.id].sueldo_diario ? 'border-destructive' : ''"
                        />
                    </div>
                    <div v-if="c.tipo === 'FREELANCE'" class="space-y-1">
                        <Label class="text-muted-foreground text-xs">Extra día adicional</Label>
                        <Input v-model.number="editState[c.id].extra_dia_adicional" type="number" step="0.01" min="0" />
                    </div>
                    <div v-if="c.tipo === 'COLABORADOR BASE'" class="space-y-1">
                        <Label class="text-muted-foreground text-xs">Compensación</Label>
                        <div class="flex items-center gap-1">
                            <Input v-model.number="editState[c.id].compensacion_pct" type="number" step="1" min="0" max="100" />
                            <span class="text-muted-foreground text-xs">%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <Button size="sm" variant="outline" class="gap-1.5" as-child>
                        <Link :href="`/colaboradores/${c.id}/perfil`">
                            <IdCard class="size-3.5" />
                            Perfil
                        </Link>
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        class="gap-1.5"
                        :disabled="c.tipo === 'CONDUCTOR' || c.tipo === 'CONDUCTOR BASE' || faltaCategoriaONivel(c) || filaOcupada(c.id, 'save')"
                        :title="faltaCategoriaONivel(c) ? 'Asigna categoría y nivel para poder guardar' : undefined"
                        @click="guardar(c)"
                    >
                        <Spinner v-if="filaOcupada(c.id, 'save')" class="size-3.5" />
                        <Pencil v-else class="size-3.5" />
                        Guardar
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        class="ml-auto text-destructive"
                        :disabled="filaOcupada(c.id, 'delete')"
                        @click="eliminar(c)"
                    >
                        <Spinner v-if="filaOcupada(c.id, 'delete')" class="size-3.5" />
                        <Trash2 v-else class="size-3.5" />
                    </Button>
                </div>
            </div>

            <div v-if="colaboradores.length === 0" class="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                Sin colaboradores registrados.
            </div>
        </div>
    </div>
</template>