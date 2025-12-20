# Client Requirements Documentation

This document addresses the specific requirements outlined in the technical assignment.

---

## Table of Contents

1. [Testing](#1-testing)
2. [Documentation on How to Setup the Project](#2-documentation-on-how-to-setup-the-project)
3. [Documentation for REST API Consumers](#3-documentation-for-rest-api-consumers)

---

# 1. Testing

## Test Suite Overview

The project includes a comprehensive test suite using PHPUnit, covering both unit and functional tests.

### Test Statistics

| Metric | Value |
|--------|-------|
| Total Tests | 53+ |
| Assertions | 250+ |
| Test Files | 2 |
| Pass Rate | 100% |
| Coverage Areas | Filter validation, Sorting, Price range, API endpoints, Pagination |

### Running Tests

```bash
cd backend

# Run all tests
php bin/phpunit

# Run with verbose output
php bin/phpunit --testdox

# Run specific test suite
php bin/phpunit --testsuite Unit
php bin/phpunit --testsuite Functional

# Generate code coverage report (requires Xdebug)
php bin/phpunit --coverage-html coverage/
```

### Expected Output

```
PHPUnit 11.5.46 by Sebastian Bergmann and contributors.

.....................................................     53 / 53 (100%)

Time: 00:00.650, Memory: 28.00 MB

Server Controller (App\Tests\Functional\Api\ServerController)
 ✔ Get servers returns json response
 ✔ Get servers returns expected structure
 ✔ Get servers returns paginated results
 ✔ Get servers second page
 ✔ Get servers with ram filter
 ✔ Get servers with hdd type filter
 ✔ Get servers with location filter
 ✔ Get servers with storage filter
 ✔ Get servers with multiple filters
 ✔ Get servers returns server properties
 ✔ Get single server returns correct data
 ✔ Get single server not found
 ✔ Get filters returns expected structure
 ✔ Get filters storage options
 ✔ Get filters ram options
 ✔ Get filters hdd types
 ✔ Get filters locations
 ✔ Get servers with sort by price
 ✔ Get servers with sort by price desc
 ✔ Get servers with sort by ram
 ✔ Get servers with sort by storage
 ✔ Get servers with sort by model
 ✔ Get servers default sorting
 ✔ Get servers with price range filter
 ✔ Get servers with price min only
 ✔ Get servers with price max only
 ✔ Get servers with price filter and sorting
 ✔ Get servers meta includes sort info

Server Filter Service (App\Tests\Unit\Service\ServerFilterService)
 ✔ Extract filters with empty request
 ✔ Extract filters with valid storage ranges
 ✔ Extract filters ignores invalid storage ranges
 ✔ Extract filters with valid ram values
 ✔ Extract filters ignores invalid ram values
 ✔ Extract filters with valid hdd type
 ✔ Extract filters with lowercase hdd type
 ✔ Extract filters ignores invalid hdd type
 ✔ Extract filters with location
 ✔ Extract pagination defaults
 ✔ Extract pagination with custom values
 ✔ Extract pagination enforces minimums
 ✔ Extract pagination enforces max limit
 ✔ Get available filters returns all options
 ✔ Storage range constants
 ✔ Ram options constants
 ✔ Hdd types constants
 ✔ Extract filters with price range
 ✔ Extract filters with price min only
 ✔ Extract filters ignores invalid price values
 ✔ Extract sorting defaults
 ✔ Extract sorting with valid values
 ✔ Extract sorting with storage field
 ✔ Extract sorting with model field
 ✔ Extract sorting ignores invalid sort field
 ✔ Extract sorting ignores invalid order
 ✔ Sort fields constants
 ✔ Sort orders constants

OK (53 tests, 250+ assertions)
```

---

## Unit Tests

**File**: `tests/Unit/Service/ServerFilterServiceTest.php`

Unit tests verify the business logic in isolation:

### Filter Extraction Tests

| Test | Description |
|------|-------------|
| `testExtractFiltersWithEmptyRequest` | Returns empty array when no filters provided |
| `testExtractFiltersWithValidStorageRanges` | Correctly extracts valid storage ranges |
| `testExtractFiltersIgnoresInvalidStorageRanges` | Filters out invalid storage values |
| `testExtractFiltersWithValidRamValues` | Correctly extracts valid RAM values |
| `testExtractFiltersIgnoresInvalidRamValues` | Filters out invalid RAM values |
| `testExtractFiltersWithValidHddType` | Extracts valid HDD type |
| `testExtractFiltersWithLowercaseHddType` | Normalizes lowercase HDD type to uppercase |
| `testExtractFiltersIgnoresInvalidHddType` | Ignores invalid HDD types |
| `testExtractFiltersWithLocation` | Extracts location filter |
| `testExtractFiltersWithPriceRange` | Extracts priceMin and priceMax |
| `testExtractFiltersWithPriceMinOnly` | Handles partial price filter |
| `testExtractFiltersIgnoresInvalidPriceValues` | Ignores invalid/negative prices |

### Pagination Tests

| Test | Description |
|------|-------------|
| `testExtractPaginationDefaults` | Returns page=1, limit=20 by default |
| `testExtractPaginationWithCustomValues` | Respects custom page and limit values |
| `testExtractPaginationEnforcesMinimums` | Enforces minimum page=1, limit=1 |
| `testExtractPaginationEnforcesMaxLimit` | Caps limit at 100 |

### Sorting Tests

| Test | Description |
|------|-------------|
| `testExtractSortingDefaults` | Returns sort=price, order=asc by default |
| `testExtractSortingWithValidValues` | Accepts valid sort field and order |
| `testExtractSortingWithStorageField` | Accepts 'storage' as sort field |
| `testExtractSortingWithModelField` | Accepts 'model' as sort field |
| `testExtractSortingIgnoresInvalidSortField` | Defaults invalid sort to 'price' |
| `testExtractSortingIgnoresInvalidOrder` | Defaults invalid order to 'asc' |
| `testSortFieldsConstants` | Verifies SORT_FIELDS constant |
| `testSortOrdersConstants` | Verifies SORT_ORDERS constant |

### Constants Tests

| Test | Description |
|------|-------------|
| `testGetAvailableFiltersReturnsAllOptions` | Returns all filter categories |
| `testStorageRangeConstants` | Verifies storage range definitions |
| `testRamOptionsConstants` | Verifies RAM option values |
| `testHddTypesConstants` | Verifies HDD type values |

---

## Functional Tests

**File**: `tests/Functional/Api/ServerControllerTest.php`

Functional tests verify API endpoints end-to-end:

### Server List Endpoint Tests

| Test | Description |
|------|-------------|
| `testGetServersReturnsJsonResponse` | Returns JSON content type |
| `testGetServersReturnsExpectedStructure` | Response has data, meta, filters keys |
| `testGetServersReturnsPaginatedResults` | Respects limit parameter |
| `testGetServersSecondPage` | Correctly returns page 2 |
| `testGetServersWithRamFilter` | Filters servers by RAM |
| `testGetServersWithHddTypeFilter` | Filters servers by HDD type |
| `testGetServersWithLocationFilter` | Filters servers by location |
| `testGetServersWithStorageFilter` | Filters servers by storage range |
| `testGetServersWithMultipleFilters` | Multiple filters work together |
| `testGetServersReturnsServerProperties` | Response includes all server fields |

### Sorting Tests

| Test | Description |
|------|-------------|
| `testGetServersWithSortByPrice` | Sorts by price ascending |
| `testGetServersWithSortByPriceDesc` | Sorts by price descending |
| `testGetServersWithSortByRam` | Sorts by RAM size |
| `testGetServersWithSortByStorage` | Sorts by storage capacity |
| `testGetServersWithSortByModel` | Sorts by model name |
| `testGetServersDefaultSorting` | Default is price ascending |
| `testGetServersMetaIncludesSortInfo` | Meta includes sort and order fields |

### Price Range Tests

| Test | Description |
|------|-------------|
| `testGetServersWithPriceRangeFilter` | Filters by priceMin and priceMax |
| `testGetServersWithPriceMinOnly` | Filters by minimum price only |
| `testGetServersWithPriceMaxOnly` | Filters by maximum price only |
| `testGetServersWithPriceFilterAndSorting` | Price filter + sorting combined |

### Single Server Endpoint Tests

| Test | Description |
|------|-------------|
| `testGetSingleServerReturnsCorrectData` | Returns correct server by ID |
| `testGetSingleServerNotFound` | Returns 404 for non-existent ID |

### Filters Endpoint Tests

| Test | Description |
|------|-------------|
| `testGetFiltersReturnsExpectedStructure` | Returns all filter categories |
| `testGetFiltersStorageOptions` | Storage options have correct structure |
| `testGetFiltersRamOptions` | RAM options have correct structure |
| `testGetFiltersHddTypes` | Returns SAS, SATA, SSD |
| `testGetFiltersLocations` | Returns location list |

---

# 2. Documentation on How to Setup the Project

## Quick Start (Docker - Recommended)

The fastest way to get the project running is with Docker.

### Prerequisites

- Docker Desktop 20.10+ ([Download](https://www.docker.com/products/docker-desktop))
- Git

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/YOUR_USERNAME/leaseweb-server-explorer.git
cd leaseweb-server-explorer

# 2. Start all services
docker-compose up -d

# 3. Wait for containers to start (30-60 seconds)
docker-compose logs -f

# 4. Access the application
# Frontend: http://localhost:4200
# API: http://localhost:8080/api/servers
# API Docs: http://localhost:8080/api/doc
# Root URL (/) redirects to API docs
```

### Docker Services

| Service | Port | Description |
|---------|------|-------------|
| `nginx` | 8080 | API web server |
| `php` | 9000 | PHP-FPM backend |
| `frontend` | 4200 | Angular dev server |

### Stopping the Application

```bash
docker-compose down
```

---

## Manual Setup (Without Docker)

### Prerequisites

- PHP 8.2+ with extensions: pdo_sqlite, intl, zip
- Composer 2.0+
- Node.js 18+ / npm 9+
- Git

### Backend Setup

```bash
# 1. Navigate to backend directory
cd backend

# 2. Install PHP dependencies
composer install

# 3. Create database and load data
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction

# 4. Start the development server
php -S localhost:8000 -t public

# Backend is now running at http://localhost:8000
# Visit http://localhost:8000 to be redirected to API docs
```

### Frontend Setup

```bash
# 1. Navigate to frontend directory (new terminal)
cd frontend

# 2. Install Node dependencies
npm install

# 3. Start the development server
npm start

# Frontend is now running at http://localhost:4200
```

### Verify Installation

| URL | Expected Result |
|-----|-----------------|
| http://localhost:4200 | Angular application loads |
| http://localhost:8000 | Redirects to /api/doc |
| http://localhost:8000/api/servers | JSON response with servers |
| http://localhost:8000/api/servers?sort=ram&order=desc | Servers sorted by RAM |
| http://localhost:8000/api/filters | JSON response with filters |
| http://localhost:8000/api/doc | Swagger documentation |

---

## Environment Configuration

### Backend Environment Variables

Create or modify `backend/.env`:

```env
# Application
APP_ENV=dev          # dev, test, or prod
APP_SECRET=your-secret-key-here

# Database
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# CORS (for frontend access)
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Frontend Environment

Edit `frontend/src/environments/environment.ts`:

```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api'  // Backend API URL
};
```

---

## Troubleshooting

### Common Issues

**Issue**: PHP command not found
```bash
# Windows: Add PHP to PATH or use full path
C:\xampp\php\php.exe -S localhost:8000 -t public
```

**Issue**: Composer not found
```bash
# Download and install from https://getcomposer.org
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php composer.phar install
```

**Issue**: npm install fails
```bash
# Clear npm cache and retry
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

**Issue**: CORS errors
```bash
# Ensure CORS_ALLOW_ORIGIN in .env includes your frontend URL
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

**Issue**: Database not found
```bash
cd backend
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
```

---

# 3. Documentation for REST API Consumers

## API Overview

**Base URL**: `http://localhost:8000/api` (development) or `https://your-app.up.railway.app/api` (production)

**Content Type**: `application/json`

**Interactive Documentation**: Available at `/api/doc` (Swagger UI)

**Root URL**: `/` redirects to `/api/doc`

---

## Authentication

Currently, the API is public and does not require authentication.

---

## Endpoints

### GET /api/servers

Returns a paginated list of servers with optional filters and sorting.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `storage[]` | array | No | Storage range filters |
| `ram[]` | array | No | RAM size filters |
| `hddType` | string | No | Disk type (SAS, SATA, SSD) |
| `location` | string | No | Server location |
| `priceMin` | number | No | Minimum price filter |
| `priceMax` | number | No | Maximum price filter |
| `sort` | string | No | Sort field (price, ram, storage, model) |
| `order` | string | No | Sort order (asc, desc) |
| `page` | integer | No | Page number (default: 1) |
| `limit` | integer | No | Items per page (default: 20, max: 100) |

#### Storage Range Values

- `0-250GB`
- `250GB-500GB`
- `500GB-1TB`
- `1TB-2TB`
- `2TB-3TB`
- `3TB-4TB`
- `4TB-8TB`
- `8TB-12TB`
- `12TB-24TB`
- `24TB-48TB`
- `48TB-72TB`
- `72TB+`

#### RAM Values

- `2GB`, `4GB`, `8GB`, `12GB`, `16GB`, `24GB`, `32GB`, `48GB`, `64GB`, `96GB`, `128GB`

#### Sort Fields

| Field | Description |
|-------|-------------|
| `price` | Sort by monthly price (default) |
| `ram` | Sort by RAM size in GB |
| `storage` | Sort by total storage in GB |
| `model` | Sort by server model name |

#### Sort Orders

| Order | Description |
|-------|-------------|
| `asc` | Ascending order (default) |
| `desc` | Descending order |

#### Example Requests

```bash
# Get all servers (first page, sorted by price ascending)
curl "http://localhost:8000/api/servers"

# Get servers with 64GB RAM
curl "http://localhost:8000/api/servers?ram[]=64GB"

# Get SSD servers in Amsterdam
curl "http://localhost:8000/api/servers?hddType=SSD&location=AmsterdamAMS-01"

# Get servers sorted by RAM (descending)
curl "http://localhost:8000/api/servers?sort=ram&order=desc"

# Get servers sorted by storage (ascending)
curl "http://localhost:8000/api/servers?sort=storage&order=asc"

# Get servers with price filter
curl "http://localhost:8000/api/servers?priceMin=50&priceMax=200"

# Get servers with multiple filters and sorting
curl "http://localhost:8000/api/servers?storage[]=1TB-2TB&ram[]=64GB&sort=price&order=asc"

# Pagination
curl "http://localhost:8000/api/servers?page=2&limit=50"
```

#### Response Structure

```json
{
  "data": [
    {
      "id": 1,
      "model": "Dell R210Intel Xeon X3440",
      "ram": "16GBDDR3",
      "ramSizeGb": 16,
      "hdd": "2x2TBSATA2",
      "storageTotalGb": 4000,
      "hddType": "SATA",
      "location": "AmsterdamAMS-01",
      "price": "49.99",
      "currency": "EUR",
      "formattedPrice": "€49.99"
    }
  ],
  "meta": {
    "total": 486,
    "page": 1,
    "limit": 20,
    "totalPages": 25,
    "hasNextPage": true,
    "hasPrevPage": false,
    "sort": "price",
    "order": "asc"
  },
  "filters": {
    "ram": ["16GB"],
    "hddType": "SATA"
  }
}
```

#### Response Fields

**Data Object**:

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique server identifier |
| `model` | string | Server model and processor |
| `ram` | string | RAM specification string |
| `ramSizeGb` | integer | RAM size in GB |
| `hdd` | string | HDD specification string |
| `storageTotalGb` | integer | Total storage in GB |
| `hddType` | string | Disk type (SAS, SATA, SSD) |
| `location` | string | Server location code |
| `price` | string | Monthly price |
| `currency` | string | Currency code |
| `formattedPrice` | string | Price with currency symbol |

**Meta Object**:

| Field | Type | Description |
|-------|------|-------------|
| `total` | integer | Total servers matching filters |
| `page` | integer | Current page number |
| `limit` | integer | Items per page |
| `totalPages` | integer | Total number of pages |
| `hasNextPage` | boolean | Whether next page exists |
| `hasPrevPage` | boolean | Whether previous page exists |
| `sort` | string | Current sort field |
| `order` | string | Current sort order |

---

### GET /api/servers/{id}

Returns details of a specific server.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Server ID (path parameter) |

#### Example Request

```bash
curl "http://localhost:8000/api/servers/15"
```

#### Success Response (200)

```json
{
  "data": {
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
}
```

#### Error Response (404)

```json
{
  "error": "Server not found",
  "status": 404
}
```

---

### GET /api/filters

Returns all available filter options.

#### Example Request

```bash
curl "http://localhost:8000/api/filters"
```

#### Response

```json
{
  "data": {
    "storage": [
      {
        "value": "0-250GB",
        "label": "0 - 250 GB",
        "min": 0,
        "max": 250
      },
      {
        "value": "250GB-500GB",
        "label": "250 - 500 GB",
        "min": 250,
        "max": 500
      }
      // ... more storage options
    ],
    "ram": [
      {
        "value": "2GB",
        "label": "2 GB",
        "sizeGb": 2
      },
      {
        "value": "4GB",
        "label": "4 GB",
        "sizeGb": 4
      }
      // ... more RAM options
    ],
    "hddType": ["SAS", "SATA", "SSD"],
    "location": [
      "AmsterdamAMS-01",
      "DallasDAL-10",
      "FrankfurtFRA-10",
      "Hong KongHKG-10",
      "San FranciscoSFO-12",
      "SingaporeSIN-11",
      "Washington D.C.WDC-01"
    ]
  }
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters |
| 404 | Not Found - Resource doesn't exist |
| 500 | Internal Server Error |

### Error Response Format

```json
{
  "error": "Error message description",
  "status": 404
}
```

---

## Rate Limiting

Currently, there are no rate limits on the API. For production deployments, consider implementing rate limiting based on your needs.

---

## Code Examples

### JavaScript (Fetch)

```javascript
// Get filtered and sorted servers
async function getServers(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.storage) {
    filters.storage.forEach(s => params.append('storage[]', s));
  }
  if (filters.ram) {
    filters.ram.forEach(r => params.append('ram[]', r));
  }
  if (filters.hddType) {
    params.set('hddType', filters.hddType);
  }
  if (filters.location) {
    params.set('location', filters.location);
  }
  if (filters.priceMin) {
    params.set('priceMin', filters.priceMin);
  }
  if (filters.priceMax) {
    params.set('priceMax', filters.priceMax);
  }
  if (filters.sort) {
    params.set('sort', filters.sort);
  }
  if (filters.order) {
    params.set('order', filters.order);
  }
  if (filters.page) {
    params.set('page', filters.page);
  }
  if (filters.limit) {
    params.set('limit', filters.limit);
  }

  const response = await fetch(`/api/servers?${params}`);
  return response.json();
}

// Usage - Get SSD servers sorted by RAM descending
const servers = await getServers({
  hddType: 'SSD',
  sort: 'ram',
  order: 'desc',
  limit: 20
});
```

### PHP (cURL)

```php
<?php
function getServers(array $filters = []): array {
    $params = [];
    
    if (!empty($filters['storage'])) {
        foreach ($filters['storage'] as $storage) {
            $params[] = 'storage[]=' . urlencode($storage);
        }
    }
    if (!empty($filters['ram'])) {
        foreach ($filters['ram'] as $ram) {
            $params[] = 'ram[]=' . urlencode($ram);
        }
    }
    if (!empty($filters['hddType'])) {
        $params[] = 'hddType=' . urlencode($filters['hddType']);
    }
    if (!empty($filters['location'])) {
        $params[] = 'location=' . urlencode($filters['location']);
    }
    if (isset($filters['priceMin'])) {
        $params[] = 'priceMin=' . $filters['priceMin'];
    }
    if (isset($filters['priceMax'])) {
        $params[] = 'priceMax=' . $filters['priceMax'];
    }
    if (!empty($filters['sort'])) {
        $params[] = 'sort=' . urlencode($filters['sort']);
    }
    if (!empty($filters['order'])) {
        $params[] = 'order=' . urlencode($filters['order']);
    }
    
    $url = 'http://localhost:8000/api/servers';
    if (!empty($params)) {
        $url .= '?' . implode('&', $params);
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Usage - Get servers between €50-€150 sorted by storage
$servers = getServers([
    'priceMin' => 50,
    'priceMax' => 150,
    'sort' => 'storage',
    'order' => 'desc'
]);
```

### Python (requests)

```python
import requests

def get_servers(filters=None):
    params = {}
    filters = filters or {}
    
    if 'storage' in filters:
        params['storage[]'] = filters['storage']
    if 'ram' in filters:
        params['ram[]'] = filters['ram']
    if 'hddType' in filters:
        params['hddType'] = filters['hddType']
    if 'location' in filters:
        params['location'] = filters['location']
    if 'priceMin' in filters:
        params['priceMin'] = filters['priceMin']
    if 'priceMax' in filters:
        params['priceMax'] = filters['priceMax']
    if 'sort' in filters:
        params['sort'] = filters['sort']
    if 'order' in filters:
        params['order'] = filters['order']
    if 'page' in filters:
        params['page'] = filters['page']
    if 'limit' in filters:
        params['limit'] = filters['limit']
    
    response = requests.get('http://localhost:8000/api/servers', params=params)
    return response.json()

# Usage - Get servers sorted by model name
servers = get_servers({
    'sort': 'model',
    'order': 'asc',
    'limit': 50
})
```

---

## OpenAPI Specification

The full OpenAPI 3.0 specification is available at:

- **Swagger UI**: `/api/doc`
- **JSON Spec**: `/api/doc.json`

You can import this specification into tools like Postman, Insomnia, or API development platforms.

---

## New Features Summary

| Feature | Endpoint | Parameters |
|---------|----------|------------|
| **Sorting** | GET /api/servers | `sort`, `order` |
| **Price Range** | GET /api/servers | `priceMin`, `priceMax` |
| **Root Redirect** | GET / | Redirects to /api/doc |
