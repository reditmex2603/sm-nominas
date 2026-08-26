<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, ShieldCheck, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import { useConfirm } from '@/composables/useConfirm';

type Rol = 'supervisor' | 'capturista';

interface Usuario {
    id: number;
    name: string;
    email: string;
    rol: string;
    permisos: string[];
}

const props = defineProps<{
    usuarios: Usuario[];
    modulos: Record<string, string>;
}>();

const modulosList = computed(() => Object.entries(props.modulos));

const rolLabel: Record<string, string> = {
    admin: 'Super admin',
    supervisor: 'Supervisor',
    capturista: 'Capturista',
};

const showDialog = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    rol: 'capturista' as Rol,
    permisos: [] as string[],
});

const { confirm } = useConfirm();

const dialogTitle = computed(() => (editingId.value === null ? 'Nuevo usuario' : 'Editar usuario'));

const abrirNuevo = () => {
    editingId.value = null;
    form.clearErrors();
    form.reset('name', 'email', 'password');
    form.rol = 'capturista';
    form.permisos = [];
    showDialog.value = true;
};

const abrirEdicion = (u: Usuario) => {
    editingId.value = u.id;
    form.clearErrors();
    form.reset('password');
    form.name = u.name;
    form.email = u.email;
    form.rol = (u.rol === 'supervisor' || u.rol === 'capturista') ? u.rol : 'capturista';
    form.permisos = [...(u.permisos ?? [])];
    showDialog.value = true;
};

const togglePermiso = (clave: string) => {
    form.permisos = form.permisos.includes(clave)
        ? form.permisos.filter(p => p !== clave)
        : [...form.permisos, clave];
};

// ── Validación cliente ─────────────────────────────────────────────
const intentado = ref(false);

const errores = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {};

    if (!form.name.trim()) {
        e.name = 'El nombre es obligatorio.';
    }

    if (!form.email.trim()) {
        e.email = 'El correo es obligatorio.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
        e.email = 'Correo electrónico inválido.';
    }

    if (editingId.value === null && form.password.length < 8) {
        e.password = 'La contraseña debe tener al menos 8 caracteres.';
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

watch(() => showDialog, (open) => {
    if (open) {
        intentado.value = false;
    }
});

const guardar = () => {
    intentado.value = true;

    if (Object.keys(errores.value).length > 0) {
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            intentado.value = false;
            form.password = '';
        },
    };

    if (editingId.value === null) {
        form.post('/parametros/usuarios', options);

        return;
    }

    form.put(`/parametros/usuarios/${editingId.value}`, options);
};

const eliminando = ref<number | null>(null);

const eliminar = async (u: Usuario) => {
    const ok = await confirm(`¿Eliminar el usuario "${u.name}"? Se revocará su acceso al sistema.`);

    if (!ok) {
return;
}

    eliminando.value = u.id;
    router.delete(`/parametros/usuarios/${u.id}`, {
        preserveScroll: true,
        onFinish: () => {
 eliminando.value = null; 
},
    });
};

