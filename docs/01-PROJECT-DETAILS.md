# Project Details & Implementation

## Project Overview

**Leaseweb Server Explorer** is a full-stack web application designed to display and filter a catalog of dedicated servers. The project demonstrates modern software development practices including clean architecture, RESTful API design, comprehensive testing, and containerization.

---

## Technical Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client Browser                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Angular 17 SPA                               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Filters   │  │ Server List │  │      Pagination         │  │
│  │  Component  │  │  Component  │  │      Component          │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
│                              │                                   │
│                    ┌─────────────────┐                          │
│                    │  ServerService  │                          │
│                    └─────────────────┘                          │
└─────────────────────────────────────────────────────────────────┘
                              │ HTTP REST
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Symfony 7 API                                │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                   Controllers                            │    │
│  │   HomeController (/) → Redirects to /api/doc            │    │
│  │   ServerController (/api/servers, /api/filters)         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│  ┌─────────────────────┐    │    ┌─────────────────────────┐   │
│  │ ServerFilterService │◄───┴───►│    ServerRepository     │   │
│  └─────────────────────┘         └─────────────────────────┘   │
│                                              │                   │
│                              ┌───────────────────────────┐      │
│                              │      Server Entity        │      │
│                              └───────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │   SQLite DB     │
                    │  (486 servers)  │
                    └─────────────────┘
```

---

## Technology Stack

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| **Backend Framework** | Symfony | 7.x | PHP application framework |
| **Backend Language** | PHP | 8.2 | Server-side programming |
| **ORM** | Doctrine | 3.x | Database abstraction |
| **Database** | SQLite | 3.x | Data persistence |
| **Frontend Framework** | Angular | 17 | Single Page Application |
| **UI Library** | Angular Material | 17.x | Material Design components |
| **API Documentation** | NelmioApiDocBundle | 4.x | OpenAPI/Swagger |
| **Testing** | PHPUnit | 11.x | Unit & Functional tests |
| **Containerization** | Docker | Latest | Deployment |
| **Web Server** | Nginx | Alpine | HTTP server |

---

## Backend Implementation

### Directory Structure

```
backend/
├── src/
│   ├── Controller/
│   │   ├── HomeController.php          # Root redirect to API docs
│   │   └── Api/
│   │       └── ServerController.php    # REST endpoints
│   ├── Entity/
│   │   └── Server.php                  # Doctrine entity with OpenAPI schema
│   ├── Repository/
│   │   └── ServerRepository.php        # Data access layer with sorting
│   ├── Service/
│   │   └── ServerFilterService.php     # Business logic, filtering, sorting
│   └── DataFixtures/
│       └── ServerFixtures.php          # Database seeder
├── tests/
│   ├── Unit/
│   │   └── Service/
│   │       └── ServerFilterServiceTest.php
│   └── Functional/
│       └── Api/
│           └── ServerControllerTest.php
└── config/
    └── packages/
        ├── doctrine.yaml
        ├── nelmio_api_doc.yaml
        └── nelmio_cors.yaml
```

### Key Components

#### 1. HomeController (`src/Controller/HomeController.php`)

Handles the root URL and redirects users to the API documentation:

```php
#[Route('/', name: 'app_home')]
public function index(): Response
{
    return $this->redirect('/api/doc');
}
```

#### 2. Server Entity (`src/Entity/Server.php`)

The Server entity maps to the database with full OpenAPI schema annotations:

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Auto-generated primary key |
| `model` | string | Server model name (e.g., "Dell R210Intel Xeon X3440") |
| `ram` | string | RAM specification (e.g., "16GBDDR3") |
| `ramSizeGb` | int | Parsed RAM size for filtering |
| `hdd` | string | HDD specification (e.g., "2x2TBSATA2") |
| `storageTotalGb` | int | Calculated total storage for filtering |
| `hddType` | string | Disk type (SAS, SATA, SSD) |
| `location` | string | Server location |
| `price` | decimal | Monthly price |
| `currency` | string | Currency code (EUR, USD, SGD) |

The entity includes OpenAPI schema annotations for automatic Swagger documentation generation.

Database indexes are created on `ramSizeGb`, `storageTotalGb`, `hddType`, and `location` for optimized filtering queries.

#### 3. Server Repository (`src/Repository/ServerRepository.php`)

The repository implements:
- **`findByFilters()`**: Builds dynamic Doctrine queries based on filter and sorting parameters
- **`applySorting()`**: Applies dynamic sorting by price, RAM, storage, or model
- **`applyFilters()`**: Handles storage ranges, RAM, HDD type, location, and **price range** filters
- **`getDistinctLocations()`**: Returns unique locations for filter dropdown

#### 4. Server Filter Service (`src/Service/ServerFilterService.php`)

Business logic for:
- **Filter validation**: Ensures only valid filter values are processed
- **Storage range parsing**: Converts ranges like "0-250GB" to min/max values
- **RAM value parsing**: Extracts numeric values from strings
- **Price range validation**: Validates priceMin and priceMax parameters
- **Sorting extraction**: Validates sort field (price, ram, storage, model) and order (asc, desc)
- **Available filters**: Provides filter options to the frontend

**Constants defined:**
- `STORAGE_RANGES`: 12 predefined storage ranges
- `RAM_OPTIONS`: 11 RAM size options (2GB to 128GB)
- `HDD_TYPES`: SAS, SATA, SSD
- `SORT_FIELDS`: price, ram, storage, model
- `SORT_ORDERS`: asc, desc

#### 5. Server Controller (`src/Controller/Api/ServerController.php`)

REST API endpoints with comprehensive OpenAPI documentation:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/` | GET | Redirects to API documentation |
| `/api/servers` | GET | List servers with filters, sorting, and pagination |
| `/api/servers/{id}` | GET | Get single server details |
| `/api/filters` | GET | Get available filter options |
| `/api/doc` | GET | Swagger UI documentation |

