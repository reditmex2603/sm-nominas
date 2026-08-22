<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed } from 'vue';

const props = defineProps<{ contenido: string }>();

function slugify(texto: string): string {
    return texto
        .toLowerCase()
        .trim()
        .replace(/[^\w\u00C0-\u024F\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Agrega IDs estilo GitHub a los encabezados para que los enlaces del índice
// funcionen dentro de la página.
function agregarIdsEncabezados(html: string): string {
    const usados: Record<string, number> = {};

    return html.replace(/<(h[1-6])([^>]*)>([\s\S]*?)<\/\1>/g, (match, tag, attrs, inner) => {
        const textoPlano = inner.replace(/<[^>]+>/g, '').trim();
        let id = slugify(textoPlano);

        if (usados[id] !== undefined) {
            usados[id] += 1;
            id = `${id}-${usados[id]}`;
        } else {
            usados[id] = 0;
        }

        return `<${tag} id="${id}">${inner}</${tag}>`;
    });
}

const html = computed(() => agregarIdsEncabezados(DOMPurify.sanitize(marked.parse(props.contenido) as string)));

function alClicEnlace(event: MouseEvent) {
    const ancla = (event.target as HTMLElement).closest('a[href^="#"]') as HTMLAnchorElement | null;

    if (!ancla) {
        return;
    }

    const id = ancla.getAttribute('href')?.slice(1);

    if (!id) {
        return;
    }

    const destino = document.getElementById(id);

    if (destino) {
        event.preventDefault();
        destino.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>

<template>
    <Head title="Manual de usuario" />

    <div class="mx-auto flex h-full w-full max-w-4xl flex-col gap-4 p-4 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold">Manual de usuario</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Guía completa del sistema de nómina SM Producciones
            </p>
        </div>

        <div
            v-if="html"
            class="manual-content rounded-xl border bg-card px-5 py-6 sm:px-8 sm:py-8"
            @click="alClicEnlace"
            v-html="html"
        />
        <div v-else class="text-muted-foreground rounded-xl border p-10 text-center text-sm">
            No hay manual disponible.
        </div>
    </div>
</template>

<style>
.manual-content {
    color: var(--foreground);
    font-size: 0.925rem;
    line-height: 1.7;
    word-wrap: break-word;
}

.manual-content > *:first-child {
    margin-top: 0;
}

.manual-content h1,
.manual-content h2,
.manual-content h3,
.manual-content h4 {
    color: var(--foreground);
    font-weight: 600;
    line-height: 1.3;
    margin: 1.75rem 0 0.75rem;
    scroll-margin-top: 2rem;
}

.manual-content h1 {
    font-size: 1.625rem;
}

.manual-content h2 {
    font-size: 1.25rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid var(--border);
    margin-top: 2.5rem;
}

.manual-content h3 {
    font-size: 1.05rem;
}

.manual-content h4 {
    font-size: 0.95rem;
}

.manual-content p {
    margin: 0.65rem 0;
}

.manual-content ul,
.manual-content ol {
    margin: 0.65rem 0;
    padding-left: 1.5rem;
}

.manual-content ul {
    list-style: disc;
}

.manual-content ol {
    list-style: decimal;
}

.manual-content li {
    margin: 0.3rem 0;
}

.manual-content li > ul,
.manual-content li > ol {
    margin: 0.2rem 0;
}

.manual-content a {
    color: var(--primary);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.manual-content a:hover {
    opacity: 0.8;
}

.manual-content strong {
    font-weight: 600;
}

.manual-content code {
    background: var(--muted);
    border-radius: 4px;
    padding: 0.15rem 0.35rem;
    font-size: 0.82em;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}

.manual-content blockquote {
    margin: 1rem 0;
    padding: 0.6rem 1rem;
    border-left: 3px solid var(--primary);
    background: var(--muted);
    border-radius: 0 6px 6px 0;
}

.manual-content blockquote p {
    margin: 0.2rem 0;
}

.manual-content table {
    width: 100%;
    margin: 1rem 0;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.manual-content th,
.manual-content td {
    border: 1px solid var(--border);
    padding: 0.45rem 0.7rem;
    text-align: left;
    vertical-align: top;
}

.manual-content th {
    background: var(--muted);
    font-weight: 600;
}

.manual-content tr:nth-child(even) td {
    background: color-mix(in srgb, var(--muted) 45%, transparent);
}

.manual-content hr {
    border: none;
    border-top: 1px solid var(--border);
    margin: 1.5rem 0;
}

.manual-content pre {
    background: var(--muted);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 1rem;
    overflow-x: auto;
}

.manual-content pre code {
    background: transparent;
    padding: 0;
}
</style>
