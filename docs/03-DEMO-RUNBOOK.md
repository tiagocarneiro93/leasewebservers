# Demo Runbook

**Presentation Guide for Leaseweb Technical Assignment**

This runbook will guide you through demonstrating the Leaseweb Server Explorer application and explaining the technical approach.

---

## Demo Overview

**Duration**: 15-20 minutes
**Format**: Live demo + Technical explanation

### Agenda
1. **Introduction** (2 min) - Project overview
2. **Live Demo** (8 min) - Feature walkthrough
3. **Technical Deep Dive** (5 min) - Architecture and code highlights
4. **Q&A** (5 min) - Questions and discussion

---

## Pre-Demo Checklist

### Before the Demo

- [ ] Application is deployed and accessible
- [ ] Tested all features work correctly
- [ ] Browser cache cleared (fresh experience)
- [ ] Code editor ready with key files open
- [ ] API documentation page loaded
- [ ] Terminal ready for commands
- [ ] Backup: Local version running (in case of network issues)

### Have Ready

1. **Live URL**: `https://[your-app].up.railway.app`
2. **API Doc URL**: `https://[your-app].up.railway.app/api/doc`
3. **GitHub Repository**: `https://github.com/[your-username]/leaseweb-server-explorer`
4. **Local backup**: `docker-compose up -d` (if needed)

---

## Part 1: Introduction (2 minutes)

### Opening Statement

> "Thank you for the opportunity to present my technical assignment. I've built a full-stack application called **Leaseweb Server Explorer** - a REST API and web interface for browsing and filtering dedicated servers.
>
> The application is built with **Symfony 7** for the backend API and **Angular 17** for the frontend, following modern development practices including comprehensive testing, Docker containerization, and deployment to Railway.
>
> I've also implemented additional features beyond the requirements, including **sorting capabilities** and a **price range filter**."

### Key Points to Mention

- Processed the Excel data (486 servers) into a structured database
- Implemented all required filter functionality plus enhancements
- Added sorting by price, RAM, storage, and model name
- Created comprehensive documentation
- Deployed to a live environment

---

## Part 2: Live Demo (8 minutes)

### 2.1 First Impression (1 min)

**Action**: Open the application in browser

> "Here's the live application. You can see a modern, dark-themed interface displaying server listings. The design is inspired by datacenter and cloud provider dashboards."

**Point out**:
- Header with server count (486 servers available)
- Collapsible filter panel on the left
- Server cards with specifications
- Pagination at the bottom
- **New**: Sorting controls in the filter panel

---

### 2.2 Server Display (1 min)

**Action**: Scroll through servers, hover over cards

> "Each server card displays:
> - The server model and processor
> - RAM and storage specifications
> - Disk type with color-coded badges (SSD in green, SATA in orange, SAS in blue)
> - Location
> - Monthly price with currency symbol
>
> Servers are sorted by price by default, showing the most affordable options first."

---

### 2.3 Storage Filter - Range Slider (1 min)

**Action**: Drag the storage range slider

> "One of the improvements I made is changing the storage filter from checkboxes to a **range slider**. This provides a more intuitive way to filter by storage capacity.
>
> The slider has discrete steps matching the specification: 0GB, 250GB, 500GB, 1TB, up to 72TB.
>
> Let me filter for servers with 1TB to 8TB of storage."

**Action**: Drag min thumb to 1TB, max thumb to 8TB

> "Notice how the server count updates immediately. The current range is shown above the slider."

---

### 2.4 Other Filters (1.5 min)

#### RAM Filter

**Action**: Select "64GB" and "128GB" RAM options

> "The RAM filter uses checkboxes for multi-selection. I can select 64GB and 128GB servers."

**Show**: All displayed servers now show 64GB or 128GB RAM

#### HDD Type Filter

**Action**: Select "SSD" from dropdown

> "The disk type filter uses a dropdown. Let me filter for SSD servers only."

**Show**: All servers now show SSD badge

#### Location Filter

**Action**: Select "AmsterdamAMS-01"

> "I can also filter by datacenter location. Let me select Amsterdam."

#### Combined Filters

**Action**: Point out the active filter badge

