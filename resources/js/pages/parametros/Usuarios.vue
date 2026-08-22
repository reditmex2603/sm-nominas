<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, ShieldCheck, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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

const guardar = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            form.password = '';
        },
    };

    if (editingId.value === null) {
        form.post('/parametros/usuarios', options);

        return;
    }

    form.put(`/parametros/usuarios/${editingId.value}`, options);
};

const eliminar = async (u: Usuario) => {
    const ok = await confirm(`¿Eliminar el usuario "${u.name}"? Se revocará su acceso al sistema.`);

    if (!ok) {
return;
}

    router.delete(`/parametros/usuarios/${u.id}`, { preserveScroll: true });
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

        <!-- Tabla -->
        <div class="overflow-x-auto rounded-xl border">
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
                    <tr v-for="u in props.usuarios" :key="u.id" class="hover:bg-muted/30">
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
                                    @click="eliminar(u)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
                            <Input v-model="form.name" required />
                            <p v-if="form.errors.name" class="text-destructive text-xs">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Correo <span class="text-destructive">*</span></Label>
                            <Input v-model="form.email" type="email" required />
                            <p v-if="form.errors.email" class="text-destructive text-xs">{{ form.errors.email }}</p>
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
                                :required="editingId === null"
                                :placeholder="editingId === null ? '' : 'Dejar vacío para no cambiar'"
                            />
                            <p v-if="form.errors.password" class="text-destructive text-xs">
                                {{ form.errors.password }}
                            </p>
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
                        <Button type="submit" :disabled="form.processing">Guardar</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog />
    </div>
</template>