import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface Branding {
    nombre?: string | null;
    color_primario?: string | null;
    color_sidebar?: string | null;
    logo_url?: string | null;
    isotipo_url?: string | null;
}

interface Rgb {
    r: number;
    g: number;
    b: number;
}

const defaultBranding: Branding = {};

const hexToRgb = (hex: string): Rgb | null => {
    const match = /^#?([0-9a-f]{6})$/i.exec(hex.trim());

    if (!match) {
return null;
}

    const value = parseInt(match[1], 16);

    return { r: (value >> 16) & 0xff, g: (value >> 8) & 0xff, b: value & 0xff };
};

const luminancia = ({ r, g, b }: Rgb): number => {
    const canal = (c: number): number => {
        const s = c / 255;

        return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
    };

    return 0.2126 * canal(r) + 0.7152 * canal(g) + 0.0722 * canal(b);
};

const mejorFrente = (hex: string): string => {
    const rgb = hexToRgb(hex);

    return rgb && luminancia(rgb) > 0.35 ? '#1f2937' : '#ffffff';
};

const mezclar = (hex: string, target: string, peso: number): string => {
    const base = hexToRgb(hex);
    const objetivo = hexToRgb(target);

    if (!base || !objetivo) {
return hex;
}

    const canal = (b: number, o: number): number => Math.round(b + (o - b) * peso);
    const aHex = (v: number): string => v.toString(16).padStart(2, '0');

    return `#${aHex(canal(base.r, objetivo.r))}${aHex(canal(base.g, objetivo.g))}${aHex(canal(base.b, objetivo.b))}`;
};

export function useBranding() {
    const page = usePage<{ branding?: Branding }>();
    const branding = computed<Branding>(() => page.props.branding ?? defaultBranding);

    return { branding };
}

export function aplicarMarca(branding: Branding): void {
    const root = document.documentElement;
    const primario = branding.color_primario;
    const sidebar = branding.color_sidebar;

    if (primario) {
        root.style.setProperty('--primary', primario);
        root.style.setProperty('--ring', primario);
        root.style.setProperty('--primary-foreground', mejorFrente(primario));
    }

    if (sidebar) {
        root.style.setProperty('--sidebar-background', sidebar);

        const frente = mejorFrente(sidebar);

        root.style.setProperty('--sidebar-foreground', frente);
        root.style.setProperty('--sidebar-accent', mezclar(sidebar, frente, 0.08));
        root.style.setProperty('--sidebar-accent-foreground', frente);
        root.style.setProperty('--sidebar-border', mezclar(sidebar, frente, 0.05));
        root.style.setProperty('--sidebar-primary', primario ?? mezclar(sidebar, frente, 0.12));
        root.style.setProperty('--sidebar-primary-foreground', mejorFrente(primario ?? sidebar));
    }
}