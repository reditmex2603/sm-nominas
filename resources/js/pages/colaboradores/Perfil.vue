<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, FileText, Printer, Trash2, Upload, User } from '@lucide/vue';
import { ref } from 'vue';
import DocumentosImprimirPanel from '@/components/DocumentosImprimirPanel.vue';
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
import { useConfirm } from '@/composables/useConfirm';
import * as perfilRoutes from '@/routes/colaboradores/perfil';
import * as perfilDocumento from '@/routes/colaboradores/perfil/documento';

type TipoColaborador = 'COLABORADOR BASE' | 'FREELANCE' | 'CONDUCTOR' | 'CONDUCTOR BASE';

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
}

interface Perfil {
    alias: string | null;
    fotografia_url: string | null;
    fecha_ingreso: string | null;
    correo: string | null;
    telefono: string | null;
    whatsapp: string | null;
    redes_sociales: string | null;
    domicilio: string | null;
    genero: string | null;
    ubicacion_maps: string | null;
    fecha_nacimiento: string | null;
    tipo_sangre: string | null;
    alergias: string | null;
    padecimientos_cronicos: string | null;
    numero_seguro_social: string | null;
    contacto_emergencia_1_nombre: string | null;
    contacto_emergencia_1_parentesco: string | null;
    contacto_emergencia_1_telefono: string | null;
    contacto_emergencia_2_nombre: string | null;
    contacto_emergencia_2_parentesco: string | null;
    contacto_emergencia_2_telefono: string | null;
    banco: string | null;
    beneficiario: string | null;
    clave_interbancaria: string | null;
    seguro_social_documento_url: string | null;
    ine_documento_url: string | null;
    curp_documento_url: string | null;
    comprobante_domicilio_documento_url: string | null;
    licencia_conducir_documento_url: string | null;
}

const props = defineProps<{
    colaborador: Colaborador;
    perfil: Perfil | null;
}>();