const permisosUsuarios = (u: Usuario) =>
    (u.permisos ?? []).map(clave => props.modulos[clave] ?? clave);
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Usuarios</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ props.usuarios.length }} usuarios · El acceso a los módulos se otorga por permisos
                </p>
            </div>

            <Button @click="abrirNuevo">
                <Plus class="size-4" />
                Nuevo usuario
            </Button>
        </div>

        <!-- Tabla escritorio (≥ lg) -->
        <div class="hidden overflow-x-auto rounded-xl border lg:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium">Correo</th>
                        <th class="px-4 py-3 text-left font-medium">Rol</th>
                        <th class="px-4 py-3 text-left font-medium">Permisos</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="props.usuarios.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-10 text-center text-sm">
                            Sin usuarios registrados.
                        </td>
                    </tr>
                    <tr v-for="u in props.usuarios" :key="u.id" class="transition-colors hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                            {{ u.name }}
                            <span
                                v-if="u.rol === 'admin'"
                                class="ml-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                            >
                                <ShieldCheck class="size-3" />
                                Super admin
                            </span>
                        </td>
                        <td class="text-muted-foreground px-4 py-3 whitespace-nowrap">{{ u.email }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <Badge variant="secondary">{{ rolLabel[u.rol] ?? u.rol }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div v-if="u.rol === 'admin'" class="text-muted-foreground text-xs">
                                Acceso total (no gestionable)
                            </div>
                            <div v-else class="flex flex-wrap gap-1">
                                <span
                                    v-for="label in permisosUsuarios(u)"
                                    :key="label"
                                    class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                >
                                    {{ label }}
                                </span>
                                <span v-if="permisosUsuarios(u).length === 0" class="text-muted-foreground text-xs">
                                    Sin permisos
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <Button
                                    v-if="u.rol !== 'admin'"
                                    variant="ghost"
                                    size="icon"
                                    title="Editar"
                                    @click="abrirEdicion(u)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    v-if="u.rol !== 'admin'"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive"
                                    title="Eliminar"
                                    :disabled="eliminando === u.id"
                                    @click="eliminar(u)"
                                >
                                    <Spinner v-if="eliminando === u.id" class="size-4" />
                                    <Trash2 v-else class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cards móvil (< lg) -->
        <div class="flex flex-col gap-3 lg:hidden">
            <div v-if="props.usuarios.length === 0" class="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                Sin usuarios registrados.
            </div>

            <div v-for="u in props.usuarios" :key="u.id" class="rounded-xl border p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium">
                            {{ u.name }}
                            <span
                                v-if="u.rol === 'admin'"
                                class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-800"
                            >
                                <ShieldCheck class="size-3" />
                                Super admin
                            </span>
                        </p>
                        <p class="text-muted-foreground mt-0.5 truncate text-xs">{{ u.email }}</p>
                        <p class="mt-0.5 text-xs">
                            <Badge variant="secondary">{{ rolLabel[u.rol] ?? u.rol }}</Badge>
                        </p>
                    </div>
                    <div v-if="u.rol !== 'admin'" class="flex flex-shrink-0 gap-1">
                        <Button variant="ghost" size="icon" title="Editar" @click="abrirEdicion(u)">
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-destructive"
                            title="Eliminar"
                            :disabled="eliminando === u.id"
                            @click="eliminar(u)"
                        >
                            <Spinner v-if="eliminando === u.id" class="size-4" />
                            <Trash2 v-else class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="mt-2 border-t pt-2">
                    <p v-if="u.rol === 'admin'" class="text-muted-foreground text-xs">
                        Acceso total (no gestionable)
                    </p>
                    <div v-else class="flex flex-wrap gap-1">
                        <span
                            v-for="label in permisosUsuarios(u)"
                            :key="label"
                            class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                        >
                            {{ label }}
                        </span>
                        <span v-if="permisosUsuarios(u).length === 0" class="text-muted-foreground text-xs">
                            Sin permisos
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog crear/editar -->
        <Dialog :open="showDialog" @update:open="showDialog = $event">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="guardar">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1">
                            <Label>Nombre <span class="text-destructive">*</span></Label>
                            <Input v-model="form.name" maxlength="255" required />
                            <InputError :message="msg('name')" />
                        </div>
                        <div class="space-y-1">
                            <Label>Correo <span class="text-destructive">*</span></Label>
                            <Input v-model="form.email" type="email" maxlength="255" required />
                            <InputError :message="msg('email')" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1">
                            <Label>Rol <span class="text-destructive">*</span></Label>
                            <Select v-model="form.rol">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="supervisor">Supervisor</SelectItem>
                                    <SelectItem value="capturista">Capturista</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1">
                            <Label>
                                Contraseña
                                <span class="text-destructive">{{ editingId === null ? ' *' : '' }}</span>
                            </Label>
                            <Input
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                :required="editingId === null"
                                :placeholder="editingId === null ? '' : 'Dejar vacío para no cambiar'"
                            />
                            <InputError :message="msg('password')" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Permisos de acceso por módulo</Label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="[clave, etiqueta] in modulosList"
                                :key="clave"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm hover:bg-muted/40"
                            >
                                <input
                                    type="checkbox"
                                    class="size-4 rounded accent-primary"
                                    :checked="form.permisos.includes(clave)"
                                    @change="togglePermiso(clave)"
                                />
                                <span class="font-medium">{{ etiqueta }}</span>
                            </label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showDialog = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing" class="gap-1.5">
                            <Spinner v-if="form.processing" class="size-4" />
                            {{ form.processing ? 'Guardando…' : 'Guardar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog />
    </div>
</template>