> "Notice the filter panel shows a badge with the number of active filters. All filters work together - we're now seeing high-RAM, SSD, Amsterdam servers with medium storage."

---

### 2.5 Sorting Feature (1.5 min)

**Action**: Navigate to sorting controls

> "I've added a **sorting feature** that wasn't in the original requirements. Users can sort servers by different criteria."

**Action**: Change sort from "Price" to "RAM"

> "Let me sort by RAM instead of price. Now the servers with the highest RAM are shown first... or if I want the lowest RAM first, I can change the order to ascending."

**Action**: Change sort to "Storage", order to "Descending"

> "I can also sort by storage capacity. This shows the servers with the most storage first."

**Action**: Return sort to "Price" ascending

> "This sorting feature makes it much easier for customers to find exactly what they need."

---

### 2.6 Clear Filters (30 sec)

**Action**: Click "Clear All"

> "I can reset all filters with one click to return to the full list. This also resets the sorting to the default."

---

### 2.7 Pagination (30 sec)

**Action**: Navigate pages, change page size

> "The pagination allows navigation through all 486 servers. I can:
> - Navigate to specific pages
> - Change items per page (10, 20, 50, or 100)
> - See the total result count"

**Show**: Change to 50 items per page

---

### 2.8 API Documentation (1 min)

**Action**: Open `/api/doc` in new tab (or note that root `/` redirects there)

> "I've included comprehensive API documentation using OpenAPI/Swagger. When you visit the root URL, it automatically redirects to the API documentation at `/api/doc`."

**Walk through**:
1. **Servers endpoint** - Show all parameters including sort, order, priceMin, priceMax
2. **Try it out** - Execute a live API call
3. **Response** - Show JSON structure with sort/order in meta

> "API consumers can use this documentation to understand how to integrate with the API. They can even test endpoints directly from this interface."

**Action**: Execute GET /api/servers with sort=ram&order=desc

---

## Part 3: Technical Deep Dive (5 minutes)

### 3.1 Architecture Overview

**Visual**: Show architecture diagram or describe verbally

> "The architecture follows a clean separation of concerns:
>
> **Backend** (Symfony 7):
> - Controllers handle HTTP requests (HomeController redirects to docs)
> - Service layer contains business logic (filter validation, sorting)
> - Repository handles database queries with dynamic query building
> - Entity with full OpenAPI schema annotations
>
> **Frontend** (Angular 17):
> - Standalone components for modularity
> - Service handles API communication with all filter/sort params
> - Reactive approach with RxJS observables
> - Material Design range slider for storage"

---

### 3.2 Code Highlights

**Action**: Open IDE/GitHub with prepared files

#### Filter Service with Sorting

```php
// Show ServerFilterService.php - extractSorting method
public function extractSorting(Request $request): array
{
    $sort = strtolower($request->query->get('sort', 'price'));
    $order = strtolower($request->query->get('order', 'asc'));
    // Validation...
}
```

> "The filter service now includes sorting extraction. It validates the sort field against allowed values and defaults to price ascending."

#### Repository with Dynamic Sorting

```php
// Show ServerRepository.php - applySorting method
private function applySorting(QueryBuilder $qb, array $sorting): void
{
    $sortFieldMap = [
        'price' => 's.price',
        'ram' => 's.ramSizeGb',
        'storage' => 's.storageTotalGb',
        'model' => 's.model',
    ];
    // ...
}
```

> "The repository applies dynamic sorting based on the requested field. This maps user-friendly names to actual database columns."

#### Test Suite

**Action**: Show test output or test files

> "I've significantly expanded the test suite to cover the new features. There are now **53+ tests** covering:
> - Filter validation (including price range)
> - Sorting functionality (all fields, both orders)
> - API responses
> 
> All tests pass - you can run them with `php bin/phpunit`."

---

### 3.3 DevOps & Deployment

> "For deployment, I used Docker with a multi-stage build:
> - Stage 1 builds the Angular frontend
> - Stage 2 installs PHP dependencies
> - Stage 3 combines everything into a production image
>
> The root URL automatically redirects to API documentation, making it easy for developers to discover the API."

---

### 3.4 Key Technical Decisions

