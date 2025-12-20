import { Component, EventEmitter, Input, Output, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatCardModule } from '@angular/material/card';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatSelectModule } from '@angular/material/select';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatSliderModule } from '@angular/material/slider';
import { FilterOptions, ServerFilters, SortOption } from '../../models/server.model';

@Component({
  selector: 'app-server-filters',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatCardModule,
    MatCheckboxModule,
    MatSelectModule,
    MatFormFieldModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatSliderModule,
  ],
  templateUrl: './server-filters.component.html',
  styleUrls: ['./server-filters.component.scss']
})
export class ServerFiltersComponent implements OnInit {
  @Input() filterOptions: FilterOptions | null = null;
  @Output() filtersChange = new EventEmitter<Partial<ServerFilters>>();

  selectedStorage: string[] = [];
  selectedRam: string[] = [];
  selectedHddType: string = '';
  selectedLocation: string = '';
  selectedSort: string = 'price';
  selectedOrder: string = 'asc';

  // Sorting options
  readonly SORT_OPTIONS: SortOption[] = [
    { value: 'price', label: 'Price', icon: 'attach_money' },
    { value: 'ram', label: 'RAM', icon: 'memory' },
    { value: 'storage', label: 'Storage', icon: 'storage' },
    { value: 'model', label: 'Model', icon: 'dns' },
  ];

  readonly ORDER_OPTIONS = [
    { value: 'asc', label: 'Ascending', icon: 'arrow_upward' },
    { value: 'desc', label: 'Descending', icon: 'arrow_downward' },
  ];

  // Discrete storage values in GB: 0, 250GB, 500GB, 1TB, 2TB, 3TB, 4TB, 8TB, 12TB, 24TB, 48TB, 72TB
  readonly STORAGE_STEPS: readonly number[] = [0, 250, 500, 1000, 2000, 3000, 4000, 8000, 12000, 24000, 48000, 72000] as const;
  
  // Slider uses index-based values (0-11) to ensure discrete snapping
  storageMinIndex = 0;
  storageMaxIndex = 11; // Index of 72TB

  ngOnInit(): void {
    // Initialize slider to full range
    this.storageMaxIndex = this.STORAGE_STEPS.length - 1;
  }

  // Get the actual GB value from slider index
  getStorageGbFromIndex(index: number): number {
    return this.STORAGE_STEPS[Math.min(Math.max(0, index), this.STORAGE_STEPS.length - 1)];
  }

  // Get display value for min/max labels
  get storageMinValue(): number {
    return this.getStorageGbFromIndex(this.storageMinIndex);
  }

  get storageMaxValue(): number {
    return this.getStorageGbFromIndex(this.storageMaxIndex);
  }

  getStorageDisplayValue(value: number): string {
    if (value >= 1000) {
      return `${Math.round(value / 1000)}TB`;
    }
    return `${value}GB`;
  }

  formatStorageLabel = (index: number): string => {
    const value = this.getStorageGbFromIndex(index);
    if (value >= 1000) {
      return `${Math.round(value / 1000)}TB`;
    }
    return `${value}GB`;
  };

  getStorageMaxIndex(): number {
    return this.STORAGE_STEPS.length - 1;
  }

  onStorageSliderChange(): void {
    const minGb = this.getStorageGbFromIndex(this.storageMinIndex);
    const maxGb = this.getStorageGbFromIndex(this.storageMaxIndex);
    const isMaxPosition = this.storageMaxIndex === this.getStorageMaxIndex();
    
    // Convert slider range to matching storage option values
    this.selectedStorage = [];
    if (this.filterOptions?.storage) {
      for (const option of this.filterOptions.storage) {
        const optionMin = option.min;
        const optionMax = option.max; // null means unbounded (72TB+)

        // Range must start at or after selected minimum
        if (optionMin < minGb) {
          continue;
        }

        if (optionMax !== null) {
          // Bounded range: must end at or before selected maximum
          if (optionMax <= maxGb) {
            this.selectedStorage.push(option.value);
          }
        } else {
          // Unbounded range (72TB+): include if slider is at max position 
          // or if the range starts within/at the selected max
          if (isMaxPosition || optionMin <= maxGb) {
            this.selectedStorage.push(option.value);
          }
        }
      }
    }
    this.emitFilters();
  }

  onRamChange(value: string, checked: boolean): void {
    if (checked) {
      this.selectedRam.push(value);
    } else {
      this.selectedRam = this.selectedRam.filter(r => r !== value);
    }
    this.emitFilters();
  }

  onHddTypeChange(): void {
    this.emitFilters();
  }

  onLocationChange(): void {
    this.emitFilters();
  }

  onSortChange(): void {
    this.emitFilters();
  }

  onOrderChange(): void {
    this.emitFilters();
  }

  clearFilters(): void {
    this.selectedStorage = [];
    this.selectedRam = [];
    this.selectedHddType = '';
    this.selectedLocation = '';
    this.storageMinIndex = 0;
    this.storageMaxIndex = this.getStorageMaxIndex();
    this.selectedSort = 'price';
    this.selectedOrder = 'asc';
    this.emitFilters();
  }

  hasActiveFilters(): boolean {
    const hasStorageFilter = this.storageMinIndex > 0 || this.storageMaxIndex < this.getStorageMaxIndex();
    return hasStorageFilter ||
           this.selectedRam.length > 0 ||
           this.selectedHddType !== '' ||
           this.selectedLocation !== '';
  }

  getActiveFilterCount(): number {
    let count = 0;
    const hasStorageFilter = this.storageMinIndex > 0 || this.storageMaxIndex < this.getStorageMaxIndex();
    if (hasStorageFilter) count++;
    if (this.selectedRam.length > 0) count++;
    if (this.selectedHddType) count++;
    if (this.selectedLocation) count++;
    return count;
  }

  isRamSelected(value: string): boolean {
    return this.selectedRam.includes(value);
  }

  private emitFilters(): void {
    this.filtersChange.emit({
      storage: this.selectedStorage,
      ram: this.selectedRam,
      hddType: this.selectedHddType,
      location: this.selectedLocation,
      sort: this.selectedSort,
      order: this.selectedOrder,
    });
  }
}
