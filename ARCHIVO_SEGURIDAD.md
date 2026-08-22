# Informe de Auditoría de Seguridad — `nominas-sm`

**Fecha**: 2026-08-20
**Alcance**: Auditoría de seguridad (OWASP Top 10) del sistema de nóminas `nominas-sm`.
**Stack**: Laravel 13.17 · PHP 8.4.24 · Fortify (2FA + passkeys) · Inertia 3 / Vue 3 · MariaDB · Sesiones en BD.

---

## 1. Resumen ejecutivo

Auditoría completada sobre **los 9 módulos** del sistema (M1–M9). Se remedió **1 hallazgo crítico** (escalada de privilegios vía registro público) y una serie de hallazgos de severidad media/alta. Todos los cambios pasaron verificaciones de `pint`, `phpstan` (0 errores en archivos tocados) y `npm run build`.

| Módulo | Área | Estado |
|---|---|---|
| M1 | Configuración / Infraestructura | ✅ Auditado y endurecido |
| M2 | Frontend (Vue/Inertia) | ✅ Auditado y endurecido |
| M3 | Manejo de archivos y subidas | ✅ Auditado y endurecido |
| M4 | Capa de datos / ORM | ✅ Auditado y endurecido |
| M5 | Autenticación | ✅ Auditado y endurecido |
| M6 | Autorización | ✅ **Crítico remediado** |
| M7 | RH / Datos maestros | ✅ Auditado y endurecido |
| M8 | Financieros | ✅ Auditado |
| M9 | Endpoint público con token | ✅ Auditado y endurecido |

---

## 2. Hallazgo crítico remediado (M6)

### Registro público escalaba a super administrador

**Cadena de explotación (sin autenticación):**

1. `Features::registration()` habilitado en `config/fortify.php` → rutas `GET|POST /register` activas.
2. `app/Actions/Fortify/CreateNewUser.php` creaba el usuario **sin definir `rol`**.
3. La migración `2026_06_27_000001_add_rol_to_users_table` tenía `->default('admin')`.
4. Por tanto, cualquier `POST /register` creaba un usuario con `rol='admin'`.
5. `User::tienePermiso()` devuelve `true` para `admin` → acceso total a todos los módulos y al grupo `es_admin` (gestión de usuarios, parámetros, marca).

**Agravante**: el modelo `User` **no implementaba `MustVerifyEmail`** (comentado), por lo que el middleware `verified` era un *no-op* y el administrador creado nacía **activo y operativo** sin verificar correo.

**Impacto**: un atacante solo necesitaba `email + password` para obtener control total del sistema de nóminas.

**Acciones de remediación:**
- `Features::registration()` deshabilitado en `config/fortify.php` (con comentario explicativo).
- `Fortify::registerView` eliminado y página `resources/js/pages/auth/Register.vue` borrada.
- Nueva migración `2026_08_20_000001_change_rol_default_to_capturista_in_users_table.php` → cambia el default de `'admin'` a `'capturista'` (defensa en profundidad, aplicada sobre la BD existente).
- `rol` y `permisos` sacados del `$fillable` de `User` (User.php:32), asignados explícitamente vía `forceFill` en `UsuarioController`.

**Verificación**: ruta `/register` → **404** (comprobado en runtime); lista de usuarios actual en BD sin admins anómalos (solo el seed `admin@example.com`).

---

## 3. Hallazgos y correcciones por módulo

### M1 — Configuración / Infraestructura

**Aplicado** (`config/security.php` nuevo, `SecurityHeaders.php` nuevo):

