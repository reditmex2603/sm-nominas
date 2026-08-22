<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Banknote,
    BookOpen,
    Briefcase,
    CalendarDays,
    ClipboardCheck,
    ClipboardList,
    HandCoins,
    History,
    LayoutDashboard,
    Palette,
    Receipt,
    Settings,
    Truck,
    UserCog,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import * as anticipos from '@/routes/anticipos';
import * as colaboradores from '@/routes/colaboradores';
import * as eventos from '@/routes/eventos';
import * as historial from '@/routes/historial';
import * as manual from '@/routes/manual';
import * as parametros from '@/routes/parametros';
import * as prestamos from '@/routes/prestamos';
import * as registroAsistencia from '@/routes/registro-asistencia';
import * as serviciosProfesionales from '@/routes/servicios-profesionales';
import * as transportes from '@/routes/transportes';
import * as validacion from '@/routes/validacion';
import * as viaticos from '@/routes/viaticos';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// El super admin (rol admin) tiene acceso a todos los módulos; el resto depende de sus permisos.
const tieneAcceso = (permiso: string) =>
    user.value?.rol === 'admin' || (user.value?.permisos ?? []).includes(permiso);

const mainNavItems: (NavItem & { permiso: string | null })[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, permiso: null },
    { title: 'Panel Validación', href: validacion.index.url(), icon: ClipboardCheck, permiso: 'validacion' },
    { title: 'Colaboradores', href: colaboradores.index.url(), icon: Users, permiso: 'colaboradores' },
    { title: 'Eventos', href: eventos.index.url(), icon: CalendarDays, permiso: 'eventos' },
    { title: 'Transportes', href: transportes.index.url(), icon: Truck, permiso: 'transportes' },
    { title: 'Anticipos', href: anticipos.index.url(), icon: Banknote, permiso: 'anticipos' },
    { title: 'Préstamos', href: prestamos.index.url(), icon: HandCoins, permiso: 'prestamos' },
    { title: 'Servicios Prof.', href: serviciosProfesionales.index.url(), icon: Briefcase, permiso: 'servicios-profesionales' },
    { title: 'Viáticos', href: viaticos.index.url(), icon: Receipt, permiso: 'viaticos' },
    { title: 'Historial', href: historial.index.url(), icon: History, permiso: 'historial' },
    { title: 'Registro Asistencia', href: registroAsistencia.index.url(), icon: ClipboardList, permiso: 'registro-asistencia' },
    { title: 'Parámetros', href: parametros.index.url(), icon: Settings, permiso: 'parametros' },
];

const mainNavVisible = computed<NavItem[]>(() =>
    mainNavItems.filter(i => i.permiso === null || tieneAcceso(i.permiso)),
);

// Administración — solo el super admin.
const adminNavVisible = computed<NavItem[]>(() =>
    user.value?.rol === 'admin'
        ? [
              { title: 'Usuarios', href: '/parametros/usuarios', icon: UserCog, isActive: false },
              { title: 'Marca', href: '/parametros/marca', icon: Palette, isActive: false },
          ]
        : [],
);

const docNavVisible = computed<NavItem[]>(() =>
    tieneAcceso('manual')
        ? [{ title: 'Manual de usuario', href: manual.index.url(), icon: BookOpen }]
        : [],
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="'/dashboard'">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavVisible" />
            <NavMain v-if="adminNavVisible.length" label="Configuración" :items="adminNavVisible" />
            <NavMain v-if="docNavVisible.length" label="Documentación" :items="docNavVisible" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
