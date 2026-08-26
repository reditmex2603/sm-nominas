<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, Printer, Trash2, Upload } from '@lucide/vue';
import { ref } from 'vue';
import FileInput from '@/components/FileInput.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import * as unidadDocumentos from '@/routes/transportes/unidades/documentos';
import * as unidadPerfil from '@/routes/transportes/unidades/perfil';

type Pertenencia = 'PROPIA' | 'RENTADA';

interface VehiculoRef { id: number; nombre: string }

interface Unidad {
    id: number;
    marca: string;
    modelo: string;
    numero_placas: string | null;
    pertenencia: Pertenencia;
    alias: string | null;
    numero_serie: string | null;
    numero_poliza_seguro: string | null;
    vigencia_poliza_seguro: string | null;
    vigencia_verificacion: string | null;
    tipo_engomado: string | null;
    color_engomado: string | null;
    placas_documento_url: string | null;
    tarjeta_circulacion_documento_url: string | null;
    poliza_seguro_documento_url: string | null;
    verificacion_documento_url: string | null;
    tenencia_documento_url: string | null;
    fotografia_url: string | null;
    vehiculo: VehiculoRef | null;
}

const props = defineProps<{
    unidad: Unidad;
    vehiculos: VehiculoRef[];
}>();

const pertenenciaBadge: Record<Pertenencia, { label: string; class: string }> = {
    PROPIA: { label: 'Propia', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' },
    RENTADA: { label: 'Rentada', class: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' },
};

const TIPOS_ENGOMADO = ['EXENTO', '0', '00', '1', '2'];
const COLORES_ENGOMADO = ['Verde', 'Amarillo', 'Rosa', 'Azul', 'Gris', 'Naranja'];
const MAX_MB = 5;
const DOCUMENTO_MIMES = ['image/jpeg', 'image/png', 'application/pdf'];

const form = useForm({
    alias: props.unidad.alias ?? '',
    numero_serie: props.unidad.numero_serie ?? '',
    numero_poliza_seguro: props.unidad.numero_poliza_seguro ?? '',
    vigencia_poliza_seguro: props.unidad.vigencia_poliza_seguro ?? '',
    vigencia_verificacion: props.unidad.vigencia_verificacion ?? '',
    tipo_engomado: props.unidad.tipo_engomado ?? '',
    color_engomado: props.unidad.color_engomado ?? '',
    fotografia: null as File | null,
    placas_documento: null as File | null,
    tarjeta_circulacion_documento: null as File | null,
    poliza_seguro_documento: null as File | null,
    verificacion_documento: null as File | null,
    tenencia_documento: null as File | null,
});

const fotoPreviewUrl = ref<string | null>(null);
const fotoError = ref('');

const elegirFotografia = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;

    if (file) {
        const esValida = file.type.startsWith('image/') && ['image/jpeg', 'image/png'].includes(file.type);
        const cabe = file.size <= MAX_MB * 1024 * 1024;

        if (!esValida) {
            fotoError.value = 'Formato no permitido. Usa JPG o PNG.';
            (e.target as HTMLInputElement).value = '';

            return;
        }

        if (!cabe) {
            fotoError.value = `El archivo excede los ${MAX_MB} MB permitidos.`;
            (e.target as HTMLInputElement).value = '';

            return;
        }
    }

    fotoError.value = '';
    form.fotografia = file;

    if (fotoPreviewUrl.value) {
        URL.revokeObjectURL(fotoPreviewUrl.value);
    }

    fotoPreviewUrl.value = file ? URL.createObjectURL(file) : null;
};

const guardar = () => {
    form.post(unidadDocumentos.update.url({ unidad: props.unidad.id }), {
        preserveScroll: true,
        onSuccess: () => {
            form.fotografia = null;

            if (fotoPreviewUrl.value) {
                URL.revokeObjectURL(fotoPreviewUrl.value);
                fotoPreviewUrl.value = null;
            }

            form.placas_documento = null;
            form.tarjeta_circulacion_documento = null;
            form.poliza_seguro_documento = null;
            form.verificacion_documento = null;
            form.tenencia_documento = null;
        },
    });
};

const { confirm } = useConfirm();

const eliminarDocumento = async (campo: string, label: string) => {
    const ok = await confirm(`¿Eliminar el documento "${label}"? Esta acción no se puede deshacer.`, {
        title: 'Eliminar documento',
    });

    if (ok) {
        router.delete(unidadDocumentos.eliminar.url({ unidad: props.unidad.id, campo }), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="`${unidad.marca} ${unidad.modelo} — Unidad de transporte`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex items-start gap-3 sm:items-center">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/transportes">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <div>
                <h1 class="text-2xl font-semibold">{{ unidad.marca }} {{ unidad.modelo }}</h1>
                <p class="text-muted-foreground mt-0.5 text-sm">Unidad de transporte</p>
            </div>
            <Button
                variant="outline"
                class="ml-auto"
                as-child
            >
                <a
                    :href="unidadPerfil.imprimir.url({ unidad: props.unidad.id })"
                    target="_blank"
                    rel="noopener"
                >
                    <Printer class="size-4" />
                    Imprimir perfil
                </a>
            </Button>
        </div>

        <!-- Información ya establecida (solo lectura — se edita desde Transportes) -->
        <fieldset class="rounded-xl border p-4">
            <legend class="px-1 text-sm font-medium">Información general</legend>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:grid-cols-4">
                <div>
                    <dt class="text-muted-foreground text-xs">Placas</dt>
                    <dd class="mt-0.5">{{ unidad.numero_placas ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-xs">Pertenencia</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium" :class="pertenenciaBadge[unidad.pertenencia].class">
                            {{ pertenenciaBadge[unidad.pertenencia].label }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-xs">Categoría (tarifa)</dt>
                    <dd class="mt-0.5">{{ unidad.vehiculo?.nombre ?? '—' }}</dd>
                </div>
            </dl>
        </fieldset>

        <form class="flex flex-col gap-6" @submit.prevent="guardar">
            <!-- Fotografía de la unidad -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Fotografía de la unidad</legend>

                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex size-28 items-center justify-center overflow-hidden rounded-xl border bg-slate-100">
                        <img
                            v-if="fotoPreviewUrl || unidad.fotografia_url"
                            :src="fotoPreviewUrl ?? unidad.fotografia_url ?? undefined"
                            :alt="`${unidad.marca} ${unidad.modelo}`"
                            class="size-full object-cover transition-opacity duration-200"
                        />
                        <span v-else class="text-sm text-muted-foreground">Sin foto</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <Input type="file" accept=".jpg,.jpeg,.png" class="max-w-xs" @change="elegirFotografia" />
                        <p v-if="fotoError" class="text-destructive flex items-center gap-1 text-xs" role="alert">
                            {{ fotoError }}
                        </p>
                        <InputError :message="form.errors.fotografia" />
                        <Button
                            v-if="unidad.fotografia_url"
                            type="button" size="sm" variant="ghost" class="text-destructive w-fit"
                            @click="eliminarDocumento('fotografia', 'Fotografía de la unidad')"
                        >
                            <Trash2 class="size-3.5" />
                            Eliminar foto
                        </Button>
                    </div>
                </div>
            </fieldset>

            <!-- Identificación -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Identificación</legend>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label>Alias</Label>
                        <Input v-model="form.alias" placeholder="Ej. Unidad 01" />
                    </div>
                    <div class="space-y-1">
                        <Label>Número de serie</Label>
                        <Input v-model="form.numero_serie" placeholder="Ej. 1HGCM82633A004352" />
                    </div>
                </div>
            </fieldset>

            <!-- Póliza de seguro -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Póliza de seguro</legend>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label>Número de póliza</Label>
                        <Input v-model="form.numero_poliza_seguro" placeholder="Número de póliza" />
                    </div>
                    <div class="space-y-1">
                        <Label>Vigencia</Label>
                        <Input v-model="form.vigencia_poliza_seguro" type="date" />
                    </div>
                </div>

                <div class="space-y-1">
                    <Label>Documento de póliza</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <FileInput
                            v-model="form.poliza_seguro_documento"
                            accept=".jpg,.jpeg,.png,.pdf"
                            :mimes="DOCUMENTO_MIMES"
                            :max-mb="MAX_MB"
                            :error="form.errors.poliza_seguro_documento"
                        />
                        <a v-if="unidad.poliza_seguro_documento_url" :href="unidad.poliza_seguro_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="unidad.poliza_seguro_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('poliza_seguro', 'Póliza de seguro')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </fieldset>

            <!-- Verificación -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Verificación</legend>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="space-y-1">
                        <Label>Vencimiento de verificación</Label>
                        <Input v-model="form.vigencia_verificacion" type="date" />
                    </div>
                    <div class="space-y-1">
                        <Label>Tipo de engomado</Label>
                        <Select v-model="form.tipo_engomado">
                            <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in TIPOS_ENGOMADO" :key="t" :value="t">{{ t }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1">
                        <Label>Color de engomado</Label>
                        <Select v-model="form.color_engomado">
                            <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in COLORES_ENGOMADO" :key="c" :value="c">{{ c }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label>Comprobante de verificación (foto)</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <FileInput
                            v-model="form.verificacion_documento"
                            accept=".jpg,.jpeg,.png,.pdf"
                            :mimes="DOCUMENTO_MIMES"
                            :max-mb="MAX_MB"
                            :error="form.errors.verificacion_documento"
                        />
                        <a v-if="unidad.verificacion_documento_url" :href="unidad.verificacion_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="unidad.verificacion_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('verificacion', 'Verificación')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </fieldset>

            <!-- Documentos del vehículo -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Documentos del vehículo</legend>

                <div class="space-y-1">
                    <Label>Placas</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <FileInput
                            v-model="form.placas_documento"
                            accept=".jpg,.jpeg,.png,.pdf"
                            :mimes="DOCUMENTO_MIMES"
                            :max-mb="MAX_MB"
                            :error="form.errors.placas_documento"
                        />
                        <a v-if="unidad.placas_documento_url" :href="unidad.placas_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="unidad.placas_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('placas', 'Placas')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label>Tarjeta de circulación</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <FileInput
                            v-model="form.tarjeta_circulacion_documento"
                            accept=".jpg,.jpeg,.png,.pdf"
                            :mimes="DOCUMENTO_MIMES"
                            :max-mb="MAX_MB"
                            :error="form.errors.tarjeta_circulacion_documento"
                        />
                        <a v-if="unidad.tarjeta_circulacion_documento_url" :href="unidad.tarjeta_circulacion_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="unidad.tarjeta_circulacion_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('tarjeta_circulacion', 'Tarjeta de circulación')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label>Documento de tenencia</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <FileInput
                            v-model="form.tenencia_documento"
                            accept=".jpg,.jpeg,.png,.pdf"
                            :mimes="DOCUMENTO_MIMES"
                            :max-mb="MAX_MB"
                            :error="form.errors.tenencia_documento"
                        />
                        <a v-if="unidad.tenencia_documento_url" :href="unidad.tenencia_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="unidad.tenencia_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('tenencia', 'Tenencia')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </fieldset>

            <div>
                <Button type="submit" :disabled="form.processing" class="gap-1.5">
                    <Spinner v-if="form.processing" class="size-4" />
                    <Upload v-else class="size-4" />
                    {{ form.processing ? 'Guardando…' : 'Guardar' }}
                </Button>
            </div>
        </form>
    </div>
</template>