**New Query Parameters:**
- `priceMin` - Minimum price filter
- `priceMax` - Maximum price filter
- `sort` - Sort field (price, ram, storage, model)
- `order` - Sort order (asc, desc)

---

## Frontend Implementation

### Directory Structure

```
frontend/src/app/
├── components/
│   ├── server-list/
│   │   ├── server-list.component.ts
│   │   ├── server-list.component.html
│   │   └── server-list.component.scss
│   ├── server-filters/
│   │   ├── server-filters.component.ts    # Range slider + sorting
│   │   ├── server-filters.component.html
│   │   └── server-filters.component.scss
│   └── pagination/
│       ├── pagination.component.ts
│       ├── pagination.component.html
│       └── pagination.component.scss
├── services/
│   └── server.service.ts
├── models/
│   └── server.model.ts                    # Includes SortOption interface
└── environments/
    ├── environment.ts
    └── environment.prod.ts
```

### Key Components

#### 1. ServerService (`services/server.service.ts`)

HTTP client service that:
- Constructs API requests with filter parameters
- Handles array parameters (storage[], ram[])
- **Includes sorting parameters (sort, order)**
- Provides observables for reactive data flow

#### 2. ServerFiltersComponent

Filter UI featuring:
- **Storage**: **Range slider** with discrete steps (0GB to 72TB)
- **RAM**: Checkbox grid for RAM sizes
- **HDD Type**: Dropdown selector
- **Location**: Dropdown selector
- **Sorting**: Dropdown for sort field with icons
- **Order**: Dropdown for ascending/descending
- **Clear Filters**: Reset all filters and sorting button
- **Active filter count**: Badge showing applied filters

**Storage Slider Steps:**
`0, 250GB, 500GB, 1TB, 2TB, 3TB, 4TB, 8TB, 12TB, 24TB, 48TB, 72TB`

**Sort Options:**
| Field | Icon | Description |
|-------|------|-------------|
| Price | attach_money | Sort by monthly price |
| RAM | memory | Sort by RAM size |
| Storage | storage | Sort by total storage |
| Model | dns | Sort by server model |

#### 3. ServerListComponent

Server display with:
- Responsive card grid layout
- Server specifications display
- Price formatting with multi-currency support (€, $, S$)
- Loading skeleton state
- Empty state message

#### 4. PaginationComponent

Pagination controls with:
- Page navigation (first, prev, next, last)
- Page size selector (10, 20, 50, 100)
- Results count display

#### 5. Models (`models/server.model.ts`)

TypeScript interfaces including:
- `Server` - Server data structure
- `PaginationMeta` - Includes sort and order fields
- `ServerFilters` - Includes sort and order fields
- `SortOption` - Sort dropdown option with icon

### Design System

The frontend uses a custom dark theme with CSS variables:

```scss
:root {
  --bg-primary: #0d1117;      // Main background
  --bg-secondary: #161b22;    // Cards background
  --accent-color: #00bcd4;    // Cyan accent
  --text-primary: #e6edf3;    // Primary text
  --text-secondary: #8b949e;  // Secondary text
  --border-color: #30363d;    // Borders
}
```

---

## Data Processing

### Excel Data Import

The original Excel file (`servers_filters_assignment.xlsx`) contains 486 servers with columns:
- Model, RAM, HDD, Location, Price

### Data Transformation

During fixture loading, the data is parsed and transformed:

1. **RAM Parsing**: "16GBDDR3" → `ramSizeGb: 16`
2. **Storage Calculation**: "2x2TBSATA2" → `storageTotalGb: 4000`
3. **HDD Type Extraction**: "4x480GBSSD" → `hddType: "SSD"`
4. **Price Cleaning**: "€49.99" → `price: 49.99`

---

## Filter Implementation

### Storage Ranges (Range Slider)

The storage filter uses a **dual-thumb range slider** with discrete steps:

| Step Index | Value | Display |
|------------|-------|---------|
| 0 | 0 GB | 0GB |
| 1 | 250 GB | 250GB |
| 2 | 500 GB | 500GB |
| 3 | 1,000 GB | 1TB |
| 4 | 2,000 GB | 2TB |
| 5 | 3,000 GB | 3TB |
| 6 | 4,000 GB | 4TB |
| 7 | 8,000 GB | 8TB |
| 8 | 12,000 GB | 12TB |
| 9 | 24,000 GB | 24TB |
| 10 | 48,000 GB | 48TB |
| 11 | 72,000 GB | 72TB |

### RAM Options (Checkboxes)

2GB, 4GB, 8GB, 12GB, 16GB, 24GB, 32GB, 48GB, 64GB, 96GB, 128GB

### HDD Types (Dropdown)

SAS, SATA, SSD

### Locations (Dropdown)

Dynamically loaded from database (Amsterdam, Dallas, Frankfurt, Hong Kong, San Francisco, Singapore, Washington D.C.)

### Price Range (API only)

`priceMin` and `priceMax` parameters for filtering by price range.

### Sorting

| Sort Field | Database Column | Description |
|------------|-----------------|-------------|
| `price` | `s.price` | Monthly price (default) |
| `ram` | `s.ramSizeGb` | RAM size in GB |
| `storage` | `s.storageTotalGb` | Total storage in GB |
| `model` | `s.model` | Server model name |

---

## Performance Optimizations

1. **Database Indexes**: Created on filter columns for fast queries
2. **Pagination**: Default 20 items per page, max 100
3. **Lazy Loading**: Angular chunks loaded on demand
4. **Gzip Compression**: Enabled in Nginx
5. **Static File Caching**: 1 year cache for assets in production
6. **OPcache**: Enabled in PHP for production
7. **Discrete Slider Steps**: Prevents unnecessary API calls with arbitrary values

---

## Security Measures

1. **CORS Configuration**: Restricted to allowed origins
2. **Input Validation**: All filter parameters validated
3. **SQL Injection Prevention**: Using Doctrine's parameterized queries
4. **XSS Protection**: Angular's built-in sanitization
5. **Security Headers**: X-Frame-Options, X-Content-Type-Options

---

## Testing Strategy

### Test Suite Summary

| Category | Tests | Assertions |
|----------|-------|------------|
| Unit Tests | 28 | 100+ |
| Functional Tests | 25 | 150+ |
| **Total** | **53** | **250+** |

### Unit Tests

**File**: `tests/Unit/Service/ServerFilterServiceTest.php`

Categories:
- Filter extraction (storage, RAM, HDD type, location, price range)
- Pagination validation
- **Sorting validation** (sort field, order)
- Constants verification

### Functional Tests

**File**: `tests/Functional/Api/ServerControllerTest.php`

Categories:
- API endpoint responses
- Filter combinations
- Pagination behavior
- **Sorting functionality** (all sort fields, both orders)
- **Price range filtering**
- Error handling

---

## Deployment Architecture

### Production Docker Build

Multi-stage Dockerfile:
1. **Stage 1**: Build Angular frontend
2. **Stage 2**: Install PHP dependencies with Composer
3. **Stage 3**: Combine into Nginx + PHP-FPM image

### Container Services

```
┌─────────────────────────────────────┐
│           Docker Container          │
│  ┌─────────────────────────────┐   │
│  │         Supervisor          │   │
│  └─────────────────────────────┘   │
│         │               │           │
│  ┌──────▼──────┐ ┌─────▼──────┐   │
│  │   Nginx     │ │  PHP-FPM   │   │
│  │   :8080     │ │    :9000   │   │
│  └─────────────┘ └────────────┘   │
│         │                          │
│  ┌──────▼──────────────────────┐  │
│  │   /var/www/html/public      │  │
│  │   - /app (Angular)          │  │
│  │   - /api (Symfony)          │  │
│  └─────────────────────────────┘  │
└─────────────────────────────────────┘
```

---

## API Features Summary

| Feature | Status | Description |
|---------|--------|-------------|
| Server Listing | ✅ | Paginated list of all servers |
| Storage Filter | ✅ | Range-based filtering |
| RAM Filter | ✅ | Multi-select filtering |
| HDD Type Filter | ✅ | Single-select filtering |
| Location Filter | ✅ | Single-select filtering |
| **Price Range Filter** | ✅ | Min/max price filtering |
| **Sorting** | ✅ | By price, RAM, storage, model |
| **Sort Order** | ✅ | Ascending/descending |
| Pagination | ✅ | Configurable page size |
| API Documentation | ✅ | Swagger UI at /api/doc |

---

## Conclusion

This project demonstrates:

- **Clean Architecture**: Separation of concerns with controllers, services, and repositories
- **Modern PHP**: Symfony 7, PHP 8.2 with typed properties and attributes
- **Modern Frontend**: Angular 17 with standalone components and RxJS
- **Enhanced UX**: Range slider for storage, sorting options, price filtering
- **DevOps Best Practices**: Docker, CI/CD ready, environment-based configuration
- **Quality Assurance**: Comprehensive test suite with PHPUnit (53+ tests)
- **Documentation**: OpenAPI/Swagger, README, and technical documentation