- Cabeceras de seguridad vía middleware del grupo `web`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (cámara, micrófono, geolocalización denegados).
- **HSTS** configurable (`max_age`, `include_subdomains`, `preload`), emitido solo sobre requests HTTPS.
- **CSP** configurable y deshabilitado por defecto (`SECURITY_CSP_ENABLED`).
- **Forzar HTTPS** en producción + `URL::forceScheme('https')` cuando `security.force_https`.
- **`APP_DEBUG` forzado a `false` en producción** (con log de advertencia si se detectó activo).
- `TRUSTED_PROXIES` movido a `AppServiceProvider::boot()` vía `TrustProxies::at(config('security.trusted_proxies'))` (no era resolvable en `withMiddleware`).
- `config/session.php`: cookie `secure` por defecto en producción.
- `.env.example` documentado con todas las directivas `SECURITY_*` / `SESSION_*`.

**Verificación**: cabeceras confirmadas con `curl -sI` sobre `/login`; `config:cache` y `route:list` operativos.

### M2 — Frontend (Vue/Inertia)

- **XSS por `marked.parse()`** en `resources/js/pages/manual/Index.vue:77` → sanitizado con **DOMPurify** (`^3.14`).
- `v-html` del dashboard (SVG generado server-side) y QR de 2FA sin riesgo.
- **`npm audit fix`**: de 6 vulnerabilidades *high* a **0**.
- Verificación: `vue-tsc --noEmit`, `npm run build`, `eslint` OK.

### M3 — Manejo de archivos y subidas

**Hallazgo crítico**: documentos sensibles (INE, CURP, NSS, placas, pólizas, evidencias) se servían desde `public/storage` **sin autenticación**, y los logos permitían SVG (riesgo de XSS).

**Aplicado:**

- Nuevo disco privado **`documentos`** (`config/filesystems.php`), fuera del webroot.
- **`ArchivoController`** (nuevo): sirve archivos solo con `auth` + `verified` + permiso según tipo (`colaboradores`, `transportes`, `registro-asistencia`/`validacion`). Bloquea *path traversal* (`..`, `..%2F`, rutas absolutas → 404), **SVG → 403**, añade `Cache-Control: private, no-store` y `X-Content-Type-Options: nosniff`.
- **`App\Support\Documentos::url()`** (nuevo): genera URLs `archivos/{tipo}/{path}`.
- Ruta `GET archivos/{tipo}/{path}` en `routes/web.php` con `whereIn`/`where('path','.*')`.
- 7 controladores migrados al disco `documentos`: ColaboradorPerfil, TransporteUnidad, AsistenciaPublica, RegistroAsistencia, Validacion, Nomina, Evento.
- `MarcaController`: logos restringidos a `mimes:jpg,jpeg,png,webp` (sin SVG).
- Comando **`archivos:migrar`** (nuevo): mueve los archivos de `public` a `documentos`. **Ejecutado: 38 archivos migrados** (se conservaron 3 logos de branding en `public`).

**Verificación**: smoke tests HTTP (sin auth → 302; sin permiso → 403; traversal/tipo inválido/inexistente → 404; SVG → 403; con permiso → binario correcto).

### M4 — Capa de datos / ORM

**Aplicado:**

- `token` **fuera del `$fillable`** de `Colaborador` (es la clave del endpoint público — riesgo de token-fixation por mass-assignment). `regenerarToken()` ahora usa `forceFill()->save()`; el hook `creating` asigna el UUID directamente.
- Guardas `if ($path = ...->store(...))` en guardados de archivos (maneja el retorno `string|false` de `store()`).

**Verificado seguro (sin cambios):**
- `HistoricoNomina::$fillable` incluye campos "congelables" (`estado`, `total_final`, `desglose`, `fecha_calculo`), pero `NominaController::guardar` los computa 100% server-side vía `NominaCalculator`; solo `comentario` entra por request validado.
- `PrestamoController`, `AnticipController`, `ViaticoController`, `JornadaController`: validación `exists` condicionada, `DB::transaction`, transiciones de estado con cheques (`PAGADA`/`historico_nomina_id`).
- `ParametroSistema::set`: solo se invoca desde `MarcaController` con constantes hardcodeadas (`Branding::CLAVE_*`).

