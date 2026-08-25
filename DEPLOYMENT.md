# Guía de despliegue, mantenimiento y desinstalación

Aplicación Laravel 13 + Inertia/Vue 3 desplegada con **Docker Compose** y expuesta
públicamente mediante **Cloudflare Tunnel (cloudflared)**. El origen es interno
(la IP privada del servidor, `http://<ip-privada>:8080`) y el TLS lo termina
Cloudflare en el borde.

## Índice

1. [Requisitos previos](#1-requisitos-previos)
2. [Instalación paso a paso](#2-instalación-paso-a-paso)
3. [Mantenimiento diario/operativo](#3-mantenimiento-diariooperativo)
4. [Desinstalación y limpieza](#4-desinstalación-y-limpieza)

---

## 1. Requisitos previos

### Software en el servidor

| Software | Versión mínima | Notas |
|----------|----------------|-------|
| Docker Engine | 24+ | Con soporte de BuildKit |
| Docker Compose | v2.24+ | — |
| Git | 2.x | Para clonar el repositorio |
| cloudflared | última estable | **Instalado en el host** (no en Docker) |

> cloudflared debe estar autenticado (`cloudflared tunnel login`) y con un túnel
> creado (`cloudflared tunnel create`). Ver [Configuración del túnel](#configuración-del-túnel-cloudflared).

### Puertos

| Puerto | Expuesto | Uso |
|--------|----------|-----|
| `0.0.0.0:8080` (vía `WEB_PORT`) | En todas las interfaces | Nginx (punto de entrada del túnel) |
| `9000`, `13714`, `3306`, `6379` | No expuestos | Internos de la red Docker |

> El puerto `WEB_PORT` (8080) enlaza en `0.0.0.0` para que cloudflared pueda
> alcanzarlo por la IP privada del host. Ver la nota de firewall en la
> [sección 2.6](#26-configuración-del-túnel-cloudflared).

### Registros DNS

- El dominio público (p. ej. `nomina.tudominio.com`) debe existir como zona en
  Cloudflare y **estar apuntando a tu cuenta** (no se usa registro A público:
  el túnel enruta el tráfico). Una vez creado el túnel, `cloudflared` asocia el
  hostname automáticamente vía DNS en Cloudflare.

---

## 2. Instalación paso a paso

### 2.1 Clonar el repositorio

```bash
git clone git@github.com:TU_ORG/nominas-sm.git
cd nominas-sm
```

### 2.2 Configurar variables de entorno

```bash
cp .env.example.production .env
```

Editar `.env` y rellenar como mínimo:

- `APP_KEY`: generar con `php -r "echo 'base64:'.base64_encode(random_bytes(32));"`.
- `APP_URL`: el dominio público (`https://nomina.tudominio.com`).
- `DB_PASSWORD` y `MYSQL_ROOT_PASSWORD` (contraseñas fuertes y distintas).
- `REDIS_PASSWORD`.
- `MAIL_*` (SMTP para reset de contraseña y verificación de email).
- `PASSKEYS_USER_HANDLE_SECRET`: `openssl rand -base64 32`.

> `.env` está en `.gitignore`; nunca se sube al repositorio. Docker Compose lee
> este archivo automáticamente para interpolar `${...}`.

### 2.3 Construir e iniciar los contenedores

```bash
docker compose up -d --build
```

Esto levanta: `app` (PHP-FPM), `web` (Nginx), `db` (MySQL 8), `redis`,
`queue` (worker de colas), `scheduler` (cron) y `ssr` (Node).

### 2.4 Migraciones y estado

Las **migraciones se ejecutan automáticamente** en el arranque del contenedor
`app` (entrypoint). Para verificar el estado:

```bash
docker compose ps
docker compose logs -f app
docker compose exec app php artisan migrate:status
```

### 2.5 Permisos de storage

Los directorios de `storage/` y `bootstrap/cache/` se crean con propietario
`www-data` dentro de la imagen y persisten en el volumen `app-storage`. No se
requiere acción manual, pero si se monta un volumen preexistente:

```bash
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### 2.6 Configuración del túnel (cloudflared)

En el **host** (no en Docker):

```bash
# 1. Autenticar cloudflared con tu cuenta de Cloudflare
cloudflared tunnel login

# 2. Crear el túnel (guarda el <TUNNEL_ID>)
cloudflared tunnel create nominas-sm

# 3. Copiar la plantilla y ajustarla
cp cloudflared/config.yml.example ~/.cloudflared/config.yml
#   - sustituir <TUNNEL_ID>
#   - sustituir <IP_PRIVADA_DEL_SERVIDOR> por la IP privada/LAN del host
#   - ajustar el hostname y la ruta del credentials-file

# 4. Enrutar el DNS (asocia el hostname al túnel)
cloudflared tunnel route dns nominas-sm nomina.tudominio.com

# 5. Instalar como servicio de systemd y arrancar
sudo cloudflared service install
sudo systemctl enable --now cloudflared
```

Verifica que el túnel funciona accediendo a `https://nomina.tudominio.com`.

> El `service` del túnel apunta a `http://<IP_PRIVADA_DEL_SERVIDOR>:8080`
> (nginx en Docker). Sustituye `<IP_PRIVADA_DEL_SERVIDOR>` por la IP privada/LAN
> real del servidor.

#### Configuración desde el Dashboard de Cloudflare (alternativa)

Si gestionas el túnel desde el panel (Zero Trust → Networks → Tunnels), en la
configuración del **Public Hostname** define el *Service* (origen) como:

```text
http://<IP_PRIVADA_DEL_SERVIDOR>:8080
```

#### Nota de seguridad: firewall

Como nginx enlaza en `0.0.0.0`, el puerto `8080` quedaría alcanzable desde la
red local (y desde internet si el host tiene IP pública). Restringe el acceso
para que **solo cloudflared** llegue al origen. Ejemplos:

```bash
# ufw — permitir solo la IP privada del host y denegar el resto
sudo ufw allow from <IP_PRIVADA_DEL_SERVIDOR> to any port 8080
sudo ufw deny 8080

# firewalld
sudo firewall-cmd --permanent --add-rich-rule='rule family="ipv4" source address="<IP_PRIVADA_DEL_SERVIDOR>" port port="8080" protocol="tcp" accept'
sudo firewall-cmd --reload
```

Con el puerto restringido, `TRUSTED_PROXIES=0.0.0.0/0` (valor por defecto en
`.env.example.production`) es seguro y permite que Laravel recupere la IP real
del cliente para el rate-limiting.

---

## 3. Mantenimiento diario/operativo

### 3.1 Actualizaciones de código (deploy continuo)

Proceso sin tiempo de inactividad (zero downtime) apoyado en la re-creación de
contenedores uno a uno:

```bash
git pull origin main
docker compose up -d --build
```

Docker reconstruye la imagen y **reemplaza los contenedores** sin cortar el
servicio (salvo un instante de reconexión). Para forzar re-creación explícita:

```bash
docker compose up -d --build --force-recreate app queue scheduler ssr
```

Si una actualización incluye **nuevas migraciones**, el entrypoint del servicio
`app` las aplica automáticamente al arrancar. Para aplicarlas manualmente:

```bash
docker compose exec app php artisan migrate --force
```

> En producción `DB::prohibitDestructiveCommands` bloquea comandos destructivos
> (`migrate:fresh`, `migrate:rollback`, `db:wipe`, …). No forzarlos.

### 3.2 Gestión de logs

```bash
# Logs de todos los servicios (seguimiento en vivo)
docker compose logs -f

# Logs de un servicio concreto
docker compose logs -f app
docker compose logs -f queue

# Últimas 200 líneas con marcas de tiempo
docker compose logs --tail=200 -t app
```

Los logs de la aplicación también están en el volumen `app-storage`:

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

### 3.3 Tareas programadas y colas

- **Scheduler (cron)**: el contenedor `scheduler` ejecuta
  `php artisan schedule:run` cada minuto. Las tareas se definen en
  `routes/console.php` (actualmente vacío; añadir con `Schedule::command(...)`).
- **Colas**: el contenedor `queue` ejecuta `php artisan queue:work` (conexión
  Redis). Se reinicia solo (`restart: unless-stopped`).

Verificar el estado:

```bash
docker compose exec queue php artisan queue:monitor   # si hay jobs monitorizados
docker compose exec app php artisan queue:failed       # trabajos fallidos
docker compose exec app php artisan queue:retry all    # reintentar fallidos
```

### 3.4 Estrategia de respaldos

#### Base de datos (MySQL)

```bash
# Backup completo
docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers "$MYSQL_DATABASE"' \
  > backups/nominas_sm_$(date +%F_%H%M).sql

# Restaurar
docker compose exec -T db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  < backups/nominas_sm_2026-01-01_0000.sql
```

Automatiza el backup con un cron en el host (p. ej. diario) y rota/copia los
ficheros fuera del servidor.

#### Assets subidos (storage)

Los archivos sensibles viven en `storage/app/documentos`, los públicos (logos)
en `storage/app/public`, todo dentro del volumen `app-storage`. Respalda el
volumen con `tar`:

```bash
docker run --rm -v nominas-sm_app-storage:/data -v "$PWD/backups":/backup alpine \
  tar czf /backup/storage_$(date +%F_%H%M).tar.gz -C /data .
```

#### Redis (opcional)

Redis usa AOF (`--appendonly yes`); su persistencia está en el volumen
`redis-data`. Si solo se usa como cache/sesión, su respaldo no es crítico.

---

## 4. Desinstalación y limpieza

### 4.1 Detener los servicios (sin borrar datos)

```bash
docker compose down
```

Los volúmenes (`db-data`, `redis-data`, `app-storage`) se conservan.

### 4.2 Detener y eliminar contenedores + volúmenes

```bash
# Detener contenedores y eliminar volúmenes (¡borra la base de datos!)
docker compose down -v
```

> ⚠️ `-v` elimina `db-data`, `redis-data` y `app-storage`. Verifica que tienes
> respaldo antes de ejecutarlo.

### 4.3 Desmantelar el entorno por completo

```bash
# 1. Detener el túnel en el host
sudo systemctl disable --now cloudflared
sudo cloudflared tunnel delete nominas-sm

# 2. Eliminar contenedores, redes y volúmenes
docker compose down -v --rmi all

# 3. Eliminar las imágenes del proyecto
docker rmi nominas-sm/app nominas-sm/web nominas-sm/ssr

# 4. (Opcional) eliminar el código clonado
cd .. && rm -rf nominas-sm
```

### 4.4 Limpieza de artefactos Docker residuales

```bash
docker system prune -a --volumes   # imágenes, contenedores, redes y volúmenes no usados
```

---

## Referencias rápidas

| Acción | Comando |
|--------|---------|
| Ver estado | `docker compose ps` |
| Ver logs | `docker compose logs -f` |
| Reconstruir | `docker compose up -d --build` |
| Ejecutar artisan | `docker compose exec app php artisan ...` |
| Entrar a un contenedor | `docker compose exec app sh` |
| Backup BD | `docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup.sql` |
