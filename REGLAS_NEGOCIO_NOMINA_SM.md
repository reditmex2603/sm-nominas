# Reglas de Negocio — Sistema de Nómina (SM Producciones)

> Este documento recoge **únicamente las reglas de negocio** (políticas de clasificación y pago del personal). No incluye la lógica técnica de implementación del sistema (normalización de datos, búsqueda por encabezados, estados automáticos, parametrización, etc.).

---

## 1. Tipos de personal y principio rector

- Existen dos tipos de personal: **Base** (interno, con semana base + extra por evento) y **Freelance** (externo, pagado por evento, día, turno o servicio).
- Un freelance **no debe ganar más** que un base equivalente por el mismo tipo de aportación, salvo que sea un **servicio profesional** claramente definido y tratado fuera de nómina.
- El personal base debe tener ventaja sobre los externos: **siempre gana su base si cumple**, y encima suma evento. Esto mantiene al equipo estable y evita depender de externos.

---

## 2. Pago del personal Base

- El base tiene **sueldo base semanal condicionado a la asistencia** y trabaja de **Lunes a Sábado**.
- Existen tres categorías con distinto extra de evento:
    - **Encargado de área** → extra de evento más alto.
    - **Técnico** → extra de evento medio.
    - **Stagehand SM** → extra de evento menor.
- Cada **día de evento** genera un extra según categoría, y ese extra **no elimina** el derecho a la semana base: se suma.
- "Evento pagable" para base = solo **montaje, show o desmontaje**. Bodega, carga y descarga cuentan como trabajo base, no como extra.
- El **transporte/viaje no se paga** como extra separado para el base (ya tiene base).
- Si un base solo hace evento y no bodega, **no debe cobrar como semana completa**:
    - solo los días realmente trabajados,
    - extras de evento únicamente si se aprueban,
    - sin bono de domingo.
    - *(Objetivo: evitar que el base abandone la bodega y solo busque evento.)*

---

## 3. Bono del séptimo día

- Si el base cumple los días suficientes de la semana (L–S), gana el **bono del séptimo día** (domingo pagado).
- Si falta algún día requerido, **pierde el bono del séptimo día** y además pierde el día faltado.

---

## 4. Evento chico

- Es un evento pequeño o local que **no conviene pagar como evento completo** (se comería el margen).
- Se paga como **día de bodega** (tarifa bodega), **sin extra de evento**.
- Aun así se **registra y contabiliza como evento** para conteo histórico y análisis.

---

## 5. Freelance

- **Stagehand "burrito"**: se paga un **paquete por evento** que cubre un rango de días (típicamente 1–5). Si el evento se extiende, se agrega un **extra por cada día adicional** (monto variable por persona).
- **Rigger**: se paga como técnico especializado con **honorario por montaje/desmontaje + transporte + viáticos** si aplica. Suele tratarse como servicio profesional.
- **Operador especializado** (audio / iluminación / video de alto nivel): se trata como **servicio profesional** en categoría separada, fuera de la nómina base, y puede cobrar tarifas altas.

---

## 6. Choferes y conductores

- Se paga por **bloques de 24 horas (día)**, no por viajes sueltos.
- La tarifa varía según:
    - **tipo de unidad** (camión grande / camión / urvan-transit),
    - **distancia** (umbral de km): a mayor distancia, mayor tarifa.
- Se paga **standby** (día sin manejar) e **ida y regreso** cuando aplica.
- En doble evento o traslape dentro de 24 h, el pago se **limita por seguridad** (ej. un porcentaje del 40–50 % si realmente rindió).

---

## 7. Traslapes (dos eventos en un día)

- **No** se pagan como dos eventos completos.
- Se aplica un **porcentaje del día de evento**:
    - **100 %** si fue evento completo,
    - **40–50 %** si fue parcial,
    - **0 %** si el registro existe pero no se justifica el pago.

---

## 8. Gobernanza / control humano

- La **validación humana tiene prioridad final** sobre el cálculo automático. Define:
    - aprobar o rechazar el día,
    - si cuenta como bodega, evento o evento chico,
    - la resolución de duplicados, traslapes y ajustes manuales.
- Los **servicios profesionales** deben mantenerse **separados de la nómina base** para no contaminar la comparación de pagos.

---

## 9. Periodo de pago

- Para el personal base, la nómina se **corta y paga semanalmente** (corte en sábado).
