# Manual de Usuario — Sistema de Nómina SM Producciones

> Documento de uso del sistema web de administración de nómina de **SM Producciones**, empresa de producción de eventos en vivo. El manual describe cada módulo de la aplicación, los flujos de trabajo paso a paso y las reglas de negocio que gobiernan el cálculo de pagos.

---

## Tabla de contenidos

1. [Introducción](#1-introducción)
   - 1.1 ¿Qué es el sistema?
   - 1.2 Tipos de personal
   - 1.3 Cómo iniciar sesión
   - 1.4 Estructura de la interfaz
   - 1.5 Convenciones usadas en este manual
2. [Módulos de catálogo](#2-módulos-de-catálogo)
   - 2.1 Colaboradores
   - 2.2 Eventos
   - 2.3 Transportes (tarifas y flotilla)
   - 2.4 Parámetros del sistema
3. [Registro de asistencia](#3-registro-de-asistencia)
   - 3.1 Formulario público del colaborador
   - 3.2 Registro de asistencia del administrador
4. [Panel de Validación y Nómina](#4-panel-de-validación-y-nómina)
   - 4.1 Generación de jornadas
   - 4.2 Validación de jornadas
   - 4.3 Cálculo de nómina
   - 4.4 Guardar y pagar nómina
5. [Anticipos, Préstamos, Servicios Profesionales y Viáticos](#5-anticipos-préstamos-servicios-profesionales-y-viáticos)
   - 5.1 Anticipos
   - 5.2 Préstamos
   - 5.3 Servicios Profesionales
   - 5.4 Viáticos
6. [Historial de Nóminas](#6-historial-de-nóminas)
   - 6.1 Consulta y filtros
   - 6.2 Impresión de nóminas
7. [Dashboard](#7-dashboard)
8. [Configuración de la cuenta](#8-configuración-de-la-cuenta)

---

# 1. Introducción

## 1.1 ¿Qué es el sistema?

El Sistema de Nómina de **SM Producciones** es una aplicación web que permite administrar de principio a fin el pago del personal de la empresa:

- Gestiona los **catálogos** del negocio (colaboradores, eventos, transporte).
- Captura la **asistencia** de cada colaborador (bodega, evento o transporte).
- Genera **jornadas consolidadas** a partir de la asistencia.
- Permite **validar** las jornadas y **calcular, guardar y pagar** las nóminas.
- Lleva un **historial permanente** de todas las nóminas calculadas y pagadas.
- Registra **anticipos, préstamos, servicios profesionales y viáticos**.

El sistema opera bajo una idea central: **la validación humana tiene la última palabra**. El sistema propone cálculos automáticos, pero el administrador revisa, aprueba y ajusta antes de que el pago quede registrado.

## 1.2 Tipos de personal

La aplicación distingue cuatro tipos de colaborador, cada uno con su propia lógica de pago:

| Tipo | Descripción | Forma de pago |
|------|-------------|---------------|
| **Colaborador Base** | Personal interno y estable (trabaja en bodega y en eventos). | Semanal (lunes a sábado), con sueldo diario + extra por día de evento + bono del séptimo día. |
| **Freelance** | Personal externo contratado por evento. | Por evento (puede pagarse en etapas: 25 / 50 / 25 %), con pago por "paquete" más días adicionales. |
| **Conductor** | Choferes de la flotilla. | Por bloque de 24 horas (día de ruta), según vehículo y distancia recorrida. |
| **Conductor base** | Chofer interno con sueldo diario. | Combinación: sueldo base por días trabajados + tarifas por rutas + bono del séptimo día. |

> **Nota:** los conceptos de *Colaborador Base*, *Freelance*, *Conductor* y *Conductor base* aparecen repetidos a lo largo de todo el sistema (pestañas, filtros, listados), siempre con el mismo significado.

## 1.3 Cómo iniciar sesión

1. Abra la dirección web del sistema en su navegador (Chrome, Edge, Firefox o Safari, en computadora o celular).
2. Si aún no tiene una cuenta, use la opción de **registro** e introduzca sus datos.
3. Introduzca su **correo electrónico** y **contraseña** y pulse **Iniciar sesión**.
4. Si el administrador habilitó la **verificación en dos pasos**, complete el código que se le solicite.

> El acceso a los módulos administrativos requiere una cuenta con rol de **administrador**. Si su cuenta no tiene permisos, no verá las opciones del menú lateral.

## 1.4 Estructura de la interfaz

Al entrar al sistema verá:

- **Barra lateral izquierda**: el menú principal con los módulos de la aplicación. Puede colapsarse a iconos para ganar espacio.
- **Encabezado superior**: título de la sección en la que se encuentra y, en algunos módulos, los botones principales de acción.
- **Área de contenido**: la pantalla del módulo activo.
- **Esquina inferior izquierda**: su menú de usuario (perfil, seguridad, apariencia y cerrar sesión).

Módulos disponibles en el menú (en orden):

1. **Dashboard** — resumen general del negocio.
2. **Panel Validación** — revisión de jornadas y cálculo de nómina (módulo central).
3. **Colaboradores** — alta, baja y edición del personal.
4. **Eventos** — catálogo de eventos y asignación de personal.
5. **Transportes** — tarifas de vehículos y flotilla (unidades).
6. **Anticipos** — entregas de dinero a cuenta.
7. **Préstamos** — créditos con calendario de cuotas.
8. **Servicios Prof.** — pagos a servicios profesionales externos.
9. **Viáticos** — gastos ligados a eventos.
10. **Historial** — registro permanente de nóminas.
11. **Registro Asistencia** — captura y consulta de asistencias.
12. **Parámetros** — configuración global del sistema.

## 1.5 Convenciones usadas en este manual

- Las **rutas de navegación** se escriben con flechas: *Menú → Módulo → Sección*.
- Los **botones y campos** se escriben en **negritas** con el texto exacto que aparece en pantalla.
- Los mensajes del sistema se muestran *en cursiva*.
- Cada acción destructiva o irreversible (eliminar, marcar como pagado) pide una **confirmación**; es parte normal del sistema, no un error.

---

# 2. Módulos de catálogo

Los módulos de catálogo almacenan la información base del negocio: quiénes trabajan, en qué eventos y con qué tarifas se paga. Antes de capturar asistencia o calcular nóminas, los catálogos deben estar completos.

## 2.1 Colaboradores

El módulo **Colaboradores** (`Menú → Colaboradores`) administra el alta, consulta y edición del personal. La pantalla muestra una tabla con todos los colaboradores y un botón **"+ Nuevo colaborador"** en la parte superior.

### Alta de un colaborador

1. Pulse **"+ Nuevo colaborador"**.
2. Complete el formulario:
   - **Nombre** y **Apellidos** (obligatorios).
   - **Tipo**: *Colaborador Base*, *Freelance*, *Conductor* o *Conductor base*.
   - Según el tipo seleccionado, el formulario muestra campos adicionales:
     - **Base / Conductor base**: **Sueldo diario**.
     - **Base** (además): **Categoría** (*Encargado de área*, *Técnico*, *Stagehand SM*) y **Nivel** (*1* o *2*). Ambos son obligatorios para el personal base.
     - **Freelance**: **Extra día adicional** (monto por cada día extra sobre el paquete del evento).
3. Pulse **Guardar**.

> Para un **Base** es obligatorio asignar categoría y nivel: el extra por día de evento se calcula precisamente a partir de esa combinación (ver sección [Parámetros del sistema](#24-parámetros-del-sistema)).

### Edición de un colaborador

Las celdas de la tabla son editables en línea (directamente sobre la tabla). Los campos disponibles dependen del tipo:

- **Base**: Categoría, Nivel, Sueldo/día y **Compensación** (%). La compensación es un porcentaje que el sistema aplica automáticamente a la nómina del colaborador (ver Panel de Validación).
- **Conductor base**: Sueldo/día.
- **Freelance**: Extra día.
- **Conductor**: no tiene campos editables; su pago sale de las tarifas de Transportes.

Para guardar los cambios de una fila pulse el botón **Guardar** (ícono de lápiz) de esa fila. El botón se deshabilita para Conductores y cuando a un Base le falte categoría o nivel.

### Perfil de colaborador

Junto a cada fila, el botón **Perfil** abre la ficha del colaborador, con:

- **Información general** (solo lectura): tipo, categoría, nivel, sueldo diario, compensación o extra día.
- **Datos de emergencia** (editables): tipo de sangre, número de seguro social, alergias y padecimientos crónicos.
- **Documentos de identificación** (editables, archivos imagen o PDF): **Seguro social**, **INE**, **CURP**, **Comprobante de domicilio** y **Licencia de conducir**.

Los documentos ya cargados se pueden **Ver** (abren en otra pestaña) o **eliminar**. Todos los cambios se guardan con el botón **"Guardar perfil"**.

### Enlace de asistencia personal

Cada colaborador tiene un **enlace personal** para registrar su asistencia desde su celular, sin necesidad de iniciar sesión. En la tabla, la columna **"Enlace asistencia"** permite:

- **Copiar link**: copia la URL al portapapeles para enviarla al colaborador (p. ej. por WhatsApp).
- **Regenerar enlace** (ícono de recarga): genera una nueva URL. **El enlace anterior deja de funcionar.**

> El enlace es personal e intransferible: quien lo recibe registra asistencia únicamente con su identidad. No debe compartirse con otras personas.

### Baja de un colaborador

1. Pulse el botón **eliminar** (papelera) de la fila.
2. Confirme la acción en el diálogo.

El sistema impide eliminar colaboradores que tengan nóminas pendientes asociadas.

## 2.2 Eventos

El módulo **Eventos** (`Menú → Eventos`) administra el catálogo de eventos de producción y el personal asignado a cada uno. La pantalla lista todos los eventos con su nombre, lugar, fechas, tamaño y pago.

### Crear un evento

1. Pulse **"+ Nuevo evento"**.
2. Complete:
   - **Nombre** (obligatorio, debe ser único).
   - **Lugar** (opcional).
   - **Fecha inicio** y **Fecha fin** (opcionales, útiles para la cotización del evento).
   - **Tamaño**: *Chico*, *Mediano* (valor por defecto) o *Grande*.
3. Pulse **Crear**.

El tamaño del evento determina su pago por defecto para freelances (configurable en Parámetros) y si el evento genera o no **extra por día de evento** para el personal base:

| Tamaño | Genera extra para Base | Pago por defecto |
|--------|:----------------------:|------------------|
| **Chico** | No (se paga como día de bodega) | $1,500 |
| **Mediano** | Sí | $2,500 |
| **Grande** | Sí | $3,000 |

> Los montos por defecto son editables en *Menú → Parámetros*.

### Editar un evento

Pulse el botón **Editar** (lápiz) de la fila y modifique los campos del mismo formulario de creación. Pulse **Guardar**.

### Editar el pago por evento

La columna **"Pago"** de la tabla es un campo numérico editable directamente: escriba el nuevo valor y salga del campo (o pulse Enter). El valor se guarda automáticamente, sin botón adicional.

### Ver asignación de personal

Pulse **Ver** (ícono de ojo) en la fila del evento para abrir la pantalla de asignación del evento. Esta vista se describe en detalle en la subsección [Asignación de personal a un evento](#asignación-de-personal-a-un-evento).

### Eliminar un evento

1. Pulse la papelera de la fila.
2. Confirme en el diálogo.

El sistema impide eliminar un evento que tenga nóminas asociadas.

### Asignación de personal a un evento

La pantalla *"Asignación — {nombre del evento}"* se divide en varias pestañas: **Resumen**, **Gastos**, **Detalles**, **Asignación y cotización**, **Unidades**, **Nómina** y **Viáticos**.

#### Pestaña Resumen

La pestaña **Resumen** (la que se abre por defecto) concentra la información clave del evento:

- **Widgets**: días del evento, colaboradores asignados (base/freelance/conductor), total cotizado y **total de gastos** (nómina + viáticos + servicios profesionales).
- **Desglose de gastos**: listado por concepto (nómina, viáticos, servicios profesionales).
- **Indicadores**: margen proyectado (total cotizado − total gastos), rentabilidad (%), promedio de gastos por día y por colaborador, y pago freelance.
- Botón **"Imprimir resumen"**: genera una vista imprimible con los rasgos del evento y el desglose de gastos, **sin** los indicadores financieros.

#### Pestaña Gastos

La pestaña **Gastos** ofrece el desglose completo del costo del evento, sección por sección (acordeón expandible):

- **Nómina freelance**: por colaborador, expandible hasta el detalle de cada registro.
- **Nómina base extra evento**: por colaborador, expandible por jornadas (con su bono).
- **Viáticos**: tabla con los viáticos del evento.
- **Servicios profesionales**: tabla con los servicios ligados al evento.

En la parte superior, widgets con los totales: total de gastos, nómina, viáticos y servicios.

#### Pestaña Detalles

La pestaña **Detalles** permite editar los datos generales y de contratación del evento, y desde aquí se imprime la **ficha del evento** (botón **"Imprimir ficha"**):

- La ficha incluye: detalle del evento (incluidos **contacto**, **teléfono de contacto** y **enlace de ubicación**), cotización, la **tabla de unidades de transporte asignadas**, la asignación de personal y, a partir de una nueva página, los **perfiles de los colaboradores asignados** y, en otra página, los **perfiles (mini CV) de las unidades de transporte asignadas**.

#### Pestaña Unidades

La pestaña **Unidades** gestiona la **flotilla asignada al evento** (unidades de transporte del módulo *Transportes*):

1. En el selector **"Agregar unidad"** elija una unidad disponible y pulse **Asignar**. La lista solo muestra las unidades que aún no están asignadas a este evento.
2. Las unidades asignadas aparecen en la tabla con marca/modelo, placas, pertenencia y categoría (tarifa). Use **Quitar** para desasignarlas.
3. Cada cambio se guarda de inmediato en el servidor.

> Las unidades asignadas se incluyen automáticamente en la ficha impresa del evento.

#### Requisitos de personal

El fieldset **"Requisitos de personal"** (colapsable) define cuántos colaboradores necesita el evento por **categoría y nivel** (Base) y cuántos **freelance**. Es la base para calcular la cotización y limita el buscador de colaboradores:

1. Pulse **Editar** en el encabezado del fieldset.
2. En la matriz, escriba cuántos colaboradores se necesitan de cada combinación (*Encargado de área / Técnico / Stagehand SM*, cada uno con *Nivel 1* y *Nivel 2*) y cuántos *Freelance*.
3. Pulse **Guardar**. El botón **Limpiar** pone todos los valores en cero y guarda.

En modo resumen, cada requisito muestra un badge de progreso (p. ej. *"Encargado N1: 2/3"*), verde cuando está cubierto y ámbar cuando faltan personas. El botón **"+"** de un badge incompleto abre el buscador ya filtrado para ese requisito.

> Si el evento aún no tiene requisitos definidos, solo se pueden asignar Conductores. Defínalos con **Editar** para poder asignar personal Base y Freelance.

#### Agregar y quitar personal

1. En el buscador **"Buscar colaborador por nombre y agregarlo..."** escriba el nombre; los resultados aparecen agrupados por *Categoría · Nivel*, luego *Freelance* y *Conductores*.
2. Haga clic en el colaborador para agregarlo a la lista de asignados.
3. Para quitarlo, use el botón **"Quitar"** (papelera) de su fila.

Cada cambio se guarda de inmediato en el servidor (no hay botón **Guardar** en esta sección).

#### Cotización

El fieldset **"Cotización (100% de participación, sin compensación ni días extra)"** muestra un estimado del costo del evento:

- Duración del evento (en días).
- Tabla de colaboradores Base: colaborador, categoría, sueldo, extra de evento y total.
- Totales: **Subtotal Base**, **Subtotal Freelance** (número de freelances × pago por evento) y **Total cotizado**.

> La cotización solo puede calcularse si el evento tiene fecha de inicio y fin. Si faltan, el sistema muestra una advertencia y pide completarlas en **Editar**.

#### Pestaña Nómina y Viáticos del evento

La pestaña **Nómina** resume los pagos relacionados con el evento (Subtotal Base por extra de evento, Subtotal Freelance, Pagado y Por pagar) con detalle expandible por colaborador, y un botón **Imprimir**. La pestaña **Viáticos** lista los viáticos registrados para ese evento.

## 2.3 Transportes

El módulo **Transportes** (`Menú → Transportes`) tiene dos pestañas: **Tarifas** y **Unidades**.

### Tarifas (vehículo × distancia)

Esta pestaña muestra la **tabla de tarifas de conductores**: una matriz donde cada **fila** es una categoría de vehículo (p. ej. Urvan, Camión, Tráiler) y cada **columna** es un rango de distancia (p. ej. *Menos de 300m*, *300-1km*, *1-5km*, *STANDBY*). La celda es la tarifa pagada por ese vehículo y esa distancia.

La columna **STANDBY** es especial: es la tarifa para un **día sin ruta identificada** (el conductor no manejó pero estuvo disponible). El sistema la usa automáticamente cuando una jornada de transporte no coincide con ninguna distancia.

#### Editar la tabla de tarifas

1. Pulse **Editar**. Todos los encabezados y celdas se convierten en campos editables.
2. Modifique nombres y valores según sea necesario:
   - **+ Distancia**: agrega una columna al final.
   - **+ Vehículo**: agrega una fila al final.
   - Marque el checkbox **Standby** en la columna que corresponda a la tarifa de standby.
3. Pulse **Guardar** para aplicar todos los cambios de una vez, o **Cancelar** para descartarlos.

> Las tarifas deben ser números ≥ 0. Debe quedar siempre al menos un vehículo y una distancia.

#### Eliminar filas o columnas (Gestionar)

1. Pulse **Gestionar**.
2. En el diálogo, seleccione en el primer menú la **distancia** (columna) a eliminar, o en el segundo el **vehículo** (fila) a eliminar.
3. Pulse el botón de eliminar (papelera).

La eliminación es inmediata al pulsar el botón. No se puede dejar la tabla sin vehículos ni sin distancias.

### Unidades (flotilla)

La pestaña **Unidades** administra los vehículos físicos de la empresa (marca, modelo, placas, si son propios o rentados y su categoría de tarifa). Es independiente de la matriz de tarifas: las tarifas definen *cuánto se paga*, las unidades definen *qué vehículos existen*.

#### Registrar una unidad

1. Pulse **"+ Nueva unidad"**.
2. Complete **Marca** y **Modelo** (obligatorios), **Número de placas** (opcional), **Pertenencia** (*Propia* o *Rentada*) y **Categoría (tarifa)** (la fila de la matriz de tarifas a la que pertenece).
3. Pulse **Crear**.

#### Editar y eliminar unidades

- **Editar**: modifique los campos directamente en la tabla y pulse **Guardar** en la fila.
- **Eliminar**: papelera de la fila + confirmación.
- **Detalle** (ícono de ojo): abre la ficha de la unidad con:
  - **Fotografía de la unidad**: suba una imagen de la unidad (se muestra en vista previa y en la impresión del perfil).
  - **Identificación**: *Alias* (p. ej. "Unidad 01") y *Número de serie*.
  - **Póliza de seguro**: número de póliza, vigencia y el documento (se puede **Ver** o **eliminar**).
  - **Verificación**: vencimiento, tipo y color de engomado, y el comprobante (foto).
  - **Documentos del vehículo**: placas, tarjeta de circulación y tenencia.
  - Todo se guarda con el botón **Guardar**. Además incluye el botón **"Imprimir perfil"**.

#### Imprimir el perfil de la unidad

Cada unidad tiene un **perfil imprimible** (mini CV de la unidad), al estilo del perfil de un colaborador:

- Desde la lista de unidades, use el ícono de impresora de la fila, o desde el detalle de la unidad el botón **"Imprimir perfil"**.
- La impresión incluye: marca/modelo, fotografía (si se cargó), placas, pertenencia, categoría de tarifa, alias, número de serie, póliza de seguro (número y vigencia), verificación (vencimiento y engomado) y el listado de documentos en archivo.
- Las unidades **asignadas a un evento** también aparecen como mini CV dentro de la ficha impresa del evento.

## 2.4 Parámetros del sistema

El módulo **Parámetros** (`Menú → Parámetros`) concentra la configuración global de cálculo. Está dividido en cuatro bloques:

1. **Pago por defecto al crear evento**: montos que el sistema asigna automáticamente a los eventos *Chico*, *Mediano* y *Grande*.
2. **Extra por día de evento (Base)**: tabla de montos por **categoría** (*Encargado de área*, *Técnico*, *Stagehand SM*), **nivel** (*1* o *2*) y **tamaño de evento** (*Mediano* / *Grande*). Es el valor que se suma por cada día calificado de evento (montaje, show o desmontaje). Los eventos *Chico* nunca generan este extra.
3. **Bono de 7° día (base)**: número de días lunes–sábado requeridos para generar el bono del séptimo día (valor habitual: 6).
4. **Marca** (solo administradores): personaliza la identidad visual del sistema. Permite subir el **logo** (visible en toda la interfaz y en las impresiones) y elegir los **colores** de la marca, que se aplican automáticamente a los botones y elementos destacados. Los cambios se ven reflejados de inmediato.

Para aplicar cambios, modifique los valores y pulse **"Guardar parámetros"**.

> Estos valores afectan directamente los cálculos de nómina. Es recomendable revisarlos al menos una vez por período de pago.

---

# 3. Registro de asistencia

El registro de asistencia es el **origen de los datos** del sistema: a partir de él se generan las jornadas que después se validan y se convierten en nómina. Existen dos formas de capturarlo: el **formulario público del colaborador** (desde su celular, sin iniciar sesión) y el **módulo de Registro de Asistencia** del administrador (que además permite corregir o eliminar registros).

## 3.1 Formulario público del colaborador

Cada colaborador recibe un **enlace personal** (ver [Enlace de asistencia personal](#enlace-de-asistencia-personal)) que abre una página tipo tarjeta con el membrete *SM Producciones* y el título *"Registro de Asistencia"*. En la parte superior se muestra **"Registrando como"** con el nombre del colaborador y su tipo, y un aviso de que el enlace es personal y no debe compartirse.

### Tipos de actividad permitidos

El formulario se adapta al tipo del colaborador:

| Tipo de colaborador | Actividades permitidas |
|---------------------|------------------------|
| Colaborador Base | Bodega, Evento |
| Freelance | Solo Evento |
| Conductor | Solo Transporte |
| Conductor base | Bodega, Transporte |

Si solo hay una actividad permitida, el campo **"Tipo de actividad"** aparece como texto fijo; si hay dos, como lista desplegable.

### Pasos para registrar asistencia

1. **Fecha** (obligatoria, por defecto hoy) y **Hora de entrada** (obligatoria, por defecto la hora actual). La **Hora de salida** es opcional.
2. **Tipo de actividad**.
3. Complete los campos según el tipo:

**Si es Bodega:**
- **Actividad** (obligatoria): *Stagehand / Apoyo general*, *Carga / Descarga*, *Mantenimiento*, *Inventario*, *Acomodo*, *Limpieza* u *Otro*.
- **Extras** (opcionales): *Chofer / Manejo*, *Carga pesada / Maniobras*, *Responsable del transporte*, *Inventario / Conteo crítico*, *Mantenimiento especializado*, *Trabajo nocturno*, *Apoyo técnico (Cableado/Reparación)* u *Otro*.

**Si es Evento:**
- **Evento** (obligatorio): seleccione el evento de la lista.
- **Etapa(s)**: *Montaje*, *Show* y/o *Desmontaje*.
- **Funciones / Extras** (opcionales): más de 25 opciones agrupadas por categoría (AUDIO, VIDEO, ILUMINACIÓN, ESCENOGRAFÍA, LOGÍSTICA, PRODUCCIÓN, BONO y Otro), por ejemplo *AUDIO | FOH*, *VIDEO | Operación*, *ILUMINACIÓN | Moving Heads / Robóticos*, *PRODUCCIÓN | Stage Manager*, *BONO | Actitud / Desempeño (requiere aprobación)*, entre otras.

**Si es Transporte:**
- **Vehículo** (obligatorio): categoría de vehículo.
- **Origen** y **Destino** (obligatorios): ciudad o lugar de salida y llegada.
- **Distancia / Ruta** (obligatorio): rango de distancia (las de tipo *Standby* se marcan con esa etiqueta).
- **Unidad** (obligatorio): la unidad específica de la flotilla de ese tipo de vehículo.
- **Extras** (opcional): texto libre.

4. **Comentarios** (opcional).
5. **Evidencia fotográfica** (obligatoria): puede tomar una foto con la cámara del celular o subir una imagen (máximo 5 MB). El botón del formulario activa la cámara trasera cuando se usa desde un teléfono.
6. Pulse **"Enviar registro"**.

Al enviar con éxito aparece la pantalla *"¡Registro enviado!"* con el botón **"Registrar otra asistencia"**, que limpia el formulario para una nueva captura.

> El formulario valida los campos obligatorios (incluida la fotografía) la primera vez que intenta enviarse y, después, en vivo conforme se modifica cada campo.

## 3.2 Registro de asistencia del administrador

El módulo **Registro Asistencia** (`Menú → Registro Asistencia`) muestra la lista completa de registros capturados (Bodega, Evento y Transporte). El encabezado muestra el total de registros y los botones **"Regenerar jornadas"** y **"+ Nuevo registro"**.

### Crear un registro

1. Pulse **"+ Nuevo registro"**.
2. Complete el formulario (equivalente al público, con algunos ajustes):
   - **Colaborador** (obligatorio), **Fecha** (por defecto hoy), **Hora de entrada** (por defecto la actual) y **Hora de salida** (opcional).
   - **Tipo de actividad** (*Bodega*, *Evento* o *Transporte*). Al cambiar el tipo se limpian las selecciones previas.
   - **Bodega**: actividad y extras (mismas opciones que el formulario público).
   - **Evento**: nombre del evento y etapa(s) (Montaje / Show / Desmontaje). Los eventos disponibles se restringen a los del colaborador elegido (salvo para conductores).
   - **Transporte**: vehículo, distancia/ruta, **unidad** (de la flotilla de ese vehículo), origen y destino.
   - **Evidencia fotográfica** (PNG o JPG hasta 10 MB) y **Comentarios**.
3. Pulse **Guardar**.

### Filtrar y consultar

En la parte superior hay una caja **"Buscar colaborador..."**, un selector de tipo (*Todos*, *Bodega*, *Evento*, *Transporte*) y un contador de resultados.

La tabla muestra por cada registro: **Fecha**, **Entrada / Salida**, **Colaborador**, **Tipo** (con badge de color), **Descripción** (actividad de bodega; *Evento (Etapa)*; o *Vehículo · Distancia (Marca Modelo — placas)*) y la **Evidencia** (miniatura clicable que abre la foto en otra pestaña).

### Corregir un registro

Pulse el botón **editar** (lápiz) de la fila. Se abre el modal **"Corregir registro"** con los mismos campos de creación, excepto el colaborador (no se puede cambiar) y la evidencia. Pulse **"Guardar corrección"** para aplicar los cambios.

### Eliminar un registro

Pulse la papelera de la fila y confirme. El botón aparece **bloqueado con un candado** (y un mensaje explicativo) cuando el registro ya forma parte de una **jornada validada** o de una **nómina guardada**: en ese caso no puede eliminarse.

---

# 4. Panel de Validación y Nómina

El **Panel Validación** (`Menú → Panel Validación`) es el corazón del sistema: aquí se revisan las jornadas, se corrigen clasificaciones de pago y se calcula, guarda y paga la nómina. Su subtítulo lo resume: *"Revisar y validar jornadas antes de calcular nómina"*.

El panel tiene **cuatro pestañas**, una por tipo de colaborador: **Base**, **Freelance**, **Conductores** y **Conductor base**. Cada pestaña muestra un contador ámbar con el número de jornadas pendientes de ese tipo.

## 4.1 Generación de jornadas

Las **jornadas consolidadas** son el resultado de agrupar los registros de asistencia de un colaborador en un mismo día (entrada = primera hora registrada, salida = última hora). El sistema las genera automáticamente y las propone para revisión.

Para regenerarlas en cualquier momento:

1. Desde **Panel Validación**, pulse **"Regenerar jornadas"**.
2. El botón girará mientras procesa. Cuando hay registros de asistencia aún sin procesar, el botón muestra un contador ámbar (p. ej. *9+*) indicando cuántos registros nuevos están pendientes.

> **Importante:** la regeneración **preserva las validaciones ya hechas**. Si una jornada ya fue validada, solo se actualizan sus datos de asistencia (entradas, salidas, detalle), manteniendo la validación y el tipo de pago asignados. Si un registro dejó de existir, la jornada pasa a *Sin pago* en lugar de borrarse, para conservar el historial.

## 4.2 Validación de jornadas

En cada pestaña, la lista de jornadas muestra primero las **pendientes de validar** y después las **validadas**, separadas por encabezados de sección. La barra superior indica *"X pendientes · Y validadas de Z"* con una barra de progreso de validación.

### Filtros

Pulse **Filtros** para mostrar u ocultar el panel de filtros:

- **Colaborador**: caja de texto *"Buscar nombre..."*.
- **Fecha desde** / **Fecha hasta**: rangos de fecha.
- **Estado**: *Todas*, *Solo pendientes*, *Solo validadas*.
- **Nómina**: *Todas*, *En nómina* (jornadas que ya forman parte de una nómina), *Sin nómina*.
- Contador de resultados visibles y botón **Limpiar**.

### Columnas según la pestaña

- **Base**: Fecha, Colaborador, Entrada, Salida, Detalle, Extras, Evidencias, Tipo pago, Compensación, Válido.
- **Freelance**: Fecha, Colaborador, Evento/Detalle, Extras, Evidencias, Tipo pago, Válido.
- **Conductores**: Fecha, Conductor, Ruta (detalle), Extras, Comentarios, Tipo pago, Válido.
- **Conductor base**: Fecha, Colaborador, Actividad (Bodega/Ruta), Extras, Comentarios, Tipo pago, Válido.

### Operaciones por jornada

- **Válido** (checkbox): marca la jornada como validada. Solo las jornadas validadas entran en el cálculo de nómina.
- **Tipo pago** (lista): clasifica la jornada para el cálculo. Los valores disponibles dependen del tipo de colaborador (ver abajo).
- **Compensación** (solo Base): activa la compensación porcentual automática del colaborador en esa jornada.
- **Evidencias**: las miniaturas de fotos abren la imagen en grande (lightbox) al hacer clic.
- **Traslapes (Base)**: cuando un día tiene **2 o más eventos**, aparece una sub-sección por evento con un selector de **fracción de evento** (*100%* o *Traslape*) y, en caso de traslape, un porcentaje exacto (1–99).

Cada cambio se guarda **al instante**: al modificar cualquier control de una fila, el sistema envía el cambio al servidor y deshabilita temporalmente los controles de esa fila. No existe un botón "Guardar jornadas".

> Las filas con tipo de pago *Error: evento* se resaltan en rojo y las validadas en verde, para revisión visual rápida.

### Tipos de pago (`tipoPago`)

| Tipo de pago | Significado | Disponible en |
|--------------|-------------|---------------|
| **Jornada completa** | Día de trabajo normal (1.0 días de sueldo, sin extra de evento). | Base, Freelance, Conductor, Conductor base |
| **Jornada + Evento** | Día con evento pagable (1.0 días de sueldo + extra de evento). | Base, Freelance, Conductor base |
| **Traslape N%** | Día compartido entre dos eventos; se paga el porcentaje indicado (1–99). | Base, Conductores, Conductor base |
| **Sin pago** | El día no se paga. | Todos |
| **Error: evento** | El evento no se reconoció; requiere corrección. Excluida del cálculo. | Todos |

> En la pestaña **Base**, si el día ya tiene dos o más eventos con fracción asignada, la opción *Traslape* desaparece del tipo de pago del día (el traslape se pondera por evento).

### Tipos de pago y su efecto en el cálculo (Base)

| Tipo de pago | Fracción de sueldo | Extra de evento |
|--------------|:------------------:|:---------------:|
| Jornada completa | 1.0 | No |
| Jornada + Evento | 1.0 | Sí (bono según categoría/nivel) |
| Traslape 40 % | 0.4 | 0.4 × bono |
| Traslape 50 % | 0.5 | 0.5 × bono |
| Sin pago | 0 (excluida) | No |
| Error: evento | 0 (excluida) | No |

## 4.3 Cálculo de nómina

Pulse el botón **"Calcular nómina"** (calculadora) del encabezado para abrir el asistente de cálculo. El diálogo tiene dos columnas: a la izquierda los **parámetros** y a la derecha el **desglose** una vez calculado.

### Paso 1 — Seleccionar el colaborador

En **Colaborador**, elija al colaborador (aparece su tipo entre paréntesis). Al seleccionarlo, el sistema detecta automáticamente su tipo y ajusta los parámetros.

### Paso 2 — Definir el periodo (Base / Conductor / Conductor base)

El corte de nómina es **semanal (lunes a sábado)**:

- **"Semana actual"** y **"Semana anterior"**: prellenan el período actual o el anterior.
- **"−1 semana"** y **"+1 semana"**: mueven el fin de período de 7 en 7 días (no se puede bajar de 1 semana).
- **Período inicio** / **Período fin**: campos de fecha editables.
- Si el rango no respeta el corte semanal, el sistema muestra una advertencia ámbar: *"Este período no respeta el corte semanal (lunes a sábado)..."*.

### Paso 2b — Definir el evento (Freelance)

- **Evento**: seleccione el evento donde el freelance tiene asistencia. El sistema lista únicamente los eventos con asistencia registrada del colaborador (placeholder *"Cargando eventos..."*).
- **Actividades registradas**: lista de los registros del colaborador en ese evento, cada uno con fecha, **checkboxes de etapa** (*Montaje / Show / Desmontaje*), campo de **Comentarios** y una insignia de *Validada* (verde) o *Sin validar* (ámbar). Los cambios en etapas o comentarios se guardan al momento.
  > Solo las etapas de jornadas **ya validadas** en el Panel de Validación cuentan para el porcentaje de pago.
- **Días adicionales**: número de días extra sobre el paquete del evento (modelo "burrito").

### Paso 3 — Calcular

Pulse **"Calcular"**. El sistema procesa y muestra el desglose.

> Si la nómina del colaborador y período ya está **pagada**, el sistema lo indica con una caja verde *"Esta nómina ya fue pagada y no puede modificarse"* y no muestra el formulario de cálculo.

### El desglose

Según el tipo de colaborador, el desglose muestra los conceptos aplicables:

- **Base**: Días trabajados, Sueldo diario, Total base, **Bonos evento**, **Bono 7° día**.
- **Freelance**: **Pago evento (N%)** (según modo/etapa) y, si aplica, **Extras días adicionales**.
- **Conductor**: **Total rutas**.
- **Conductor base**: Días trabajados, Sueldo diario, Total base (días), **Total rutas**, **Bono 7° día**.

La sección **"Ver detalle de lo evaluado"** (colapsable) muestra el desglose por jornada/registro/ruta: fecha, tipo de pago, importe, bonos por evento con su porcentaje de etapas y fracción, y la línea *"Categoría: X · Nivel Y"* (para Base). Si el colaborador Base no tiene categoría o nivel, el extra se calcula en $0 y se muestra la advertencia. Para conductores muestra vehículo · distancia y monto.

Además, el desglose incluye:

- **Compensación manual**: campo numérico editable. Un valor **positivo** agrega pago; uno **negativo** funciona como descuento manual puntual. No puede superar el total a pagar (de lo contrario se muestra *"El descuento no puede superar el total a pagar. Máximo: $X"*).
- **Anticipos**: total de anticipos descontados (en rojo).
- **Préstamos**: descuentos por préstamo, con detalle por plazo (*"No. plazo · fecha programada (concepto): -$X"*). Cada plazo muestra la **fecha programada** de descuento. Si el colaborador tiene un préstamo activo, el desglose ofrece los botones **Aplazar** y **Distribuir carga** para reacomodar las cuotas (ver [§5.2 Aplazar o distribuir plazos](#aplazar-o-distribuir-plazos)).
- **Actividades extra registradas**: caja ámbar que avisa cuando hay extras en el período que **no** se incluyeron automáticamente y que, de ameritar pago, deben agregarse como compensación manual.
- **Comentario**: nota opcional sobre el cálculo.
- **Total a pagar**: monto final, en negrita. Se **recalcula en pantalla** al editar la compensación, sin necesidad de recalcular.
- Si quedan jornadas sin validar en el período, el sistema muestra *"N jornadas sin validar en este período"* y **deshabilita el botón Guardar** hasta validarlas.

### Reglas de negocio del cálculo

- **Base**: solo se incluyen jornadas **validadas** con tipo de pago distinto de *Sin pago* y *Error: evento*. El **bono del séptimo día** solo se genera si la semana tiene jornadas validadas en los **6 días** (lunes a sábado); si falta alguno, se pierde el bono y también el día faltado.
- **Freelance**: pago = pago del evento × porcentaje del modo + días adicionales × extra. Los **anticipos se descuentan solo si su concepto coincide con el evento**; un anticipo sin concepto no se descuenta automáticamente (requiere compensación manual negativa).
- **Traslapes**: nunca se pagan dos eventos completos el mismo día; el secundario se paga al 40–50 %.
- **Evento Chico**: para Base se paga como día de bodega, sin extra de evento (el sistema lo propone como *Jornada completa*).

## 4.4 Guardar y pagar nómina

Una vez revisado el desglose:

1. Pulse **"Guardar nómina"**. El sistema guarda la nómina en estado **PENDIENTE** y recalcula el desglose para reflejarlo. Si el colaborador y período ya tenían una nómina pendiente, esta se **sobrescribe**.
   > Si modifica la compensación manual y no guarda, el sistema le avisará *"Compensación modificada — guarda la nómina para no perder el cambio"* y le pedirá confirmación antes de cerrar el diálogo o salir de la pantalla.
2. Cuando la nómina esté guardada y sea el momento de pagar, pulse **"Marcar como pagado"**.
3. Confirme en el diálogo *"¿Marcar esta nómina como PAGADA? Esta acción es irreversible."*

> **Una nómina marcada como PAGADO no puede recalcularse ni modificarse.** Queda solo como consulta e impresión en el Historial. Esta acción es irreversible.

---

# 5. Anticipos, Préstamos, Servicios Profesionales y Viáticos

Estos cuatro módulos registran los movimientos de dinero ligados al personal. Anticipos y Préstamos se **descuentan automáticamente** en la nómina; Servicios Profesionales y Viáticos son **gastos** que se registran y consultan, pero no se integran a la nómina base.

## 5.1 Anticipos

El módulo **Anticipos** (`Menú → Anticipos`) registra entregas de dinero a cuenta de un pago futuro. La tabla es **solo lectura** (los anticipos no se pueden editar ni eliminar): si se registra uno incorrecto, la corrección se hace con una compensación manual en la nómina.

### Registrar un anticipo

1. Pulse **"+ Nuevo anticipo"**.
2. Complete el formulario:
   - **Colaborador** (obligatorio): aparece el nombre y, entre paréntesis, su tipo.
   - **Monto** (obligatorio).
   - **Origen** (obligatorio): una etiqueta que indica el contexto del anticipo:
     - **Suelto (sin evento)**: anticipo genérico, no ligado a ningún evento.
     - **Por evento**: solo permite elegir un **evento** de la lista de eventos en los que el colaborador está **asignado** (si el colaborador no tiene eventos asignados, no se puede marcar como por evento).
   - **Concepto** (opcional). Para un **freelance**, el concepto **debe coincidir con el nombre del evento** para que el anticipo se descuente automáticamente de la nómina de ese evento.
   - **Fecha de descuento** (opcional; si no se llena, se usa la fecha actual). Es la fecha en que se aplicará el descuento en la nómina del colaborador.
   - **¿Quién entrega?** (opcional).
3. Pulse **Registrar**.

> La etiqueta de **origen** es **informativa**: no cambia la forma de descontar. El descuento automático sigue dependiendo del concepto (coincidencia con el nombre del evento) y de las fechas, como se indica en las *Reglas de descuento automático*.

### Consulta y filtros

La lista se puede filtrar por **colaborador** (búsqueda de texto), **tipo** (Base, Freelance, Conductor, Conductor base), **origen** (*Por evento* / *Suelto*) y **rango de fechas**. Cada fila muestra además del colaborador y el monto, la columna **Origen** con un badge que indica el evento (p. ej. *Festival de Verano 2026*) o *Suelto*. El encabezado de filtros muestra el total de registros visibles y la **suma de montos** filtrada.

### Reglas de descuento automático

| Tipo | Regla de descuento |
|------|--------------------|
| **Base** | Se descuenta si la **fecha** del anticipo cae dentro del período de la nómina. |
| **Conductor / Conductor base** | Igual que Base (por rango de fechas). |
| **Freelance** | Se descuenta si el **concepto** coincide (coincidencia difusa) con el nombre del evento. |
| **Freelance (sin concepto)** | No se descuenta automáticamente; requiere compensación manual negativa en la nómina. |

## 5.2 Préstamos

El módulo **Préstamos** (`Menú → Préstamos`) administra créditos otorgados al personal **Base, Conductor y Conductor base** (los Freelance no pueden tener préstamos), con un **calendario de cuotas** que se descuentan automáticamente en cada nómina.

### Registrar un préstamo

1. Pulse **"+ Nuevo préstamo"**.
2. Complete:
   - **Colaborador** (obligatorio; solo Base, Conductor o Conductor base).
   - **Monto total** (obligatorio).
   - **Número de plazos** (obligatorio, 1–52) y **Periodicidad** (*Semanal*, *Quincenal* — valor por defecto — o *Mensual*). El sistema muestra una vista previa: *"N cuotas de ~$X cada una, empezando el {fecha}"*.
   - **Fecha de inicio** (obligatoria, por defecto hoy).
   - **Concepto** y **Autoriza** (opcionales).
3. Pulse **Registrar**.

### Consultar y gestionar cuotas

La tabla lista los préstamos con colaborador, monto total, plazos, inicio, **saldo pendiente** y estado (*Activo* / *Liquidado*). Al hacer clic en una fila se expande el **calendario de cuotas**:

- Cada cuota muestra su número, fecha programada, monto, estado (*Pendiente* ámbar / *Pagada* verde) y, si fue pagada vía nómina, la nota *"(vía nómina)"*.
- **Marcar pagada**: registra el pago manual de una cuota (con confirmación). Se usa cuando el pago se hizo fuera de la nómina.
- **Revertir**: devuelve a pendiente una cuota pagada manualmente (no disponible si la cuota se pagó vía nómina).

### Aplazar o distribuir plazos

Si el calendario de cuotas necesita ajustes (por ejemplo, un colaborador no alcanza a pagar la cuota completa esta quincena), el préstamo permite **mover** cargos entre plazos sin cambiar el monto total del préstamo:

1. Expandir el préstamo y marcar con las casillas los **plazos** que se quieren mover (solo plazos *Pendiente*).
2. Elegir una operación:

   - **Aplazar**: los plazos marcados se recorren **un período** hacia adelante (cada uno suma el período del calendario: semanal/quincenal/mensual), manteniendo su monto; el préstamo se alarga un período.
   - **Distribuir carga**: los montos de los plazos marcados se **suman y reparten** de manera uniforme entre los plazos restantes del calendario (los centavos sobrantes se asignan al último plazo). Los plazos marcados se eliminan del calendario y el saldo total del préstamo queda igual.

3. Pulse el botón correspondiente. La operación se aplica de inmediato y el calendario se refresca.

> Esta misma operación está disponible desde el **Panel de Validación** cuando se calcula la nómina de un colaborador con préstamo (vista previa del desglose), con los botones **Aplazar** y **Distribuir carga**.

### Eliminar un préstamo

Pulse la papelera de la fila y confirme. **No se puede eliminar** un préstamo que ya tiene cuotas pagadas (el botón se deshabilita).

> Las cuotas también se descuentan de forma automática al calcular nóminas (aparecen en el desglose como *"Plazo N: -$X"*).

## 5.3 Servicios Profesionales

El módulo **Servicios Prof.** (`Menú → Servicios Prof.`) registra pagos a profesionales externos (riggers, operadores de audio/video/luz) que **no forman parte de la nómina interna** y se mantienen separados para no contaminar la comparación de pagos. La tabla es **solo creación** (no se editan ni eliminan registros).

### Registrar un servicio profesional

1. Pulse **"+ Nuevo servicio"**.
2. Complete:
   - **Nombre** (obligatorio) y **Apellidos** (opcional).
   - **Tipo** (obligatorio): *Rigger*, *Operador Audio*, *Operador Video*, *Operador Luz* u *Otro*.
   - **Evento** (opcional): evento al que corresponde el servicio.
   - **Concepto** (obligatorio): descripción del servicio.
   - **Monto** (obligatorio).
   - **Fecha** (obligatoria, por defecto hoy).
   - **Autoriza** (opcional): quién autorizó el pago.
3. Pulse **Registrar**.

La tabla lista fecha, nombre (*Apellidos, Nombre*), tipo (con badge de color), evento, concepto, monto y autoriza.

## 5.4 Viáticos

El módulo **Viáticos** (`Menú → Viáticos`) registra gastos **siempre ligados a un evento** (transporte, hospedaje, alimentos, casetas y gasolina). La tabla es **solo creación**.

### Registrar un viático

1. Pulse **"+ Nuevo viático"**.
2. Complete:
   - **Evento** (obligatorio).
   - **Registrar como**: dos modos:
     - **Colaborador**: se asigna a un colaborador del evento elegido (solo aparecen los colaboradores asignados a ese evento). Si el evento no tiene colaboradores asignados, el sistema sugiere asignarlos primero en *Menú → Eventos*.
     - **General**: gasto general del evento, con **Nombre** (obligatorio) y **Apellidos** (opcional) de quien realiza o recibe el gasto.
   - **Tipo** (obligatorio): *Transporte*, *Hospedaje*, *Alimentos*, *Casetas y Gasolina* u *Otro*.
   - **Concepto** (obligatorio): descripción del gasto.
   - **Monto** (obligatorio) y **Fecha** (obligatoria, por defecto hoy).
   - **Autoriza** (opcional).
3. Pulse **Registrar**.

La tabla lista fecha, nombre (colaborador o con etiqueta *"(General)"*), tipo, evento, concepto, monto y autoriza.

> Los viáticos se consultan también desde la pantalla de asignación de cada evento (pestaña **Viáticos**).

---

# 6. Historial de Nóminas

El módulo **Historial** (`Menú → Historial`) es el registro **permanente** de todas las nóminas calculadas y guardadas: *"Registro permanente de nóminas calculadas"*. Aquí se consultan, se imprimen, se marcan como pagadas y (si están pendientes) se eliminan.

La pantalla tiene **cuatro pestañas** con contador: **Base**, **Freelance**, **Conductores** y **Conductor base**.

## 6.1 Consulta y filtros

Pulse **Filtros** para mostrar u ocultar el panel:

- **Colaborador**: lista *"Todos"* o un colaborador específico (se filtra según la pestaña activa; al cambiar de pestaña se limpia).
- **Estado**: *Todos*, *Pendiente*, *Pagado*.
- **Período desde** / **Período hasta**: rango de fechas (solo en Base, Conductores y Conductor base).
- **Evento**: selector de eventos (solo en la pestaña Freelance).
- Contador de resultados visibles y botón **Limpiar**.

En cada pestaña, tres tarjetas de resumen: **Por pagar** (ámbar), **Pagado** (verde) y **Total registros** (o *Nóminas* en Freelance).

### Pestaña Base

Tabla con: Período (*Fecha inicio → Fecha fin*), Colaborador, Días, Base, Bonos, Compensación, **Anticipos** (en rojo), **Préstamos** (en rojo), Total, Comentario (truncado con tooltip) y Estado (*PENDIENTE* ámbar / *PAGADO* verde).

### Pestaña Freelance

Lista de **eventos colapsables**: al hacer clic en un evento se expande la lista de sus freelances (Freelance, Compensación, Total, Comentario, Estado y acciones). Cada evento muestra su tamaño, el badge de estado del evento (*Sin nóminas* gris, *PENDIENTE* ámbar, *PAGADO* verde) y un contador *"X/Y pagados"*.

### Pestañas Conductores y Conductor base

- **Conductores**: Período, Conductor, Rutas (número de rutas/días), Total rutas, Compensación, Anticipos, Préstamos, Total, Comentario, Estado.
- **Conductor base**: Período, Colaborador, Días, Base, **Rutas**, **Bono 7°**, Compensación, Anticipos, Préstamos, Total, Comentario, Estado.

### Acciones sobre una nómina

Al hacer clic en una fila se expande el **desglose detallado** de esa nómina (jornadas, registros o rutas según el tipo). Las acciones disponibles son:

- **Imprimir** (ícono de impresora): abre la impresión de esa nómina en una nueva pestaña.
- **Marcar pagado** (solo nóminas PENDIENTE): con confirmación *"¿Marcar esta nómina como PAGADA? Esta acción es irreversible."*
- **Eliminar** (solo nóminas PENDIENTE): con confirmación *"¿Eliminar este cálculo de nómina? Podrás volverlo a calcular desde el Panel de Validación."*
- Las nóminas **PAGADO** solo permiten imprimir; no pueden modificarse ni eliminarse.

## 6.2 Impresión de nóminas

### Imprimir nóminas de un período

En cualquier pestaña, el botón **"Imprimir nóminas del período"** (ícono de impresora) abre en una nueva pestaña la vista de impresión de todas las nóminas del período y tipo filtrados.

### Imprimir una nómina individual

Desde la fila de una nómina, el botón **Imprimir** abre la vista de impresión de esa nómina en particular (con su desglose y totales).

> Ambas vistas de impresión están pensadas para guardarse en PDF o imprimirse directamente desde el navegador.

---

# 7. Dashboard

El **Dashboard** (`Menú → Dashboard`) ofrece un resumen general del negocio con indicadores de todo el sistema. Está organizado en bloques:

### Tarjetas de resumen

- **Colaboradores**: total y desglose por tipo (Base, Freelance, Conductor, Conductor base).
- **Eventos**: total y desglose por tamaño (Chico, Mediano, Grande).
- **Nómina Pendiente** y **Nómina Pagada del Mes**: montos por pagar y pagados (con total histórico).
- **Anticipos del Mes**, **Préstamos** (activos y monto por cobrar), **Viáticos del Mes** y **Servicios Prof. del Mes**.

### Alertas

Si hay situaciones que requieren atención, el panel muestra una sección de **Alertas**:

- Colaboradores **sin perfil** capturado.
- **Jornadas sin validar** de semanas anteriores.
- **Cuotas por vencer** en los próximos 7 días.

### Gráficas y resúmenes

- **Colaboradores por tipo** (barras) y **Eventos por tamaño** (barras).
- **Validación de jornadas**: anillo de progreso con el porcentaje validado y el conteo de validadas/pendientes.
- **Distribución de gastos del mes**: gráfica de pastel que divide el gasto total entre Nómina, Anticipos, Viáticos y Servicios Profesionales.
- **Resumen de transporte**: unidades totales (propias y rentadas), categorías de vehículo, tarifas registradas y tarifa promedio, con acceso directo al módulo **Gestionar transportes**.
- **Vencimientos próximos (30 días)**: cuotas de préstamo por vencer y seguros de unidades por vencer, con acceso a **Gestionar préstamos**.
- **Nómina pagada por período**: barras del monto pagado por período.
- **Últimas nóminas**: tabla con las nóminas más recientes (colaborador, período, total y estado), con enlace *Ver todas*.
- **Próximos eventos**: tabla con evento, inicio, tamaño y pago, con enlace *Ver todos*.

> El Dashboard es informativo: ninguna de sus cifras se edita desde aquí. Las acciones se realizan en los módulos correspondientes.

# 8. Configuración de la cuenta

La configuración personal se abre desde el **menú de usuario** (esquina inferior izquierda de la barra lateral), con tres opciones: **Perfil**, **Seguridad** y **Apariencia**.

## 8.1 Perfil

En **Perfil** se editan los datos de la cuenta:

- **Nombre** y **Apellidos**.
- **Correo electrónico**.

Pulse **Guardar** para aplicar los cambios. El sistema solicita confirmación de contraseña para cambios sensibles.

## 8.2 Seguridad

La sección **Seguridad** administra la protección de la cuenta:

- **Contraseña**: cambiar la contraseña actual (se pide la contraseña actual y la nueva, dos veces).
- **Verificación en dos pasos**: activar o desactivar el segundo factor de autenticación, con códigos de respaldo (recovery codes) y confirmación por código OTP.
- **Passkeys**: registrar llaves de acceso (biometría del dispositivo, etc.) para iniciar sesión sin contraseña, y administrar las registradas.

## 8.3 Apariencia

En **Apariencia** se elige el tema visual de la interfaz: **Claro**, **Oscuro** o **Sistema** (sigue la configuración del dispositivo).

---

*Fin del manual.*
