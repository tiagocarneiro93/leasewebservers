import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatSidenavModule } from '@angular/material/sidenav';
import { ServerListComponent } from './components/server-list/server-list.component';
import { ServerFiltersComponent } from './components/server-filters/server-filters.component';
import { PaginationComponent } from './components/pagination/pagination.component';
import { ServerService } from './services/server.service';
import { 
  Server, 
  FilterOptions, 
  PaginationMeta, 
  ServerFilters 
} from './models/server.model';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    CommonModule,
    MatToolbarModule,
    MatIconModule,
    MatButtonModule,
    MatSidenavModule,
    ServerListComponent,
    ServerFiltersComponent,
    PaginationComponent,
  ],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {
  servers: Server[] = [];
  filterOptions: FilterOptions | null = null;
  meta: PaginationMeta | null = null;
  loading = false;
  error: string | null = null;

  currentFilters: Partial<ServerFilters> = {
    storage: [],
    ram: [],
    hddType: '',
    location: '',
    page: 1,
    limit: 20,
    sort: 'price',
    order: 'asc',
  };

  sidenavOpen = true;

  constructor(private serverService: ServerService) {}

  ngOnInit(): void {
    this.loadFilters();
    this.loadServers();
  }

  loadFilters(): void {
    this.serverService.getFilters().subscribe({
      next: (response) => {
        this.filterOptions = response.data;
      },
      error: (err) => {
        console.error('Failed to load filters:', err);
      }
    });
  }

  loadServers(): void {
    this.loading = true;
    this.error = null;

    this.serverService.getServers(this.currentFilters).subscribe({
      next: (response) => {
        this.servers = response.data;
        this.meta = response.meta;
        this.loading = false;
      },
      error: (err) => {
        console.error('Failed to load servers:', err);
        this.error = 'Failed to load servers. Please try again.';
        this.loading = false;
      }
    });
  }

  onFiltersChange(filters: Partial<ServerFilters>): void {
    this.currentFilters = {
      ...this.currentFilters,
      ...filters,
      page: 1, // Reset to first page when filters change
    };
    this.loadServers();
  }

  onPageChange(page: number): void {
    this.currentFilters.page = page;
    this.loadServers();
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  onLimitChange(limit: number): void {
    this.currentFilters.limit = limit;
    this.currentFilters.page = 1; // Reset to first page
    this.loadServers();
  }

  toggleSidenav(): void {
    this.sidenavOpen = !this.sidenavOpen;
  }
}
