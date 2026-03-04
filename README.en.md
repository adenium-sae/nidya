# Nidya — Backend

> Point of sale (POS) and inventory management system for grocery stores. Open source project.

🌐 *[Leer en español](README.md)*

## What is Nidya?

Nidya is a comprehensive management system for grocery stores and similar businesses. It allows you to manage:

- 🏪 **Stores and branches** — "Concept Store" model (multi-tenant) with shared consolidated warehouses per branch
- 📦 **Product catalog** — With categories, variants, attributes, and images
- 📊 **Inventory** — Stock per warehouse, adjustments, inter-warehouse transfers, movements
- 💰 **Sales** — Point of sale with discounts, taxes, multiple payment methods
- 👥 **Users and roles** — Granular permission system by module
- 🏷️ **Customers** — Customer directory with history

## Stack

| Technology | Version |
|-----------|---------|
| PHP | ≥ 8.2 |
| Laravel | 12.x |
| PostgreSQL | — |
| Laravel Sanctum | 4.x |
| Pest | 4.x |

## Quick Setup

```bash
# Clone and install
git clone <repo>
cd nidya-backend
composer install
npm install

# Configure
cp .env.example .env
php artisan key:generate
# Edit .env with your PostgreSQL credentials

# Database
php artisan migrate:fresh --seed

# Start
composer run dev
```

The server will be available at `http://localhost:8000`.

### Demo User

| Field | Value |
|-------|-------|
| Email | Defined in `DemoDataSeeder` |
| Password | Defined in `DemoDataSeeder` |

## Documentation

- 📐 [Backend Architecture (ES)](docs/ARCHITECTURE.md) — Patterns, structure, data model, API
- 📐 [Backend Architecture (EN)](docs/ARCHITECTURE.en.md) — Patterns, structure, data model, API

## Available Scripts

```bash
composer run dev      # Development server (artisan serve + vite)
composer run test     # Run tests (Pest)
composer run setup    # Full installation (dependencies + migrations + build)
```

## Contributing

This is an open source project. Contributions are welcome.

1. Fork the repository
2. Create a branch for your feature (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -m 'Add: my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

### Code Conventions

- **PHP**: Laravel Pint (`vendor/bin/pint`)
- **Actions**: `VerbNounAction` — only operations that mutate the DB
- **Services**: `DomainService` — queries only + delegate to Actions
- **Controllers**: only inject Services, never Actions directly

## License

MIT
