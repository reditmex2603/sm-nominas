<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Image as ImageIcon, Palette, Trash2, Upload } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';

interface BrandingProps {
    nombre?: string | null;
    color_primario?: string | null;
    color_sidebar?: string | null;
    logo_url?: string | null;
    isotipo_url?: string | null;
}

const props = defineProps<{
    branding: BrandingProps;
}>();

const form = useForm({
    color_primario: props.branding.color_primario ?? '',
    color_sidebar: props.branding.color_sidebar ?? '',
});

const logoForm = useForm({
    archivo: null as File | null,
});

const isotipoForm = useForm({
    archivo: null as File | null,
});

const { confirm } = useConfirm();

const guardarColores = () => {
    form.post('/parametros/marca/colores', { preserveScroll: true });
};

const subirLogo = (cual: 'logo' | 'isotipo') => {
    const f = cual === 'logo' ? logoForm : isotipoForm;

    if (!f.archivo) {
        return;
    }

    f.post(`/parametros/marca/logo/${cual}`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            f.reset('archivo');
        },
    });
};

const eliminarLogo = async (cual: 'logo' | 'isotipo') => {
    const ok = await confirm(`¿Eliminar el ${cual} de la marca? Se quitará de la aplicación y de los documentos.`);

    if (!ok) {
        return;
    }

    router.delete(`/parametros/marca/logo/${cual}`, { preserveScroll: true });
};

