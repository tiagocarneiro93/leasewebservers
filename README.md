# Leaseweb Server Explorer

A full-stack application for browsing and filtering dedicated servers with a modern REST API and Angular frontend.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)
![Symfony](https://img.shields.io/badge/Symfony-7.0-000000?logo=symfony)
![Angular](https://img.shields.io/badge/Angular-17-DD0031?logo=angular)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)

## 🚀 Features

- **RESTful API** built with Symfony 7
- **Modern Angular 17 SPA** with Material Design
- **Advanced Filtering**: Storage (range slider), RAM, Disk Type, Location
- **Sorting**: By price, RAM, storage, or model (ascending/descending)
- **Price Range Filter**: Filter by minimum and maximum price
- **Pagination** with customizable page sizes
- **Docker** support for development and production
- **OpenAPI/Swagger** documentation (root URL redirects to docs)
- **Comprehensive test suite** (53+ tests) with PHPUnit

## 📋 Requirements

### For Docker Setup (Recommended)
- Docker Desktop 20.10+
- Docker Compose 2.0+

### For Local Development
- PHP 8.2+
- Composer 2.0+
- Node.js 18+ / npm 9+
- SQLite 3

## 🏃 Quick Start with Docker

```bash
# Clone the repository
git clone <repository-url>
cd leaseweb-servers

# Start all services
docker-compose up -d

# Wait for services to start, then access:
# - Frontend: http://localhost:4200
# - API: http://localhost:8080/api/servers
# - API Docs: http://localhost:8080/api/doc (or just http://localhost:8080)
```

## 🛠️ Local Development Setup

### Backend (Symfony API)

```bash
cd backend

# Install dependencies
composer install

# Configure database (SQLite by default)
# Edit .env if needed: DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# Create database and load data
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction

# Start the development server
php -S localhost:8000 -t public
```

### Frontend (Angular)

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm start

# Open http://localhost:4200
```

## 📖 API Documentation

### Base URL
```
http://localhost:8000/api
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Redirects to API documentation |
| GET | `/api/servers` | List servers with filters and sorting |
| GET | `/api/servers/{id}` | Get server details |
| GET | `/api/filters` | Get available filter options |
| GET | `/api/doc` | Swagger UI documentation |

### Query Parameters for `/api/servers`

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `storage[]` | array | Storage ranges | `?storage[]=0-250GB&storage[]=500GB-1TB` |
| `ram[]` | array | RAM sizes | `?ram[]=16GB&ram[]=32GB` |
| `hddType` | string | Disk type (SAS, SATA, SSD) | `?hddType=SSD` |
| `location` | string | Server location | `?location=AmsterdamAMS-01` |
| `priceMin` | number | Minimum price (EUR) | `?priceMin=50` |
| `priceMax` | number | Maximum price (EUR) | `?priceMax=200` |
| `sort` | string | Sort field (price, ram, storage, model) | `?sort=ram` |
| `order` | string | Sort order (asc, desc) | `?order=desc` |
| `page` | int | Page number (default: 1) | `?page=2` |
| `limit` | int | Items per page (default: 20, max: 100) | `?limit=50` |

### Example Requests

```bash
# Get servers sorted by RAM (descending)
curl "http://localhost:8000/api/servers?sort=ram&order=desc"

# Get servers within a price range
curl "http://localhost:8000/api/servers?priceMin=50&priceMax=150"

# Get SSD servers with 64GB RAM, sorted by price
curl "http://localhost:8000/api/servers?ram[]=64GB&hddType=SSD&sort=price&order=asc"
```

### Example Response

```json
{
  "data": [
    {
      "id": 15,
      "model": "HP DL180 G92x Intel Xeon E5-2620v3",
      "ram": "64GBDDR4",
      "ramSizeGb": 64,
      "hdd": "2x120GBSSD",
      "storageTotalGb": 240,
      "hddType": "SSD",
      "location": "AmsterdamAMS-01",
      "price": "199.99",
      "currency": "EUR",
      "formattedPrice": "€199.99"
    }
  ],
  "meta": {
    "total": 87,
    "page": 1,
    "limit": 20,
    "totalPages": 5,
    "hasNextPage": true,
    "hasPrevPage": false,
    "sort": "price",
    "order": "asc"
  },
  "filters": {
    "ram": ["64GB"],
    "hddType": "SSD"
  }
}
```

## 🧪 Running Tests

```bash
cd backend

# Run all tests
php bin/phpunit

# Run with verbose output
php bin/phpunit --testdox

# Run with coverage
php bin/phpunit --coverage-html coverage

# Run specific test suite
php bin/phpunit --testsuite Unit
php bin/phpunit --testsuite Functional
```

### Test Summary

| Category | Tests | Description |
|----------|-------|-------------|
| Unit Tests | 28 | Filter validation, sorting, pagination |
| Functional Tests | 25 | API endpoints, filters, sorting, price range |
| **Total** | **53+** | **250+ assertions** |

## 📁 Project Structure

```
leaseweb-servers/
├── backend/                    # Symfony 7 API
│   ├── src/
│   │   ├── Controller/
│   │   │   ├── HomeController.php    # Root redirect
│   │   │   └── Api/
│   │   │       └── ServerController.php
│   │   ├── Entity/             # Doctrine entities with OpenAPI schema
│   │   ├── Repository/         # Data access with sorting
│   │   ├── Service/            # Business logic, filter & sort validation
│   │   └── DataFixtures/       # Database seeders
│   ├── tests/
│   │   ├── Unit/               # Unit tests (28 tests)
│   │   └── Functional/         # API tests (25 tests)
│   └── config/                 # Symfony config
├── frontend/                   # Angular 17 SPA
│   └── src/
│       ├── app/
│       │   ├── components/
│       │   │   ├── server-list/       # Server cards display
│       │   │   ├── server-filters/    # Range slider + sorting
│       │   │   └── pagination/        # Page navigation
│       │   ├── services/       # API services
│       │   └── models/         # TypeScript interfaces
│       └── environments/       # Environment configs
├── docker/                     # Docker configs
│   ├── nginx/
│   ├── php/
│   └── supervisor/
├── docs/                       # Documentation
│   ├── 01-PROJECT-DETAILS.md
│   ├── 02-RAILWAY-DEPLOYMENT.md
│   ├── 03-DEMO-RUNBOOK.md
│   └── 04-CLIENT-REQUIREMENTS.md
├── docker-compose.yml          # Development setup
├── Dockerfile                  # Production build
└── README.md
```

## 🚀 Deployment

### Railway Deployment

1. Connect your GitHub repository to Railway
2. Railway will auto-detect the Dockerfile
3. Set environment variables:
   - `APP_ENV=prod`
   - `APP_SECRET=<generate-secret>`
4. Deploy!

### Manual Production Build

```bash
# Build the Docker image
docker build -t leaseweb-servers .

# Run the container
docker run -p 8080:8080 leaseweb-servers

# Access at http://localhost:8080
```

## 🔧 Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment (dev/prod) | `dev` |
| `APP_SECRET` | Symfony secret key | - |
| `DATABASE_URL` | Database connection | SQLite |
| `CORS_ALLOW_ORIGIN` | CORS origins | `localhost` |

## 📊 Filter Options

### Storage Ranges (Range Slider)
0 GB → 250 GB → 500 GB → 1 TB → 2 TB → 3 TB → 4 TB → 8 TB → 12 TB → 24 TB → 48 TB → 72 TB

### RAM Options (Checkboxes)
2 GB, 4 GB, 8 GB, 12 GB, 16 GB, 24 GB, 32 GB, 48 GB, 64 GB, 96 GB, 128 GB

### Disk Types (Dropdown)
SAS, SATA, SSD

### Sorting Options
| Field | Description |
|-------|-------------|
| `price` | Monthly price (default) |
| `ram` | RAM size in GB |
| `storage` | Total storage in GB |
| `model` | Server model name |

## 👨‍💻 Author

Tiago - Leaseweb Technical Assignment

## 📄 License

This project is created for demonstration purposes as part of Leaseweb's technical evaluation.