### M5 — Autenticación

**Aplicado:**

- **Política de contraseñas inconsistente**: `UsuarioController` (alta/edición de usuarios) usaba `min:8`, mientras Fortify usaba `Password::defaults()` (en producción: 12+ con mayúsculas, números, símbolos y `uncompromised`). Ahora también usa `Password::defaults()`.
- **`POST /forgot-password` sin rate limit** → sobrescrito en `routes/web.php` con `throttle:6,1` (mitiga enumeración de cuentas y spam de correo), conservando el controlador de Fortify.

**Verificado correcto (sin cambios):**
- Rate limiting de `login` (5/min por email+IP), `two-factor` (5/min), `passkeys` (10/min) y verificación de email (`6,1`).
- Sesiones en **BD** (revocables), cookie `http_only`, `same_site:lax`, `secure` en producción, `DB::prohibitDestructiveCommands` en producción.

### M6 — Autorización

Ver [sección 2 — Hallazgo crítico](#2-hallazgo-crítico-remediado-m6).

Adicional: `UsuarioController::store/update` ya validaban `rol` contra `in:supervisor,capturista` y `permisos.*` contra la whitelist `MODULOS`; `EsAdmin` y `VerPermiso` correctos (403).

### M7 — RH / Datos maestros

**Aplicado:**

- **`TransporteController::guardar`**: `tarifas` solo validaba `'required|array'` — los valores pasaban por `is_numeric()` **sin rango** (aceptaba tarifas negativas o enormes). Añadida validación `'tarifas.*.*' => ['nullable','numeric','min:0']`.

**Verificado seguro (sin cambios):**
- `EventoController`: `Telefono` rule robusto, `tamano` whitelist, `unique`, cotizaciones/requisitos computados server-side.
- `TransporteUnidadController`: whitelist de campos de documento en `eliminarDocumento`, `mimes` restrictivos, disco privado.
- `AsignacionController`: `exists` + sync.
- `RegistroAsistenciaController`: integridad — bloquea borrado si la jornada está validada o en nómina; elimina la evidencia física.

**Nota de integridad (no explotable sin permiso):** `TransporteController::guardar` borra todos los vehículos/distancias y los recrea; como `transporte_vehiculo_id` es `nullOnDelete()`, al guardar la matriz las unidades de flotilla pierden su categoría de vehículo. Riesgo de integridad de datos de diseño, no de acceso.

### M8 — Financieros

**Verificado seguro (sin cambios):**
- `ServicioProfesionalController`: `monto min:0`, `tipo in:`, `evento_id exists`.
- Casts monetarios `decimal:2` en Viatico, Anticipo, Prestamo, PrestamoCuota, HistoricoNomina, ServicioProfesional.
- Transiciones de estado con cheques de integridad (pagar/revertir/aplazar/distribuir cuotas dentro de `DB::transaction`).

### M9 — Endpoint público `asistencia/{token}`

**Aplicado:**

- **Rate limiting** en rutas públicas: `GET asistencia/{token}` → `throttle:30,1`; `POST asistencia/{token}` → `throttle:20,1` (mitiga spam de registros y abuso de almacenamiento con un token válido).

**Verificado seguro (sin cambios):**
- **Token UUID 122-bit** (`string(36)` único) → brute-force inviable; `firstOrFail` → 404 sin enumeración práctica.
- Mass-assignment limpio en `RegistroNormalizado`; `colaborador_id` forzado server-side.
- Validación estricta (`tipo_actividad` restringido por rol, `required_if`, `file|image|max:5120`).
- Evidencia a disco privado `documentos` (M3).
- CSRF presente (grupo `web`).

**Nota (Bajo, intencional):** `show()` expone placas de unidades a quien posea el token — requisito funcional del formulario; el token es secreto del colaborador.

---

## 4. Archivos modificados / creados

**Nuevos:**
- `config/security.php`
- `app/Http/Middleware/SecurityHeaders.php`
- `app/Http/Controllers/ArchivoController.php`
- `app/Support/Documentos.php`
- `app/Console/Commands/MigrarDocumentos.php`
- `database/migrations/2026_08_20_000001_change_rol_default_to_capturista_in_users_table.php`

**Modificados (principales):**
- `config/fortify.php`, `config/filesystems.php`, `config/session.php`, `config/security.php`
- `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`, `app/Providers/FortifyServiceProvider.php`
- `routes/web.php`
- `app/Http/Controllers/`: `UsuarioController`, `ColaboradorController`, `ColaboradorPerfilController`, `TransporteUnidadController`, `TransporteController`, `AsistenciaPublicaController`, `RegistroAsistenciaController`, `ValidacionController`, `NominaController`, `EventoController`, `MarcaController`
- `app/Models/User.php`, `app/Models/Colaborador.php`
- `resources/js/pages/manual/Index.vue`
- · `resources/js/pages/auth/Register.vue` **eliminado**
- `.env.example`

---

## 5. Verificaciones ejecutadas

- `php artisan route:list` / `route:list -v` (rutas `/register`→sin rutas; throttles presentes en forgot-password y `asistencia/{token}`).
- `./vendor/bin/pint` → PASS en todos los archivos modificados.
- `./vendor/bin/phpstan analyse` → **0 errores** en los archivos auditados/modificados.
- `npm run build`, `vue-tsc --noEmit`, `eslint` → OK.
- `npm audit` → **0 vulnerabilidades**.
- Smoke tests HTTP: cabeceras de seguridad, auth de archivos (302/403/404), SVG (403), traversal (404), /register (404).
- `php artisan migrate` → aplicada la migración de cambio de default.
- Smoke tests funcionales (`tinker`): `forceFill` de `rol`/`permisos` + `tienePermiso()` con whitelist.

---

## 6. Pendientes / notas de menor riesgo

- **187 errores de phpstan nivel 7 preexistentes** en el resto del código (tipado genérico de Eloquent) — ajenos a esta auditoría. Recomendado `php artisan` baseline o corrección progresiva.
- **Suite de tests rota por entorno**: `could not find driver (Connection: sqlite...)` — falta `pdo_sqlite` en PHP 8.4; la app usa MariaDB. Preexistente, sin relación con los cambios. Corregir con `apt install php8.4-sqlite3` o ajustar `phpunit.xml`.
- **Integridad de flotilla** (M7): al guardar la matriz de tarifas, las unidades pierden su categoría de vehículo (`nullOnDelete`). Considerar validación que impida borrar vehículos en uso o re-vincular unidades.
- **`rol`/`permisos` de `User`**: ya fuera de `$fillable`; cualquier futura creación de usuario debe usar `forceFill` explícito (aplicado en `UsuarioController`).
- **M2**: los SVG/HTML generados server-side se consideran seguros tras la sanitización; revisar cualquier nueva inyección de `v-html` en futuros screens.

---

## 7. Conclusión

La auditoría eliminó la vulnerabilidad crítica de **escalada a administrador vía registro público** y reforzó la superficie de ataque general: protección de archivos sensibles, hardening de infraestructura (headers, HTTPS, HSTS/CSP), sanitización de XSS, política de contraseñas, rate limiting de autenticación y de endpoints públicos, y endurecimiento del modelo de datos contra mass-assignment. El sistema queda en un estado de seguridad considerablemente más sólido, con todas las correcciones verificadas.

Para producción, se recomienda: configurar SMTP real, habilitar `SECURITY_FORCE_HTTPS`/`HSTS`/CSP según la infraestructura (proxy/reverse proxy detrás del cual corregir `TRUSTED_PROXIES`), ejecutar `php artisan config:cache`/`optimize` tras el despliegue, y abordar los ítems de la sección 6.