const primarioValido = computed(() => /^#?[0-9A-Fa-f]{6}$/.test(form.color_primario.trim()));
const sidebarValido = computed(() => /^#?[0-9A-Fa-f]{6}$/.test(form.color_sidebar.trim()));

const normalizarHex = (valor: string): string => {
    const limpio = valor.trim().replace(/^#/, '');

    return limpio ? `#${limpio.toUpperCase()}` : '';
};
</script>

<template>
    <Head title="Marca" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold">Marca</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Personaliza los colores de la aplicación y agrega el logo (tipo) e isotipo que se usan en el encabezado
                ({{ props.branding.nombre ?? 'SM' }}) y en los documentos imprimibles.
            </p>
        </div>

        <!-- Colores -->
        <div class="rounded-xl border p-5">
            <div class="mb-4 flex items-center gap-2">
                <Palette class="size-4" />
                <h2 class="font-semibold">Colores</h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <Label>Color primario (botones y elementos destacados)</Label>
                    <div class="mt-1.5 flex items-center gap-3">
                        <input
                            v-model="form.color_primario"
                            type="color"
                            class="size-9 cursor-pointer rounded border bg-transparent p-0.5"
                        />
                        <Input v-model="form.color_primario" placeholder="#0f172a" class="max-w-44 font-mono" />
                    </div>
                    <p v-if="!primarioValido" class="text-destructive mt-1 text-xs">Formato hex inválido (#RRGGBB).</p>
                </div>

                <div>
                    <Label>Color del panel lateral</Label>
                    <div class="mt-1.5 flex items-center gap-3">
                        <input
                            v-model="form.color_sidebar"
                            type="color"
                            class="size-9 cursor-pointer rounded border bg-transparent p-0.5"
                        />
                        <Input v-model="form.color_sidebar" placeholder="#0f172a" class="max-w-44 font-mono" />
                    </div>
                    <p v-if="!sidebarValido" class="text-destructive mt-1 text-xs">Formato hex inválido (#RRGGBB).</p>
                </div>
            </div>

            <!-- Vista previa -->
            <div class="mt-5 overflow-hidden rounded-lg border">
                <div class="flex">
                    <div class="flex w-40 flex-col gap-2 p-3" :style="{ backgroundColor: normalizarHex(form.color_sidebar) }">
                        <span class="text-[11px] font-semibold" :style="{ color: normalizarHex(form.color_sidebar) ? '#ffffff' : undefined }">
                            Panel lateral
                        </span>
                        <span
                            class="h-6 rounded bg-black/10"
                        ></span>
                        <span
                            class="h-6 rounded bg-black/10"
                        ></span>
                    </div>
                    <div class="flex flex-1 flex-col gap-2 bg-white p-3">
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                :value="normalizarHex(form.color_primario)"
                                class="size-5 cursor-pointer rounded border bg-transparent p-0"
                            />
                            <span class="text-sm font-medium text-gray-800">Botón primario</span>
                        </div>
                        <div class="h-8 w-28 rounded-md" :style="{ backgroundColor: normalizarHex(form.color_primario) || '#0f172a' }"></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <Button
                    :disabled="form.processing || !primarioValido || !sidebarValido"
                    @click="guardarColores"
                >
                    Guardar colores
                </Button>
            </div>
        </div>

        <!-- Logo e isotipo -->
        <div class="grid gap-4 lg:grid-cols-2">
            <!-- Logo -->
            <div class="rounded-xl border p-5">
                <div class="mb-4 flex items-center gap-2">
                    <ImageIcon class="size-4" />
                    <h2 class="font-semibold">Logo (tipo)</h2>
                </div>

                <div
                    class="flex h-24 items-center justify-center overflow-hidden rounded-lg border bg-muted/40 p-3"
                >
                    <img
                        v-if="props.branding.logo_url"
                        :src="props.branding.logo_url"
                        alt="Logo actual"
                        class="max-h-full max-w-full object-contain"
                    />
                    <span v-else class="text-sm text-muted-foreground">Sin logo — se usa el texto "SM"</span>
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <Label>Reemplazar logo</Label>
                    <div class="flex items-center gap-2">
                        <Input
                            type="file"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            @change="(e: Event) => {
                                const target = e.target as HTMLInputElement;
                                logoForm.archivo = target.files?.[0] ?? null;
                            }"
                        />
                    </div>
                    <div class="mt-2 flex justify-end gap-2">
                        <Button
                            v-if="props.branding.logo_url"
                            variant="outline"
                            :disabled="logoForm.processing"
                            @click="eliminarLogo('logo')"
                        >
                            <Trash2 class="size-4" />
                            Eliminar
                        </Button>
                        <Button :disabled="!logoForm.archivo || logoForm.processing" @click="subirLogo('logo')">
                            <Upload class="size-4" />
                            {{ logoForm.processing ? 'Subiendo…' : 'Guardar logo' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Isotipo -->
            <div class="rounded-xl border p-5">
                <div class="mb-4 flex items-center gap-2">
                    <ImageIcon class="size-4" />
                    <h2 class="font-semibold">Isotipo (ícono)</h2>
                </div>

                <div
                    class="flex h-24 items-center justify-center overflow-hidden rounded-lg border bg-muted/40 p-3"
                >
                    <img
                        v-if="props.branding.isotipo_url"
                        :src="props.branding.isotipo_url"
                        alt="Isotipo actual"
                        class="max-h-full max-w-full object-contain"
                    />
                    <span v-else class="text-sm text-muted-foreground">Sin isotipo — se usa el ícono por defecto</span>
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <Label>Reemplazar isotipo</Label>
                    <div class="flex items-center gap-2">
                        <Input
                            type="file"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            @change="(e: Event) => {
                                const target = e.target as HTMLInputElement;
                                isotipoForm.archivo = target.files?.[0] ?? null;
                            }"
                        />
                    </div>
                    <div class="mt-2 flex justify-end gap-2">
                        <Button
                            v-if="props.branding.isotipo_url"
                            variant="outline"
                            :disabled="isotipoForm.processing"
                            @click="eliminarLogo('isotipo')"
                        >
                            <Trash2 class="size-4" />
                            Eliminar
                        </Button>
                        <Button :disabled="!isotipoForm.archivo || isotipoForm.processing" @click="subirLogo('isotipo')">
                            <Upload class="size-4" />
                            {{ isotipoForm.processing ? 'Subiendo…' : 'Guardar isotipo' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>