> "A few key decisions I'd like to highlight:
>
> 1. **Range Slider vs Checkboxes**: Changed storage filter to a range slider for better UX
>
> 2. **Sorting Feature**: Added sorting capability as it's essential for real-world use
>
> 3. **Price Range Filter**: API supports priceMin/priceMax for future frontend implementation
>
> 4. **OpenAPI Schema on Entity**: Full schema annotations for better documentation
>
> 5. **Home Route Redirect**: Root URL redirects to API docs for developer convenience"

---

## Part 4: Addressing Requirements

Explicitly mention how each requirement was met:

### Testing ✅
> "The project includes **53+ PHPUnit tests** with comprehensive coverage of all features including the new sorting and price filtering."

### Documentation for Setup ✅
> "The README includes complete setup instructions for both Docker and manual installation. Anyone can clone and run the project with minimal effort."

### API Documentation ✅
> "API consumers have access to Swagger UI documentation with interactive examples, including the new sorting and price range parameters."

### Bonus Points

| Bonus | Implementation |
|-------|----------------|
| Code Quality | PSR-12 standards, typed PHP 8.2, clean architecture |
| Readability | Meaningful names, documented methods, consistent style |
| Application Structure | Layered architecture, separation of concerns |
| User Interface | Dark theme, range slider, sorting controls, responsive |
| Optimization | Database indexes, pagination, gzip compression |

### Beyond Requirements

| Feature | Benefit |
|---------|---------|
| **Sorting** | Users can sort by price, RAM, storage, or model |
| **Price Range Filter** | API ready for future price slider on frontend |
| **Range Slider** | More intuitive storage filtering |
| **Root Redirect** | Better developer experience |

---

## Handling Questions

### Anticipated Questions & Answers

**Q: Why did you add sorting when it wasn't required?**
> "Sorting is essential for a good user experience in any listing application. Users need to find the cheapest server, the one with most RAM, or most storage. It demonstrates that I think about real-world usage, not just meeting minimum requirements."

**Q: Why a range slider instead of checkboxes for storage?**
> "A range slider provides a more intuitive way to filter by a continuous value like storage. Users think in terms of 'I need at least 2TB' rather than checking multiple boxes. It also reduces clutter in the UI."

**Q: Why Symfony over Laravel?**
> "The job description mentioned Symfony as preferred. Additionally, Symfony 7 offers excellent typing support, a mature ecosystem, and follows industry best practices that align well with enterprise development."

**Q: How would you scale this application?**
> "Several approaches:
> 1. Database: Switch to PostgreSQL with read replicas
> 2. Caching: Add Redis for frequently accessed data
> 3. CDN: Serve static assets via CDN
> 4. Horizontal scaling: Railway supports multiple replicas"

**Q: What would you improve given more time?**
> "I would add:
> - Price range slider on the frontend
> - Server comparison feature
> - Save filter preferences
> - Full-text search for model names
> - Performance monitoring"

---

## Closing Statement

> "Thank you for your time. This project demonstrates my ability to:
> - Build full-stack applications with PHP/Symfony and Angular
> - Go beyond requirements to deliver better user experiences
> - Follow clean architecture principles
> - Write comprehensive tests (53+ tests)
> - Create professional documentation
> - Deploy using modern DevOps practices
>
> I'm happy to dive deeper into any aspect of the implementation or answer any questions you might have."

---

## Emergency Procedures

### If Live Demo Fails

1. **Have local version ready**:
   ```bash
   cd leaseweb-servers
   docker-compose up -d
   # Open http://localhost:4200
   ```

2. **Use screenshots/video** as backup

3. **Focus on code walkthrough** instead

### If Specific Feature Breaks

- Skip to next feature
- Acknowledge: "Let me show you that in the code instead"
- Continue with technical explanation

---

## Presentation Tips

1. **Speak clearly** and at a measured pace
2. **Face the camera/audience**, not just the screen
3. **Narrate actions** as you perform them
4. **Pause for questions** at natural breakpoints
5. **Be confident** - you built this, you know it well!
6. **Highlight the extras** - sorting, range slider, price filter
7. **Time yourself** in practice runs

**Good luck with your demo!** 🚀
