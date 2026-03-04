# Nidya — Backend

> Sistema de punto de venta (POS) e inventario para negocios de abarrotes. Proyecto de código libre.

🌐 *[Read in English](README.en.md)*

## ¿Qué es Nidya?

Nidya es un sistema integral de gestión para tiendas de abarrotes y negocios similares. Permite administrar:

- 🏪 **Tiendas y sucursales** — Modelo "Concept Store" (multi-tienda) con base de almacenes compartida por sucursal
- 📦 **Catálogo de productos** — Con categorías, variantes, atributos e imágenes
- 📊 **Inventario** — Stock por almacén, ajustes, transferencias entre almacenes, movimientos
- 💰 **Ventas** — Punto de venta con descuentos, impuestos, múltiples métodos de pago
- 👥 **Usuarios y roles** — Sistema de permisos granulares por módulo
- 🏷️ **Clientes** — Directorio de clientes con historial
- 📋 **Bitácora** — Registro de auditoría de todas las operaciones que mutan la base de datos (creaciones, actualizaciones, eliminaciones, autenticación)

## Stack

| Tecnología | Versión |
|-----------|------|
| PHP | ≥ 8.2 |
| Laravel | 12.x |
| PostgreSQL | — |
| Laravel Sanctum | 4.x |
| Vue 3 + Vite | — |
| Pest | 4.x |

## Setup rápido

```bash
# Clonar e instalar
git clone <repo>
cd nidya-backend
composer install
npm install

# Configurar
cp .env.example .env
php artisan key:generate
# Editar .env con datos de tu PostgreSQL
# APP_URL=https://tudominio.com  (también controla el dominio que aparece en el sidebar)

# Base de datos
php artisan migrate:fresh --seed

# Iniciar
composer run dev
```

El servidor estará en `http://localhost:8000`.

### Usuario de demo

| Campo | Valor |
|-------|-------|
| Email | Definido en `DemoDataSeeder` |
| Password | Definido en `DemoDataSeeder` |

## Documentación

- 📐 [Arquitectura del backend (ES)](docs/ARCHITECTURE.md) — Patrones, estructura, modelo de datos, API
- 📐 [Backend Architecture (EN)](docs/ARCHITECTURE.en.md) — Patterns, structure, data model, API

## Scripts disponibles

```bash
composer run dev      # Servidor de desarrollo (artisan serve + vite)
composer run test     # Ejecutar tests (Pest)
composer run setup    # Instalación completa (dependencias + migraciones + build)
```

## Contribuir

Este es un proyecto de código libre. Las contribuciones son bienvenidas.

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/mi-feature`)
3. Commit tus cambios (`git commit -m 'Add: mi feature'`)
4. Push a la rama (`git push origin feature/mi-feature`)
5. Abre un Pull Request

### Convenciones de código

- **PHP**: Laravel Pint (`vendor/bin/pint`)
- **Actions**: `VerbNounAction` — solo operaciones que mutan la BD
- **Services**: `DomainService` — solo consultas + delegan a Actions
- **Controllers**: solo inyectan Services, nunca Actions directamente

## Licencia

MIT