const tipoBadge: Record<TipoColaborador, { label: string; class: string }> = {
    'COLABORADOR BASE': { label: 'Base', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' },
    'FREELANCE': { label: 'Freelance', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    'CONDUCTOR': { label: 'Conductor', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    'CONDUCTOR BASE': { label: 'Conductor base', class: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
};

const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as const;
const GENEROS = ['Masculino', 'Femenino'] as const;

const form = useForm({
    alias: props.perfil?.alias ?? '',
    fotografia: null as File | null,
    fecha_ingreso: props.perfil?.fecha_ingreso ?? '',
    correo: props.perfil?.correo ?? '',
    telefono: props.perfil?.telefono ?? '',
    whatsapp: props.perfil?.whatsapp ?? '',
    redes_sociales: props.perfil?.redes_sociales ?? '',
    domicilio: props.perfil?.domicilio ?? '',
    genero: props.perfil?.genero ?? '',
    ubicacion_maps: props.perfil?.ubicacion_maps ?? '',
    fecha_nacimiento: props.perfil?.fecha_nacimiento ?? '',
    contacto_emergencia_1_nombre: props.perfil?.contacto_emergencia_1_nombre ?? '',
    contacto_emergencia_1_parentesco: props.perfil?.contacto_emergencia_1_parentesco ?? '',
    contacto_emergencia_1_telefono: props.perfil?.contacto_emergencia_1_telefono ?? '',
    contacto_emergencia_2_nombre: props.perfil?.contacto_emergencia_2_nombre ?? '',
    contacto_emergencia_2_parentesco: props.perfil?.contacto_emergencia_2_parentesco ?? '',
    contacto_emergencia_2_telefono: props.perfil?.contacto_emergencia_2_telefono ?? '',
    banco: props.perfil?.banco ?? '',
    beneficiario: props.perfil?.beneficiario ?? '',
    clave_interbancaria: props.perfil?.clave_interbancaria ?? '',
    tipo_sangre: props.perfil?.tipo_sangre ?? '',
    alergias: props.perfil?.alergias ?? '',
    padecimientos_cronicos: props.perfil?.padecimientos_cronicos ?? '',
    numero_seguro_social: props.perfil?.numero_seguro_social ?? '',
    seguro_social_documento: null as File | null,
    ine_documento: null as File | null,
    curp_documento: null as File | null,
    comprobante_domicilio_documento: null as File | null,
    licencia_conducir_documento: null as File | null,
});

const fotoPreviewUrl = ref<string | null>(props.perfil?.fotografia_url ?? null);

const elegirFotografia = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;

    if (fotoPreviewUrl.value) {
        URL.revokeObjectURL(fotoPreviewUrl.value);
    }

    form.fotografia = file;
    fotoPreviewUrl.value = file ? URL.createObjectURL(file) : (props.perfil?.fotografia_url ?? null);
};

type CampoArchivo = 'seguro_social_documento' | 'ine_documento' | 'curp_documento' | 'comprobante_domicilio_documento' | 'licencia_conducir_documento';

const elegirArchivo = (campo: CampoArchivo, e: Event) => {
    form[campo] = (e.target as HTMLInputElement).files?.[0] ?? null;
};

const guardar = () => {
    form.post(perfilRoutes.update.url({ colaborador: props.colaborador.id }), {
        preserveScroll: true,
        onSuccess: () => {
            if (fotoPreviewUrl.value) {
                URL.revokeObjectURL(fotoPreviewUrl.value);
            }

            form.fotografia = null;
            fotoPreviewUrl.value = props.perfil?.fotografia_url ?? null;
            form.seguro_social_documento = null;
            form.ine_documento = null;
            form.curp_documento = null;
            form.comprobante_domicilio_documento = null;
            form.licencia_conducir_documento = null;
        },
    });
};

const { confirm } = useConfirm();

const eliminarDocumento = async (campo: string, label: string) => {
    const ok = await confirm(`¿Eliminar el documento "${label}"? Esta acción no se puede deshacer.`, {
        title: 'Eliminar documento',
    });

    if (ok) {
        router.delete(perfilDocumento.eliminar.url({ colaborador: props.colaborador.id, campo }), {
            preserveScroll: true,
        });
    }
};

const abrirImprimir = () => {
    window.open(`/colaboradores/${props.colaborador.id}/perfil/imprimir`, '_blank');
};

const mostrarDocumentos = ref(false);
</script>

<template>
    <Head :title="`Perfil — ${colaborador.nombre} ${colaborador.apellidos}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex items-start gap-3 sm:items-center">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/colaboradores">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <div>
                <h1 class="text-2xl font-semibold">{{ colaborador.apellidos }}, {{ colaborador.nombre }}</h1>
                <p class="text-muted-foreground mt-0.5 text-sm">Perfil de colaborador</p>
            </div>
            <Button variant="outline" size="sm" class="ml-auto gap-1.5" @click="abrirImprimir">
                <Printer class="size-3.5" />
                Imprimir
            </Button>
            <Button variant="outline" size="sm" class="gap-1.5" @click="mostrarDocumentos = true">
                <FileText class="size-3.5" />
                Imprimir documentos
            </Button>
        </div>

        <!-- Información ya establecida (solo lectura — se edita desde Colaboradores) -->
        <fieldset class="rounded-xl border p-4">
            <legend class="px-1 text-sm font-medium">Información general</legend>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:grid-cols-4">
                <div>
                    <dt class="text-muted-foreground text-xs">Tipo</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium" :class="tipoBadge[colaborador.tipo].class">
                            {{ tipoBadge[colaborador.tipo].label }}
                        </span>
                    </dd>
                </div>
                <template v-if="colaborador.tipo === 'COLABORADOR BASE'">
                    <div>
                        <dt class="text-muted-foreground text-xs">Categoría</dt>
                        <dd class="mt-0.5">{{ colaborador.categoria ?? '—' }}<template v-if="colaborador.nivel"> · Nivel {{ colaborador.nivel }}</template></dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs">Sueldo diario</dt>
                        <dd class="mt-0.5">{{ colaborador.sueldo_diario ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs">Compensación</dt>
                        <dd class="mt-0.5">{{ colaborador.compensacion_pct }}%</dd>
                    </div>
                </template>
                <div v-if="colaborador.tipo === 'FREELANCE'">
                    <dt class="text-muted-foreground text-xs">Extra día adicional</dt>
                    <dd class="mt-0.5">{{ colaborador.extra_dia_adicional ?? '—' }}</dd>
                </div>
            </dl>
        </fieldset>

        <form class="flex flex-col gap-6" @submit.prevent="guardar">
            <!-- Datos personales -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Datos personales</legend>

                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex flex-col items-center gap-1.5">
                        <div class="flex size-24 items-center justify-center overflow-hidden rounded-full border bg-muted">
                            <img v-if="fotoPreviewUrl" :src="fotoPreviewUrl" alt="Fotografía" class="size-full object-cover" />
                            <User v-else class="size-10 text-muted-foreground" />
                        </div>
                        <Label class="cursor-pointer text-xs font-normal underline">
                            <input type="file" accept="image/*" class="hidden" @change="elegirFotografia" />
                            {{ form.fotografia || props.perfil?.fotografia_url ? 'Cambiar foto' : 'Subir foto' }}
                        </Label>
                        <p v-if="form.errors.fotografia" class="text-destructive text-xs">{{ form.errors.fotografia }}</p>
                    </div>

                    <div class="grid flex-1 grid-cols-2 gap-4 md:grid-cols-4">
                        <div class="space-y-1">
                            <Label>Alias</Label>
                            <Input v-model="form.alias" placeholder="Ej. Charly" />
                            <p v-if="form.errors.alias" class="text-destructive text-xs">{{ form.errors.alias }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Fecha de ingreso <span class="text-destructive">*</span></Label>
                            <Input v-model="form.fecha_ingreso" type="date" required />
                            <p v-if="form.errors.fecha_ingreso" class="text-destructive text-xs">{{ form.errors.fecha_ingreso }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Fecha de nacimiento</Label>
                            <Input v-model="form.fecha_nacimiento" type="date" />
                            <p v-if="form.errors.fecha_nacimiento" class="text-destructive text-xs">{{ form.errors.fecha_nacimiento }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Género</Label>
                            <Select v-model="form.genero">
                                <SelectTrigger><SelectValue placeholder="Sin especificar" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="g in GENEROS" :key="g" :value="g">{{ g }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.genero" class="text-destructive text-xs">{{ form.errors.genero }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div class="space-y-1">
                        <Label>Correo</Label>
                        <Input v-model="form.correo" type="email" placeholder="correo@ejemplo.com" />
                        <p v-if="form.errors.correo" class="text-destructive text-xs">{{ form.errors.correo }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Número de teléfono <span class="text-destructive">*</span></Label>
                        <Input v-model="form.telefono" type="tel" required />
                        <p v-if="form.errors.telefono" class="text-destructive text-xs">{{ form.errors.telefono }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>WhatsApp <span class="text-destructive">*</span></Label>
                        <Input v-model="form.whatsapp" type="tel" required />
                        <p v-if="form.errors.whatsapp" class="text-destructive text-xs">{{ form.errors.whatsapp }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div class="space-y-1">
                        <Label>Enlace de redes sociales</Label>
                        <Input v-model="form.redes_sociales" type="url" placeholder="https://instagram.com/usuario" />
                        <p v-if="form.errors.redes_sociales" class="text-destructive text-xs">{{ form.errors.redes_sociales }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Ubicación en Maps</Label>
                        <Input v-model="form.ubicacion_maps" type="url" placeholder="https://maps.app.goo.gl/..." />
                        <p v-if="form.errors.ubicacion_maps" class="text-destructive text-xs">{{ form.errors.ubicacion_maps }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label>Domicilio</Label>
                    <textarea
                        v-model="form.domicilio"
                        rows="2"
                        placeholder="Calle, número, colonia, ciudad..."
                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                    />
                    <p v-if="form.errors.domicilio" class="text-destructive text-xs">{{ form.errors.domicilio }}</p>
                </div>
            </fieldset>

            <!-- Datos de emergencia -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Datos de emergencia</legend>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div class="space-y-1">
                        <Label>Tipo de sangre</Label>
                        <Select v-model="form.tipo_sangre">
                            <SelectTrigger><SelectValue placeholder="Sin especificar" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in TIPOS_SANGRE" :key="t" :value="t">{{ t }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="col-span-2 space-y-1">
                        <Label>Número de seguro social</Label>
                        <Input v-model="form.numero_seguro_social" placeholder="NSS" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-1">
                        <Label>Alergias</Label>
                        <textarea
                            v-model="form.alergias"
                            rows="3"
                            placeholder="Ninguna conocida"
                            class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label>Padecimientos crónicos</Label>
                        <textarea
                            v-model="form.padecimientos_cronicos"
                            rows="3"
                            placeholder="Ninguno conocido"
                            class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full resize-none rounded-md border bg-transparent px-3 py-1.5 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                        />
                    </div>
                </div>

                <div class="space-y-1">
                    <Label>Documento de seguro social</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input type="file" accept="image/*,.pdf" class="max-w-xs" @change="(e: Event) => elegirArchivo('seguro_social_documento', e)" />
                        <a v-if="perfil?.seguro_social_documento_url" :href="perfil.seguro_social_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="perfil?.seguro_social_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('seguro_social', 'Seguro social')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                    <p v-if="form.errors.seguro_social_documento" class="text-destructive text-xs">{{ form.errors.seguro_social_documento }}</p>
                </div>
            </fieldset>

            <!-- Contactos de emergencia -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Contactos de emergencia</legend>

                <div>
                    <Label class="text-muted-foreground text-xs uppercase">Contacto 1</Label>
                    <div class="mt-1 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-1">
                            <Label>Nombre</Label>
                            <Input v-model="form.contacto_emergencia_1_nombre" placeholder="Nombre completo" />
                            <p v-if="form.errors.contacto_emergencia_1_nombre" class="text-destructive text-xs">{{ form.errors.contacto_emergencia_1_nombre }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Parentesco</Label>
                            <Input v-model="form.contacto_emergencia_1_parentesco" placeholder="Ej. Esposa, Hermano, Amigo" />
                            <p v-if="form.errors.contacto_emergencia_1_parentesco" class="text-destructive text-xs">{{ form.errors.contacto_emergencia_1_parentesco }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Número de teléfono</Label>
                            <Input v-model="form.contacto_emergencia_1_telefono" type="tel" placeholder="10 dígitos" />
                            <p v-if="form.errors.contacto_emergencia_1_telefono" class="text-destructive text-xs">{{ form.errors.contacto_emergencia_1_telefono }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <Label class="text-muted-foreground text-xs uppercase">Contacto 2</Label>
                    <div class="mt-1 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-1">
                            <Label>Nombre</Label>
                            <Input v-model="form.contacto_emergencia_2_nombre" placeholder="Nombre completo" />
                            <p v-if="form.errors.contacto_emergencia_2_nombre" class="text-destructive text-xs">{{ form.errors.contacto_emergencia_2_nombre }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Parentesco</Label>
                            <Input v-model="form.contacto_emergencia_2_parentesco" placeholder="Ej. Esposa, Hermano, Amigo" />
                            <p v-if="form.errors.contacto_emergencia_2_parentesco" class="text-destructive text-xs">{{ form.errors.contacto_emergencia_2_parentesco }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Número de teléfono</Label>
                            <Input v-model="form.contacto_emergencia_2_telefono" type="tel" placeholder="10 dígitos" />
                            <p v-if="form.errors.contacto_emergencia_2_telefono" class="text-destructive text-xs">{{ form.errors.contacto_emergencia_2_telefono }}</p>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Documentos de identificación -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Documentos de identificación</legend>

                <div class="space-y-1">
                    <Label>INE</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input type="file" accept="image/*,.pdf" class="max-w-xs" @change="(e: Event) => elegirArchivo('ine_documento', e)" />
                        <a v-if="perfil?.ine_documento_url" :href="perfil.ine_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="perfil?.ine_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('ine', 'INE')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                    <p v-if="form.errors.ine_documento" class="text-destructive text-xs">{{ form.errors.ine_documento }}</p>
                </div>

                <div class="space-y-1">
                    <Label>CURP</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input type="file" accept="image/*,.pdf" class="max-w-xs" @change="(e: Event) => elegirArchivo('curp_documento', e)" />
                        <a v-if="perfil?.curp_documento_url" :href="perfil.curp_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="perfil?.curp_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('curp', 'CURP')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                    <p v-if="form.errors.curp_documento" class="text-destructive text-xs">{{ form.errors.curp_documento }}</p>
                </div>

                <div class="space-y-1">
                    <Label>Comprobante de domicilio</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input type="file" accept="image/*,.pdf" class="max-w-xs" @change="(e: Event) => elegirArchivo('comprobante_domicilio_documento', e)" />
                        <a v-if="perfil?.comprobante_domicilio_documento_url" :href="perfil.comprobante_domicilio_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="perfil?.comprobante_domicilio_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('comprobante_domicilio', 'Comprobante de domicilio')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                    <p v-if="form.errors.comprobante_domicilio_documento" class="text-destructive text-xs">{{ form.errors.comprobante_domicilio_documento }}</p>
                </div>

                <div class="space-y-1">
                    <Label>Licencia de conducir</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input type="file" accept="image/*,.pdf" class="max-w-xs" @change="(e: Event) => elegirArchivo('licencia_conducir_documento', e)" />
                        <a v-if="perfil?.licencia_conducir_documento_url" :href="perfil.licencia_conducir_documento_url" target="_blank" rel="noopener">
                            <Button type="button" size="sm" variant="outline"><Eye class="size-3.5" />Ver</Button>
                        </a>
                        <Button
                            v-if="perfil?.licencia_conducir_documento_url"
                            type="button" size="sm" variant="ghost" class="text-destructive"
                            @click="eliminarDocumento('licencia_conducir', 'Licencia de conducir')"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                    <p v-if="form.errors.licencia_conducir_documento" class="text-destructive text-xs">{{ form.errors.licencia_conducir_documento }}</p>
                </div>
            </fieldset>

            <!-- Datos bancarios -->
            <fieldset class="space-y-4 rounded-xl border p-4">
                <legend class="px-1 text-sm font-medium">Datos bancarios</legend>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div class="space-y-1">
                        <Label>Banco</Label>
                        <Input v-model="form.banco" placeholder="Ej. BBVA" />
                        <p v-if="form.errors.banco" class="text-destructive text-xs">{{ form.errors.banco }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Beneficiario</Label>
                        <Input v-model="form.beneficiario" placeholder="Nombre del titular" />
                        <p v-if="form.errors.beneficiario" class="text-destructive text-xs">{{ form.errors.beneficiario }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Clave interbancaria (CLABE)</Label>
                        <Input v-model="form.clave_interbancaria" maxlength="18" placeholder="18 dígitos" />
                        <p v-if="form.errors.clave_interbancaria" class="text-destructive text-xs">{{ form.errors.clave_interbancaria }}</p>
                    </div>
                </div>
            </fieldset>

            <div>
                <Button type="submit" :disabled="form.processing">
                    <Upload class="size-4" />
                    Guardar perfil
                </Button>
            </div>
        </form>
    </div>

    <DocumentosImprimirPanel
        v-if="mostrarDocumentos"
        :colaborador="colaborador"
        :perfil="perfil"
        @close="mostrarDocumentos = false"
    />
</template>
