# Especificación de Migración — SM Nómina

> Documento de referencia completo para que un agente de IA (o equipo) reconstruya este sistema en otra tecnología (Laravel u otro framework). Incluye modelo de datos, reglas de negocio, flujos de usuario, lógica de cálculo y acciones que actualmente se realizan directamente en Google Sheets y deben migrarse a la interfaz.

---

## Tabla de contenidos

1. [Contexto del negocio](#1-contexto-del-negocio)
2. [Entidades y modelo de datos](#2-entidades-y-modelo-de-datos)
3. [Tipos de personal y principio rector](#3-tipos-de-personal-y-principio-rector)
4. [Catálogos y configuración](#4-catálogos-y-configuración)
5. [Registro de asistencia (origen de datos)](#5-registro-de-asistencia-origen-de-datos)
6. [Procesamiento de jornadas](#6-procesamiento-de-jornadas)
7. [Reglas de negocio — Cálculo de nómina](#7-reglas-de-negocio--cálculo-de-nómina)
8. [Flujos de usuario por módulo](#8-flujos-de-usuario-por-módulo)
9. [Anticipos](#9-anticipos)
10. [Servicios Profesionales](#10-servicios-profesionales)
11. [Historial de nóminas](#11-historial-de-nóminas)
12. [Acciones que hoy viven en Sheets y deben migrar a la UI](#12-acciones-que-hoy-viven-en-sheets-y-deben-migrar-a-la-ui)
13. [Estados y transiciones](#13-estados-y-transiciones)
14. [Validaciones de integridad](#14-validaciones-de-integridad)
15. [Permisos y gobernanza](#15-permisos-y-gobernanza)
16. [Referencia de valores enumerados](#16-referencia-de-valores-enumerados)

---

## 1. Contexto del negocio

**SM Producciones** es una empresa de producción de eventos en vivo. Gestiona el pago de tres tipos de personal:

- **Colaborador Base**: equipo interno estable (trabaja semana corrida en bodega + extras en eventos).
- **Freelance**: personal externo contratado por evento (montaje, show, desmontaje).
- **Conductor**: choferes que se pagan por ruta/distancia, no por hora ni por evento.

El ciclo de pago es:
- Base → **semanal** (lunes a sábado, corte el sábado).
- Freelance → **por evento** (puede pagarse en etapas: 25/50/25 %).
- Conductor → **por periodo acordado** (variable).

---

## 2. Entidades y modelo de datos

### 2.1 COLABORADORES

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | string | Identificador único del colaborador |
| `nombre` | string | Nombre(s) |
| `apellidos` | string | Apellidos |
| `tipo` | enum | `COLABORADOR BASE` \| `FREELANCE` \| `CONDUCTOR` |
| `area` | string | Área de trabajo (aplica a BASE y FREELANCE) |
| `sueldo_diario` | decimal | Solo aplica a `COLABORADOR BASE` |
| `bono_por_evento` | decimal | Extra individual por día de evento. Solo `COLABORADOR BASE` |
| `extra_dia_adicional` | decimal | Monto por cada día extra sobre el paquete. Solo `FREELANCE` |

**Reglas de edición por tipo:**

| Campo | BASE | FREELANCE | CONDUCTOR |
|-------|:----:|:---------:|:---------:|
| área | ✓ | ✓ | — |
| sueldo_diario | ✓ | — | — |
| bono_por_evento | ✓ | — | — |
| extra_dia_adicional | — | ✓ | — |

> Los conductores no tienen campos editables desde la interfaz (sus tarifas viven en la tabla TRANSPORTES).

### 2.2 EVENTOS

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `nombre` | string | Nombre único del evento (clave natural) |
| `lugar` | string | Ubicación |
| `tamano` | enum | `CHICO` \| `MEDIANO` \| `GRANDE` |
| `pago_por_evento_completo` | decimal | Monto base que se paga a freelances por el evento completo |

**Lógica del tamaño:**
- `CHICO` → no genera bono de evento para base; se paga como día de bodega.
- `MEDIANO` / `GRANDE` → genera bono de evento según el `bono_por_evento` individual del colaborador base.

**Pago por defecto al crear** (hardcoded en frontend actual, debe ser configurable en la nueva app):

| Tamaño | Monto default |
|--------|--------------|
| CHICO | $1,500 |
| MEDIANO | $2,500 |
| GRANDE | $3,000 |

> En la migración, este default debe ser configurable desde la UI (parámetro de sistema), no hardcoded.

### 2.3 ASIGNACIONES

Relación muchos-a-muchos entre eventos y colaboradores (principalmente para freelances).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `evento` | string FK | Nombre del evento |
| `colaborador` | string FK | Nombre del colaborador |

> El sistema identifica eventos en los registros de asistencia fuzzy-matching contra esta tabla y contra EVENTOS.

### 2.4 REGISTROS_NORMALIZADOS

Tabla de origen generada automáticamente desde el **Google Form de asistencia**. En la migración, esta tabla equivale al input que llega desde el mecanismo de registro (app móvil, QR, formulario web, etc.).

Columnas relevantes (índices del AppScript actual):

| Índice | Campo | Descripción |
|--------|-------|-------------|
| 5 | `id_colaborador` | ID del colaborador |
| 6 | `tipo` | Tipo de colaborador |
| 7 | `tipo_actividad` | `Bodega` \| `Evento` \| `Transporte` |
| 8 | `actividad` | Descripción de la actividad |
| 10 | `evento_raw` | Nombre del evento como lo escribió el usuario |
| 11 | `etapa` | Etapa del evento (Montaje, Show, Desmontaje) |
| 13 | `transporte` | Tipo de vehículo |
| 14 | `distancia` | Rango de distancia |
| 15 | `origen` | Origen del viaje |
| 16 | `destino` | Destino del viaje |
| 18-20 | `extras_*` | Extras por tipo de actividad |
| 21-26 | `evidencia_*`, `comentarios_*` | Evidencia fotográfica y comentarios por tipo |
| 28 | `fecha` | Fecha del registro |
| 29 | `hora` | Hora del registro |

### 2.5 JORNADAS_CONSOLIDADAS

Tabla procesada (generada por `generarJornadas()`). Agrupa múltiples registros del mismo colaborador en el mismo día en una sola fila.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `fecha` | date | Fecha de la jornada |
| `id` | string FK | ID del colaborador |
| `tipo` | enum | Tipo del colaborador |
| `entrada` | time | Primera hora registrada del día |
| `salida` | time | Última hora registrada del día |
| `actividades` | string | Lista de tipos de actividad del día (ej. "Bodega, Evento") |
| `detalle` | text | Descripción detallada (multiline, una línea por actividad) |
| `extras` | string | Extras registrados |
| `evidencias` | text | URLs de fotos de evidencia |
| `comentarios` | text | Comentarios adicionales |
| `validado` | boolean | `TRUE` / `FALSE` — marcado por el administrador |
| `tipoPago` | enum | Clasificación de pago asignada por el admin |

**Formato del campo `detalle`** (texto multiline):
```
Bodega: Carga de equipo
Evento: Nombre del Evento Oficial - Montaje
Transporte: Manejó un(a) Urvan 100-200 km, de Origen a Destino
```

### 2.6 ANTICIPOS

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_colaborador` | string FK | ID del colaborador |
| `nombre` | string | Nombre |
| `apellidos` | string | Apellidos |
| `tipo` | string | Tipo del colaborador |
| `concepto` | string | Descripción. Para freelance: debe coincidir con el nombre del evento |
| `monto` | decimal | Monto entregado |
| `fecha` | date | Fecha de entrega |
| `entregado_por` | string | Quién entregó el anticipo |

### 2.7 HISTORICO_NOMINA

Registro permanente de todas las nóminas calculadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_colaborador` | string | ID del colaborador |
| `nombre` | string | Nombre del colaborador |
| `tipo_colaborador` | enum | `COLABORADOR BASE` \| `FREELANCE` \| `CONDUCTOR` |
| `periodo_inicio` | date | Inicio del periodo (base/conductor); vacío para freelance |
| `periodo_fin` | date | Fin del periodo; vacío para freelance |
| `dias` | decimal | Días trabajados (puede ser decimal por traslapes); rutas para conductor |
| `sueldo_diario` | decimal | Sueldo diario (base); pago evento (freelance); 0 (conductor) |
| `total_base` | decimal | Días × sueldo_diario; total_rutas para conductor |
| `bonos_evento` | decimal | Suma de bonos_evento + bono_septimo_dia |
| `compensaciones` | decimal | Compensación manual del admin |
| `anticipos` | decimal | Total de anticipos descontados |
| `total_final` | decimal | Monto neto a pagar |
| `estado` | enum | `PENDIENTE` \| `PAGADO` |
| `fecha_calculo` | datetime | Cuándo se calculó/guardó |
| `evento` | string | Nombre del evento (solo freelance) |

### 2.8 TRANSPORTES

Tabla de tarifas para conductores. Estructura matricial:

| TIPO VEHÍCULO | Menos de 300m | 300-1km | 1-5km | ... | STANDBY |
|--------------|:---:|:---:|:---:|:---:|:---:|
| Urvan | 350 | 500 | 700 | ... | 200 |
| Camión | 500 | 700 | 900 | ... | 300 |
| Tráiler | 800 | 1100 | 1500 | ... | 400 |

- **Primera columna**: nombre del tipo de vehículo.
- **Demás columnas**: rangos de distancia (headers) con la tarifa en cada celda.
- La columna `STANDBY` es opcional pero reconocida automáticamente.
- La tabla es **completamente dinámica**: el admin puede agregar/eliminar vehículos (filas) y distancias (columnas) desde la UI.

### 2.9 SERVICIOS_PROFESIONALES

Registro externo, no forma parte de la nómina interna.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `nombre` | string | Nombre del profesional |
| `apellidos` | string | Apellidos |
| `tipo` | enum | `RIGGER` \| `OPERADOR_AUDIO` \| `OPERADOR_VIDEO` \| `OPERADOR_LUZ` \| `OTRO` |
| `evento` | string | Nombre del evento |
| `concepto` | string | Descripción del servicio |
| `monto` | decimal | Monto pagado |
| `fecha` | date | Fecha del servicio |
| `autoriza` | string | Quién autorizó el pago |

---

## 3. Tipos de personal y principio rector

### 3.1 Jerarquía de ventaja

El personal base **siempre** tiene ventaja sobre el externo. Nunca un freelance debe ganar más que un base equivalente por el mismo tipo de aportación, salvo que sea un **servicio profesional** claramente definido y separado de nómina.

### 3.2 Colaborador Base

- Trabaja de **lunes a sábado**.
- Tiene **sueldo diario fijo** (condicionado a la asistencia).
- Tiene un **bono individual por cada día de evento** pagable (MEDIANO o GRANDE).
- Bodega, carga y descarga **no generan bono de evento**, solo cuentan como jornada base.
- "Evento pagable" = **montaje, show o desmontaje** en evento no CHICO.
- El transporte **no se paga como extra** para el base (ya tiene sueldo base).
- Si un base solo hace eventos sin trabajar bodega durante la semana, **no cobra semana completa**: solo los días reales trabajados, sin bono de domingo.

**Sub-categorías** (definen el `bono_por_evento` individual):
- Encargado de área → bono más alto
- Técnico → bono medio
- Stagehand SM → bono menor

### 3.3 Freelance

- Se paga **por evento**, no por semana.
- Modelo **"burrito"** (stagehand freelance): paquete fijo por el rango del evento (típicamente cubre 1–5 días). Si el evento se extiende, se cobra un extra por cada día adicional (`extra_dia_adicional` × días adicionales).
- Rigger: honorario por montaje/desmontaje + transporte + viáticos si aplica.
- Operador especializado (audio/video/luz): servicio profesional separado, fuera de nómina.
- Un freelance **no tiene** sueldo diario ni bono de séptimo día.

### 3.4 Conductor

- Pago por **bloques de 24 horas (día de ruta)**, no por viaje suelto.
- Tarifa variable según **tipo de vehículo** + **rango de distancia** (tabla TRANSPORTES).
- **Standby**: día sin manejar, con tarifa especial (columna STANDBY en TRANSPORTES).
- En doble evento o traslape de 24 h: el pago se **limita** (40–50 % según criterio del admin).
- No tiene sueldo diario ni bono de evento.

---

## 4. Catálogos y configuración

### 4.1 Gestión de colaboradores

- **Agregar / eliminar**: actualmente se hace directo en la hoja COLABORADORES. **En la migración: debe hacerse desde la UI** con formulario de alta/baja con los campos del modelo.
- **Editar**: desde la UI, con campos habilitados según el tipo (ver tabla 2.1).

### 4.2 Gestión de eventos

Desde la UI:
1. Listar eventos (nombre, lugar, tamaño, pago).
2. Crear evento (nombre, lugar, tamaño). El pago por defecto se aplica según el tamaño (configurable).
3. Editar el campo `pago_por_evento_completo` inline en la tabla.
4. Asignar/quitar colaboradores a un evento (modal de asignación).

### 4.3 Gestión de tabla TRANSPORTES

Desde la UI (modo edición):
- Ver tabla completa con vehículos × distancias.
- Activar modo edición → todos los campos se vuelven inputs.
- Agregar columna (nueva distancia): se inserta al final.
- Agregar fila (nuevo vehículo): se inserta al final.
- Guardar (reemplaza toda la tabla atómicamente con rollback si falla).
- Gestionar → eliminar columna (distancia) o fila (vehículo) por nombre.
- Cancelar → descarta cambios.

**Restricciones:**
- La primera columna siempre es el nombre del vehículo (no editable como distancia).
- Las celdas de tarifas deben ser números ≥ 0.
- El header `STANDBY` tiene significado especial: es la tarifa para días sin ruta identificada.

---

## 5. Registro de asistencia (origen de datos)

En la versión actual, el registro viene de un **Google Form** que produce REGISTROS_NORMALIZADOS via fórmulas de Sheets.

**En la migración**: debe existir un mecanismo equivalente para que el personal registre su asistencia. Opciones:
- Formulario web interno (mismo sistema).
- App móvil.
- Integración con otro sistema.

El sistema debe recibir, por cada entrada de asistencia:
- ID del colaborador, tipo, fecha, hora.
- Tipo de actividad (`Bodega` / `Evento` / `Transporte`).
- Para Evento: nombre del evento (texto libre, se normaliza después), etapa.
- Para Transporte: tipo de vehículo, distancia, origen, destino.
- Extras, evidencia (URL de foto), comentarios.

---

## 6. Procesamiento de jornadas

### 6.1 Función `generarJornadas()` (equivalente en la migración: job/comando)

> **ADVERTENCIA CRÍTICA PARA LA MIGRACIÓN**: la función actual hace `hoja.clear()` antes de escribir (backend.js:524). Esto **borra completamente** JORNADAS_CONSOLIDADAS incluyendo los campos `validado` y `tipoPago` que el admin haya editado manualmente. En el sistema actual, re-ejecutar "Generar Jornadas" después de que el admin haya validado jornadas **destruye esas validaciones sin advertencia**. La arquitectura nueva **debe resolverlo**: una opción es hacer upsert por `id + fecha` preservando `validado` y `tipoPago` si ya existen, y solo agregar filas nuevas; otra es tener dos tablas separadas (registros crudos vs. validaciones).

**Input**: tabla REGISTROS_NORMALIZADOS.
**Output**: tabla JORNADAS_CONSOLIDADAS (regenerada completamente).

**Algoritmo**:

1. Cargar todos los eventos oficiales desde EVENTOS.
2. Para cada registro en REGISTROS_NORMALIZADOS (agrupando por `id + fecha`):
   - **Entrada/salida** (solo BASE): primera hora del día = entrada, última = salida.
   - **Actividades**: agregar al conjunto del día (`Bodega`, `Evento`, `Transporte`).
   - **Detalles** (texto multiline):
     - Si `Bodega`: `"Bodega: {actividad}"`.
     - Si `Evento`: resolver el nombre fuzzy-match contra EVENTOS → `"Evento: {nombre_oficial} - {etapa}"`. Si no se reconoce: `"Evento: NO IDENTIFICADO - {etapa}"` + error.
     - Si `Transporte`: `"Transporte: Manejó un(a) {vehiculo} {distancia}, de {origen} a {destino}"`.
   - **Extras, evidencias, comentarios**: agregar al conjunto del día.
   - **Errores**:
     - Freelance con actividad ≠ Evento → `"Freelance actividad inválida"`.
     - Conductor con actividad ≠ Transporte → `"Conductor actividad inválida"`.
     - BASE con Transporte → `"Base en transporte"`.
3. Escribir una fila por `id + fecha` con los sets consolidados. Campo `validado` = `FALSE`.

**Normalización fuzzy para eventos**:
```
normalizar(texto) → minúsculas, sin tildes, trim
match si: texto_usuario.includes(evento_oficial) OR evento_oficial.includes(texto_usuario)
```

**Cuándo se ejecuta**: actualmente hay un trigger automático en Sheets. En la migración: puede ser un job programado o ejecutarse bajo demanda desde la UI con un botón "Regenerar jornadas".

### 6.2 Clasificación automática de `tipoPago`

Tras generar jornadas, el sistema propone automáticamente el `tipoPago`.

**Comportamiento de guardado en tiempo real (crítico para la migración):**
- El checkbox `validado` y el dropdown `tipoPago` se persisten **en tiempo real al cambio**, sin ningún botón "Guardar jornada".
- Cada cambio de `validado` dispara `actualizarValidacionJornada(id, fecha, valor)` inmediatamente.
- Cada cambio de `tipoPago` dispara `actualizarTipoPagoJornada(id, fecha, tipoPago)` inmediatamente.
- Ambas son llamadas independientes al servidor, no un guardado en batch.
- En la migración: implementar como endpoints PATCH individuales o websocket; no usar formulario con submit.

| Condición en `detalle` | `tipoPago` propuesto |
|------------------------|----------------------|
| Contiene `"Bodega"` (sin evento) | `JORNADA_COMPLETA` |
| Contiene `"Evento:"` con evento CHICO | `JORNADA_COMPLETA` |
| Contiene `"Evento:"` con evento MEDIANO/GRANDE | `JORNADA_COMPLETA + EVENTO` |
| Contiene `"Evento:"` pero evento NO IDENTIFICADO | `ERROR_EVENTO` |
| Sin datos | `SIN_PAGO` |

**Importante**: esto es solo una **propuesta**. El administrador puede cambiar el `tipoPago` manualmente en la pantalla de validación antes de calcular.

---

## 7. Reglas de negocio — Cálculo de nómina

### 7.1 Nómina Base (semanal)

**Solo se incluyen jornadas** con `validado = TRUE` y `tipoPago ≠ 'SIN_PAGO'` y `tipoPago ≠ 'ERROR_EVENTO'`.

#### Fracción de día por `tipoPago`

| tipoPago | Fracción |
|----------|:--------:|
| `JORNADA_COMPLETA` | 1.0 |
| `JORNADA_COMPLETA + EVENTO` | 1.0 |
| `TRASLAPE_40` | 0.4 |
| `TRASLAPE_50` | 0.5 |
| `SIN_PAGO` | 0.0 (excluida) |
| `ERROR_EVENTO` | 0.0 (excluida) |

#### Cálculo paso a paso

```
1. días_totales = Σ fracción(tipoPago) por cada jornada
2. total_base = sueldo_diario × días_totales

3. Por cada jornada con tipoPago = 'JORNADA_COMPLETA + EVENTO':
   a. Extraer nombre del evento del campo detalle
   b. Buscar el evento en EVENTOS para saber su tamaño
   c. Si tamaño ≠ 'CHICO':
      bonos_evento += bono_por_evento × fracción(tipoPago)

4. bono_septimo_dia:
   - Agrupar jornadas validadas por semana (lunes a sábado)
   - Si la semana contiene jornadas los 6 días (lunes=1, martes=2, ..., sábado=6):
     bono_septimo_dia += sueldo_diario (equivale a pagar el domingo)
   - Si falta algún día: bono_septimo_dia = 0 para esa semana

5. anticipos = Σ monto de anticipos del colaborador
   cuya fecha esté dentro del rango [fechaInicio, fechaFin]

6. compensacion_manual = monto ingresado por el admin (puede ser negativo)

7. total_final = total_base + bonos_evento + bono_septimo_dia
                + compensacion_manual - anticipos
```

#### Regla de traslapes (dos eventos el mismo día)

Si un colaborador tiene 2 jornadas el mismo día:
- El evento **principal** se marca `JORNADA_COMPLETA + EVENTO` (100%).
- El evento **secundario** se marca `TRASLAPE_40` (40%) o `TRASLAPE_50` (50%).
- Nunca se pagan como dos jornadas completas.
- El admin decide qué porcentaje aplica según cuánto aportó realmente en cada uno.

#### Evento CHICO para personal base

- El día se registra en JORNADAS_CONSOLIDADAS como evento (para el historial).
- Pero para el cálculo, `tipoPago = 'JORNADA_COMPLETA'` (sin bono).
- El admin puede decidir asignar `JORNADA_COMPLETA + EVENTO` manualmente, pero el sistema propone `JORNADA_COMPLETA`.

### 7.2 Nómina Freelance (por evento)

```
1. pago_base = pago_por_evento_completo × porcentaje(modo_pago)

   Modos de pago y porcentajes:
   - completo → 100%
   - etapa1   → 25%
   - etapa2   → 50%
   - etapa3   → 25%

2. pago_extras = dias_adicionales × extra_dia_adicional

3. anticipos = Σ monto de anticipos del colaborador
   cuyo concepto contenga (fuzzy-match) el nombre del evento
   (anticipos con concepto vacío o de otro evento NO se descuentan automáticamente)

4. compensacion_manual = monto ingresado por el admin

5. total_final = pago_base + pago_extras + compensacion_manual - anticipos
```

**Notas:**
- Los días adicionales se suman **encima** del modo de pago seleccionado.
- Un anticipo con concepto vacío requiere ajuste manual (compensación negativa).

### 7.3 Nómina Conductor (por periodo)

```
1. Por cada jornada validada (validado = TRUE):
   a. Extraer vehículo y distancia del campo detalle
      Patrón: "Transporte: Manejó un(a) {vehiculo} {distancia}, de {origen} a {destino}"
   b. Buscar tarifa en TRANSPORTES cruzando vehículo (fila) × distancia (columna)
   c. Si distancia no se reconoce → intentar columna STANDBY
   d. Si tampoco → tarifa = 0 (requiere compensación manual)

2. total_rutas = Σ tarifa por cada jornada validada

3. anticipos = Σ monto de anticipos del conductor
   cuya fecha esté dentro del rango [fechaInicio, fechaFin]

4. compensacion_manual = monto ingresado por el admin (incluye standby no detectados)

5. total_final = total_rutas + compensacion_manual - anticipos
```

**Búsqueda de tarifa en TRANSPORTES (algoritmo)**:

```
normDist(txt) → minúsculas, sin tildes, "k" → "km", normalizar espacios en "-"

1. Parsear detalle con regex: /Manejó un\(a\)\s+(.+?),\s*de\s+/i
   → "resto" = "{vehiculo} {distancia}"
2. Para cada distancia (columna) en TRANSPORTES:
   si normDist(resto).includes(normDist(distancia)):
     distancia_encontrada = nombre oficial de la columna
     vehiculo = resto[0 .. índice_de_distancia].trim()
3. Si distancia vacía → usar columna "STANDBY"
4. Buscar fila donde norm(nombre_vehiculo) == norm(vehiculo)
5. Retornar valor numérico en [fila, columna]
```

---

## 8. Flujos de usuario por módulo

### 8.0 Vista inicial al cargar la aplicación

La aplicación arranca directamente en **Panel Validación → pestaña Colaboradores Base** (sin pantalla de inicio ni dashboard). El admin ve el formulario de búsqueda por rango de fechas para nómina base.

La barra lateral tiene estas secciones (en orden):
1. **Panel Validación** (activo al inicio)
2. **Colaboradores**
3. **Eventos**
4. **Transportes**
5. **Anticipos**
6. **Servicios Prof.**
7. **Historial**

La vista **Asignación** (asignar colaboradores a un evento) NO aparece en la barra lateral: es una vista auxiliar accesible únicamente desde el botón "Ver" en la tabla de Eventos.

### 8.1 Módulo: Colaboradores

**Listar colaboradores**
1. Cargar tabla completa de COLABORADORES.
2. Para cada colaborador, mostrar también los eventos a los que está asignado (de ASIGNACIONES).
3. Mostrar badge de tipo coloreado (verde=base, amarillo=freelance, azul=conductor).
4. Conductores aparecen con opacidad reducida y botón de guardar deshabilitado.

**Editar colaborador**
1. Campos editables según tipo (ver tabla 2.1) se muestran como inputs.
2. El admin escribe el valor y hace clic en "Guardar".
3. Se actualizan únicamente los campos: `area`, `sueldo_diario`, `bono_por_evento`, `extra_dia_adicional`.

**Agregar colaborador** (actualmente en Sheets, debe migrar a UI)
1. Formulario con todos los campos del modelo.
2. Validar tipo antes de mostrar/ocultar campos relevantes.
3. Guardar en la base de datos.

**Eliminar colaborador** (actualmente en Sheets, debe migrar a UI)
1. Verificar que no tenga nóminas PENDIENTE antes de eliminar.
2. Confirmar con el admin.

**No existe búsqueda ni filtro** en la lista de colaboradores. Todos se muestran siempre, sin paginación. La migración puede agregar filtrado, pero no existía en el original.

**`COLABORADORES_DATA` se carga una sola vez al arrancar la app** y se reutiliza en múltiples vistas (historial, anticipo modal, selección de conductor). Si el admin agrega un colaborador durante la sesión, no se refleja en los dropdowns hasta recargar la página. En la migración, usar una fuente reactiva o recargar al navegar entre vistas.

### 8.2 Módulo: Eventos

**Listar eventos**
- Tabla con: Nombre, Lugar, Tamaño (badge coloreado), Pago, botón "Ver".

**Crear evento**
1. Modal con campos: Nombre, Lugar, Tamaño (dropdown: CHICO/MEDIANO/GRANDE).
2. El pago se asigna automáticamente según el tamaño. **Este cálculo ocurre en el cliente** antes de llamar al backend; el backend recibe el valor ya calculado y no aplica ningún default propio. En la migración, el backend debe aplicar el default él mismo para ser la fuente de verdad.
3. El admin puede editar el pago inline después de crear.
4. Al guardar con éxito: cierra el modal y recarga la tabla de eventos automáticamente.

**Ver / gestionar asignación de personal**
1. Clic en "Ver" → navega a la vista de asignación (no es un modal; es una vista separada que reemplaza la pantalla actual).
2. Muestra tabla de colaboradores asignados (nombre, apellidos, tipo).
3. Botón "+ Agregar" → despliega dropdown con colaboradores no asignados aún (filtrado localmente desde `COLABORADORES_DATA`).
4. Botón "Quitar" por fila → elimina la asignación.
5. **Cada cambio (agregar o quitar) dispara un guardado inmediato** que reemplaza todas las asignaciones del evento en un solo write, y luego recarga la tabla desde el servidor.
6. No hay botón "Guardar" ni "Cancelar" en esta vista — cada operación es atómica.

**Editar evento** (actualmente en Sheets, debe migrar a UI)
- Editar nombre, lugar, tamaño y pago directamente en la tabla.

**Eliminar evento** (actualmente en Sheets, debe migrar a UI)
- Verificar que no tenga nóminas PENDIENTE ni PAGADO asociadas.

### 8.3 Módulo: Validación de Nómina — Base

> **Nota de nomenclatura UI**: el Panel Validación tiene tres pestañas internas. En la UI real se llaman: **"Colaboradores Base"**, **"Eventos"** (para freelance) y **"Conductores"**. El spec usa "Freelance" como nombre del módulo pero la pestaña en la interfaz dice "Eventos".

**Paso 1: Buscar colaboradores con jornadas en el periodo**
1. El admin selecciona `fecha_inicio` y `fecha_fin` (normalmente lunes–sábado).
2. El sistema lista los colaboradores BASE con jornadas en ese rango.

**Paso 2: Seleccionar colaborador y ver sus jornadas**
1. Al seleccionar un colaborador, se cargan sus jornadas del rango.
2. Por cada jornada se muestra:
   - Fecha
   - Entrada / Salida
   - Actividades del día
   - Detalle (texto multiline)
   - Extras
   - Evidencia (URL/imagen)
   - Comentarios
   - Checkbox de validación (`validado`)
   - Selector de `tipoPago`

**Paso 3: El admin revisa y ajusta**
- Puede marcar/desmarcar el checkbox `validado`.
- Puede cambiar el `tipoPago` (dropdown con los 6 valores posibles).
- Cada cambio se guarda inmediatamente en JORNADAS_CONSOLIDADAS.

**Paso 4: Calcular nómina**

Si la nómina de ese colaborador + periodo ya está en estado `PAGADO`, el sistema **no muestra el desglose**: reemplaza el área de resultado con un mensaje de bloqueo que indica que la nómina ya fue pagada y redirige al historial. No hay forma de recalcular desde este panel.

Si no está pagada:
1. Botón "Calcular nómina" → llama al servidor, que devuelve el desglose.
2. Muestra desglose:
   - Días trabajados (puede ser decimal)
   - Total base
   - Bonos de evento
   - Bono 7° día
   - Compensación manual (input editable — **el total se recalcula localmente** al editar este campo, sin nueva llamada al servidor)
   - Anticipos (calculados automáticamente por el servidor)
   - **Total a pagar**
3. También muestra un resumen de contadores: "Por pagar / Pagado / Total registros" encima de la tabla de historial.

**Comportamiento del campo Compensación Manual:**
- El admin puede editar el campo en cualquier momento después de calcular.
- El total visible se actualiza en tiempo real en el cliente (recálculo local).
- El valor de compensación solo viaja al servidor en el momento de "Guardar nómina".
- **Riesgo**: si el admin edita la compensación y no guarda, el valor se pierde al navegar.

**Paso 5: Guardar**
- "Guardar nómina" → el frontend envía el resumen completo (incluyendo la compensación actual del input) al servidor como `resumenExterno`. El backend guarda ese resumen directamente sin recalcular.
- **DECISIÓN CRÍTICA DE MIGRACIÓN**: este diseño hace que el cliente sea la fuente de verdad al guardar. En la nueva app, el servidor debe recalcular al momento de guardar recibiendo solo los parámetros (colaborador, periodo, compensación), no el resumen precalculado.
- Si ya existe una nómina para ese colaborador + periodo con estado `PENDIENTE` → la sobreescribe.
- Si está `PAGADO` → muestra toast de error y aborta: `"Esta nómina ya fue pagada y no puede modificarse"`.

**Paso 6: Pagar**
- "Marcar como pagado" → muestra modal de confirmación → al confirmar, cambia estado a `PAGADO`.
- **Esta acción es irreversible**: una nómina `PAGADO` no puede recalcularse ni modificarse.
- Después de marcar como pagado: recarga la lista del historial automáticamente.

### 8.4 Módulo: Validación de Nómina — Freelance (pestaña "Eventos" en la UI)

**Paso 1: Listar eventos con freelances asignados**
- **Solo se muestran eventos cuyo estado NO sea `PAGADO`**. Un evento donde todos los freelances ya están pagados desaparece de esta lista. Para consultar eventos pagados, usar el Historial.
- Por cada freelance se muestra si tiene nómina guardada y en qué estado.
- Estado del evento: `SIN_FREELANCE` / `PENDIENTE` / `PAGADO` (según el estado de todas las nóminas).

**Paso 2: Seleccionar evento y freelance**
- El admin selecciona el evento y luego el freelance específico.

**Paso 3: Ver jornadas del freelance en ese evento**
- Se muestran las jornadas del freelance que contengan el nombre del evento (fuzzy-match en el campo `detalle`).
- Se muestra: fecha, etapa (Montaje/Show/Desmontaje), extras, evidencia, comentarios, checkbox validado.

**Paso 4: Seleccionar modo de pago y días adicionales**
- Cuatro botones de modo, cada uno muestra el monto calculado al momento de renderizar:
  - `Evento completo → $X,XXX` (100% de `pago_por_evento_completo`)
  - `Etapa 1 → $XXX` (25%)
  - `Etapa 2 → $X,XXX` (50%)
  - `Etapa 3 → $XXX` (25%)
- Campo numérico "Días adicionales" para el modelo burrito.
- Campo "Compensación manual".

**Paso 5: Calcular, guardar y pagar**
- Mismo flujo que base.
- Al marcar como pagado: recarga la vista del evento (el colaborador pagado aparece con badge verde en la lista de asignados).

### 8.5 Módulo: Validación de Nómina — Conductores

**Paso 1: Seleccionar conductor y rango de fechas**
- Dropdown con conductores.
- Fechas inicio y fin.

**Paso 2: Buscar rutas**
- Se cargan las jornadas del conductor en el rango.
- Por cada jornada se muestra:
  - Fecha
  - Vehículo detectado (o "No identificado")
  - Distancia detectada (o "No identificada")
  - Ruta: `de {origen} a {destino}`
  - Extras, evidencia, comentarios
  - Checkbox validado
  - Tarifa calculada (o `⚠ Sin tarifa` si no se reconoció)

**Paso 3: Calcular**
- Se suman solo las jornadas `validado = TRUE`.
- La compensación manual sirve para agregar standby u otros casos que el sistema no calculó.

**Paso 4: Guardar y pagar**
- Mismo flujo que base.

---

## 9. Anticipos

### 9.1 Registrar anticipo

1. El admin va a Anticipos → "+ Nuevo Anticipo".
2. Selecciona el colaborador del dropdown (los campos nombre, apellidos y tipo se llenan automáticamente).
3. Llena:
   - **Concepto**: para freelance, debe coincidir con el nombre del evento para descuento automático.
   - **Monto** *(obligatorio)*
   - **Fecha** *(opcional — si no se llena, se usa la fecha actual)*
   - **¿Quién entrega?** *(opcional)*
4. Guardar → cierra el modal y recarga la tabla de anticipos.

**Campos obligatorios**: únicamente `id_colaborador` y `monto`. Todos los demás son opcionales.

**Anticipos son solo de creación**: no existe función de edición ni eliminación de anticipos. La tabla es de solo lectura. Si se ingresó un anticipo incorrecto, la corrección se hace vía compensación manual positiva en la nómina correspondiente. Esto es intencional en el diseño actual.

### 9.2 Reglas de descuento automático

| Tipo | Regla |
|------|-------|
| BASE | Se descuenta si la `fecha` del anticipo está dentro del `[fechaInicio, fechaFin]` del cálculo |
| CONDUCTOR | Igual que BASE (por rango de fechas) |
| FREELANCE | Se descuenta si el `concepto` contiene (fuzzy-match) el nombre del evento |
| FREELANCE (concepto vacío) | NO se descuenta automáticamente — requiere compensación manual negativa |

### 9.3 Listar anticipos

- Tabla con todos los anticipos registrados.
- Filtrable por colaborador, tipo, fecha.

---

## 10. Servicios Profesionales

Módulo completamente **independiente** de la nómina base.

**Campos obligatorios al registrar**: `nombre`, `tipo`, `evento`, `monto`. Los demás son opcionales.

**Valores exactos del campo `tipo`** (lo que se persiste en base de datos):

| Etiqueta en UI | Valor en BD |
|----------------|-------------|
| Rigger | `RIGGER` |
| Operador Audio | `OPERADOR_AUDIO` |
| Operador Video | `OPERADOR_VIDEO` |
| Operador Luz | `OPERADOR_LUZ` |
| Otro | `OTRO` |

> Usar exactamente los valores con underscore. El enum del modelo de datos (§2.9) listaba formato con espacio, pero el valor real guardado usa underscore.

**Registros son solo de creación**: no existe edición ni eliminación. La tabla es de solo lectura. Intencional.

Los registros **no afectan** los cálculos de COLABORADOR BASE ni FREELANCE. Se guardan en tabla separada (SERVICIOS_PROFESIONALES) y se listan en tabla con todos los registros.

---

## 11. Historial de nóminas

### 11.1 Vistas del historial

El historial tiene tres secciones:
1. **Base**: nóminas semanales.
2. **Eventos (Freelance)**: nóminas por evento.
3. **Conductores**: nóminas por periodo.

### 11.2 Comportamiento de carga — asimetría importante

| Sección | Comportamiento al entrar |
|---------|--------------------------|
| **Base** | Muestra "Aplica un filtro para ver los registros". No carga datos hasta que el admin aplique un filtro. |
| **Conductores** | Igual que Base — requiere acción del admin. |
| **Eventos** | Carga automáticamente todos los eventos al entrar (sin filtro previo). |

### 11.3 Estados vacíos

- Historial Base/Conductores sin filtro aplicado: `"Aplica un filtro para ver los registros"`.
- Historial Base/Conductores con filtro pero sin resultados: `"Sin registros para los filtros seleccionados"`.
- Historial Eventos sin eventos: `"Sin eventos registrados"`.
- Historial Eventos con eventos pero sin nóminas guardadas: el evento aparece con badge `SIN_NOMINAS` y sin toggle colapsable.

### 11.4 Filtros disponibles (Base y Conductores)

- Por colaborador
- Por estado (`PENDIENTE` / `PAGADO`)
- Por rango de fechas (periodo_inicio / periodo_fin)

### 11.5 Resumen de totales

Encima de la tabla de historial (Base y Conductores) se muestran tres widgets de conteo:
- **Por pagar**: total de nóminas PENDIENTE
- **Pagado**: total de nóminas PAGADO
- **Total registros**: suma

### 11.6 Historial de eventos (vista agrupada y colapsable)

- Lista todos los eventos con su estado general.
- Por cada evento: badge de estado, conteo de freelances pagados/pendientes.
- Los eventos son **colapsables/expandibles** con un toggle individual.
- Al expandir: lista de freelances con nombre, total y estado de nómina.
- Desde el historial de eventos, el admin puede **marcar como pagado** directamente sin ir al panel de validación.
- Al marcar pagado desde historial: recarga la lista completa de eventos.

---

## 12. Acciones que hoy viven en Sheets y deben migrar a la UI

Estas son las acciones que actualmente el admin realiza **directamente editando celdas en Google Sheets** y que en la nueva tecnología deben tener pantalla/formulario propio en la interfaz.

### 12.1 Alta y baja de colaboradores

**Hoy**: el admin abre la hoja COLABORADORES y agrega/elimina filas manualmente.

**Migración**: pantalla de ABM de colaboradores con:
- Formulario de alta: todos los campos del modelo, con validación por tipo.
- Eliminación con confirmación y verificación de dependencias (nóminas pendientes).
- El ID debe generarse automáticamente (no depender de que el admin lo escriba).

### 12.2 Eliminación de eventos

**Hoy**: el admin elimina la fila del evento directamente en EVENTOS.

**Migración**: botón "Eliminar" en la tabla de eventos con confirmación y validación de que no tenga nóminas asociadas.

### 12.3 Edición del pago de un evento

**Hoy**: el admin edita directamente la celda en EVENTOS.

**Migración**: campo editable inline en la tabla de eventos (ya parcialmente implementado: el pago se puede editar, pero en la migración debe persistirse en BD).

### 12.4 Activación del proceso "Generar Jornadas"

**Hoy**: botón en la interfaz que llama a `generarJornadas()` en el backend AppScript, o trigger automático.

**Migración**: botón "Regenerar jornadas" en la UI que ejecuta el job de procesamiento. Puede ser también un job automático programado (cron) que se ejecute cada X horas o al recibir nuevos registros.

### 12.5 Configuración de parámetros del sistema

**Hoy**: valores hardcoded en el código JS del frontend (ej. pago por defecto según tamaño: CHICO=$1,500, MEDIANO=$2,500, GRANDE=$3,000).

**Migración**: tabla de parámetros del sistema configurable desde la UI por el admin:
- Monto por defecto por tamaño de evento.
- Posiblemente: umbral de días para bono 7° día (hoy es 6 días L-S).

### 12.6 Corrección de datos en REGISTROS_NORMALIZADOS

**Hoy**: si un registro viene con datos erróneos (nombre de evento mal escrito, actividad incorrecta), el admin puede corregirlo directamente en la hoja.

**Migración**: pantalla de revisión/corrección de registros de asistencia, con posibilidad de:
- Corregir el nombre del evento.
- Cambiar el tipo de actividad.
- Eliminar registros duplicados o erróneos.

### 12.7 Gestión de la hoja ASIGNACIONES

**Hoy**: se puede editar directamente en la hoja ASIGNACIONES.

**Migración**: solo desde la pantalla de asignación de evento (ya implementado en AppScript, debe portarse a la nueva UI).

### 12.8 Autenticación (completamente ausente en el sistema actual)

**Hoy**: la "autenticación" la provee Google. Solo los usuarios con acceso al Google Sheet pueden abrir la web app. No hay login, tokens ni roles.

**Migración**: debe construirse desde cero:
- Sistema de login (usuario + contraseña, o SSO).
- Al menos un rol de admin (único perfil que usa el sistema).
- Si en el futuro se agrega un rol de "solo lectura" para el historial, debe estar previsto en el esquema.
- Proteger todos los endpoints de API con autenticación.

### 12.9 Almacenamiento de evidencias fotográficas

**Hoy**: las evidencias son **URLs de Google Drive**. El sistema extrae el `fileId` de la URL y genera un thumbnail: `https://drive.google.com/thumbnail?id={fileId}&sz=w200`. Maneja tres formatos distintos de URL de Drive.

**Migración**: decidir la arquitectura de archivos antes de comenzar:
- **Opción A**: mantener URLs externas (el personal sube fotos a Drive/S3 y pega la URL en el formulario de registro).
- **Opción B**: upload propio al servidor (campo `file` en el formulario de registro, almacenado en storage local o S3).
- La lógica de conversión de URL de Drive ya no aplica en la migración; el sistema nuevo debe poder renderizar la evidencia desde donde sea que esté almacenada.

---

## 13. Estados y transiciones

### 13.1 Estados de jornada (`validado` + `tipoPago`)

```
[Sin revisar]
  validado = FALSE
  tipoPago = (propuesto automáticamente)
        ↓ Admin revisa
[Revisada]
  validado = TRUE
  tipoPago = (confirmado o ajustado por admin)
        ↓ Se incluye en cálculo
[En cálculo]
  tipoPago ∈ {JORNADA_COMPLETA, JORNADA_COMPLETA+EVENTO, TRASLAPE_40, TRASLAPE_50}
```

### 13.2 Estados de nómina

```
[No existe]
  → Admin calcula por primera vez
        ↓ Guardar nómina
[PENDIENTE]
  → Admin puede recalcular y guardar de nuevo
  → Admin puede marcar como pagado
        ↓ Marcar como pagado (IRREVERSIBLE)
[PAGADO]
  → Solo lectura. No se puede modificar ni recalcular.
```

### 13.3 Estado de evento (para freelances)

```
SIN_FREELANCE  → No tiene freelances asignados con nómina guardada
PENDIENTE      → Al menos un freelance con nómina PENDIENTE
PAGADO         → Todos los freelances con nómina PAGADO
```

---

## 14. Validaciones de integridad

| Validación | Regla |
|-----------|-------|
| Nómina PAGADO | No puede recalcularse ni modificarse |
| Anticipo freelance | Solo se descuenta si el concepto coincide con el evento |
| Evento CHICO | No genera bono de evento para base (aunque el admin puede forzar `JORNADA_COMPLETA + EVENTO`) |
| Traslapes | Nunca sumar dos jornadas completas el mismo día |
| tipoPago | Solo los 6 valores del enum son válidos |
| Modo de pago freelance | Solo `completo`, `etapa1`, `etapa2`, `etapa3` |
| Días adicionales | Solo para freelances; número entero ≥ 0 |
| Bono 7° día | Solo si la semana tiene jornadas validadas los 6 días L–S |
| Transportes — mínimos | No se puede dejar la tabla con menos de 1 vehículo (fila) ni menos de 1 distancia (columna) |
| Transportes — estructura | Primera columna siempre es nombre de vehículo; demás columnas son tarifas numéricas ≥ 0 |
| Transportes — sin confirmación | Eliminar fila o columna de TRANSPORTES es inmediato, sin modal de confirmación previa |
| Colaborador CONDUCTOR | No tiene campos de sueldo/bono editables; sus tarifas vienen de TRANSPORTES |
| Anticipo / Servicio Prof. | Solo se pueden crear, no editar ni eliminar desde la UI |
| Nómina PAGADO — UI | Al intentar calcular sobre una nómina PAGADO se muestra bloqueo visual, no el formulario |

---

## 15. Permisos y gobernanza

- **La validación humana tiene prioridad final** sobre cualquier cálculo automático.
- El admin puede:
  - Aprobar o rechazar cualquier jornada.
  - Cambiar el tipo de pago de cualquier jornada.
  - Agregar compensación manual (positiva o negativa) en cualquier nómina.
  - Resolver traslapes asignando el porcentaje correcto.
- Los **servicios profesionales** se mantienen separados de la nómina interna.
- Una nómina marcada como `PAGADO` no puede ser modificada por nadie (salvo intervención directa en BD, que no debe permitirse desde la UI).

---

## 16. Referencia de valores enumerados

### `tipoPago` en JORNADAS_CONSOLIDADAS

| Valor | Efecto en cálculo base |
|-------|------------------------|
| `JORNADA_COMPLETA` | 1.0 días de sueldo, sin bono de evento |
| `JORNADA_COMPLETA + EVENTO` | 1.0 días de sueldo + bono_por_evento × 1.0 |
| `TRASLAPE_40` | 0.4 días de sueldo + bono_por_evento × 0.4 |
| `TRASLAPE_50` | 0.5 días de sueldo + bono_por_evento × 0.5 |
| `SIN_PAGO` | Excluida del cálculo |
| `ERROR_EVENTO` | Excluida del cálculo; requiere corrección |

### `modoPago` para freelance

| Valor | Porcentaje |
|-------|:----------:|
| `completo` | 100% |
| `etapa1` | 25% |
| `etapa2` | 50% |
| `etapa3` | 25% |

### `tamano` de evento

| Valor | Efecto |
|-------|--------|
| `CHICO` | Sin bono para base; pago base para freelance |
| `MEDIANO` | Con bono para base; pago estándar para freelance |
| `GRANDE` | Con bono para base; pago estándar para freelance |

### `tipo` de colaborador

| Valor | Descripción |
|-------|-------------|
| `COLABORADOR BASE` | Personal interno, pago semanal |
| `FREELANCE` | Personal externo, pago por evento |
| `CONDUCTOR` | Chofer, pago por ruta/distancia |

### `estado` de nómina

| Valor | Descripción |
|-------|-------------|
| `PENDIENTE` | Calculada y guardada, aún no pagada |
| `PAGADO` | Pagada. Inmutable. |

### `tipo` en SERVICIOS_PROFESIONALES

| Valor en BD |
|-------------|
| `RIGGER` |
| `OPERADOR_AUDIO` |
| `OPERADOR_VIDEO` |
| `OPERADOR_LUZ` |
| `OTRO` |

---

## 17. Comportamiento detallado de la tabla TRANSPORTES

### 17.1 Flujo de edición

1. Vista normal: tabla de solo lectura con tarifas formateadas como `$X.XX`.
2. Clic "Editar" → modo edición: todos los headers y celdas se convierten en `<input>`.
3. En modo edición están disponibles: `+ Distancia` (agrega columna al final), `+ Vehículo` (agrega fila al final), `Guardar`, `Cancelar`.
4. Cancelar → descarta todos los cambios y vuelve a modo lectura (recarga desde servidor).
5. Guardar → envía toda la tabla al servidor, que la reemplaza atómicamente con rollback automático si falla.

### 17.2 Flujo de eliminación (modal "Gestionar")

1. Clic "Gestionar" → carga snapshot completo de TRANSPORTES en memoria.
2. Modal con dos dropdowns independientes:
   - Dropdown de columnas (distancias): excluye la primera columna (vehículo).
   - Dropdown de filas (vehículos): listados por nombre.
3. Clic "Eliminar columna" → elimina del snapshot y guarda inmediatamente.
4. Clic "Eliminar fila" → igual.
5. **No hay confirmación previa**: la eliminación es inmediata al clic.
6. Solo se puede eliminar una fila o columna a la vez.

### 17.3 Restricciones mínimas

- No se puede eliminar la última distancia si solo queda 1 (mensaje: `"Debe quedar al menos una distancia"`).
- No se puede eliminar el último vehículo si solo queda 1 (mensaje: `"Debe quedar al menos un vehículo"`).
- No existe reordenamiento de filas ni columnas.

### 17.4 Columna STANDBY

- Es una columna normal con el header exacto `STANDBY` (mayúsculas, sin espacios).
- El sistema la detecta automáticamente cuando no puede identificar una distancia en el detalle de la jornada del conductor.
- Si no existe la columna STANDBY y el conductor tiene un día sin ruta identificada, la tarifa es `$0` y se requiere compensación manual.

---

## 18. Decisiones críticas de arquitectura para la migración

Estos son los puntos donde el sistema actual tiene deuda técnica o limitaciones de diseño que la migración **debe resolver** — no replicar.

### 18.1 El cliente es la fuente de verdad al guardar nóminas (DEBE CAMBIAR)

**Problema actual**: al guardar una nómina, el frontend extrae los valores del DOM (incluyendo la compensación manual del `<input>`) y los envía al servidor como un objeto resumen precalculado. El servidor lo almacena sin recalcular.

**Implicación**: si el cliente manipulara los valores en el DOM antes de guardar, el servidor aceptaría cualquier número.

**Solución en migración**: el endpoint de guardado debe recibir únicamente los parámetros de entrada (`colaborador_id`, `periodo_inicio`, `periodo_fin`, `compensacion_manual`) y recalcular en el servidor antes de guardar. El servidor es la única fuente de verdad.

### 18.2 `generarJornadas()` destruye validaciones humanas (DEBE CAMBIAR)

**Problema actual**: cada vez que se regeneran jornadas, se borra y reescribe toda la tabla JORNADAS_CONSOLIDADAS, incluyendo los campos `validado` y `tipoPago` que el admin haya editado.

**Solución en migración**: usar upsert por clave `(id_colaborador, fecha)`:
- Si la jornada ya existe con `validado = TRUE` → preservar `validado` y `tipoPago`, solo actualizar campos de asistencia (entrada, salida, detalle, extras, evidencias).
- Si la jornada es nueva → insertar con `validado = FALSE` y `tipoPago` propuesto automáticamente.
- Si un registro fue eliminado del form de origen → marcar como `SIN_PAGO` en lugar de borrarlo, para preservar el historial.

### 18.3 Ausencia total de autenticación (DEBE CONSTRUIRSE)

El sistema actual no tiene usuarios, sesiones ni roles. La migración debe incluir desde el inicio:
- Tabla `usuarios` con credenciales y rol.
- Middleware de autenticación en todos los endpoints.
- Sesión o JWT según la arquitectura elegida.
- Al menos el rol `admin` (único perfil actual). Prever `supervisor` (solo lectura) y `capturista` (solo registro de asistencia) para uso futuro.

### 18.4 Sin paginación ni búsqueda en tablas grandes (A CONSIDERAR)

Ninguna tabla tiene paginación. En volúmenes bajos (< 50 colaboradores, < 200 jornadas/semana) es aceptable. La migración debe evaluar si agregar paginación en historial y jornadas desde el inicio.

### 18.5 Compensación manual no persiste si no se guarda (A COMUNICAR AL USUARIO)

Si el admin modifica la compensación manual en el formulario de cálculo y navega a otra sección sin guardar, el valor se pierde (solo existe en el estado local de la pantalla). La migración debe mostrar una advertencia de "cambios sin guardar" antes de navegar.

### 18.6 Arquitectura de evidencias fotográficas (DECIDIR ANTES DE COMENZAR)

Ver §12.9. La decisión de cómo almacenar las fotos afecta el diseño del formulario de registro de asistencia, el modelo de datos y los requisitos de infraestructura. Debe definirse antes de iniciar la migración.

### 18.7 Parámetros de sistema hardcodeados (EXTERNALIZAR)

Los siguientes valores están actualmente hardcodeados en el frontend y deben externalizarse a una tabla de configuración administrable desde la UI:

| Parámetro | Valor actual |
|-----------|-------------|
| Pago default evento CHICO | $1,500 |
| Pago default evento MEDIANO | $2,500 |
| Pago default evento GRANDE | $3,000 |
| Días requeridos para bono 7° día | 6 (L–S) |
| Porcentaje traslape parcial opciones | 40% y 50% |

---

*Revisado el 2026-06-27. Generado a partir del análisis completo del código AppScript (backend.js, code.js, JS_app.html, VIEW_panel.html) y los documentos REGLAS_NEGOCIO_NOMINA_SM.md y MANUAL_USUARIO.md. Auditado por agente de revisión de gaps.*
