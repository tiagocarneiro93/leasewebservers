export interface Server {
  id: number;
  model: string;
  ram: string;
  ramSizeGb: number;
  hdd: string;
  storageTotalGb: number;
  hddType: string;
  location: string;
  price: string;
  currency: string;
  formattedPrice: string;
}

export interface PaginationMeta {
  total: number;
  page: number;
  limit: number;
  totalPages: number;
  hasNextPage: boolean;
  hasPrevPage: boolean;
  sort: string;
  order: string;
}

export interface ServerResponse {
  data: Server[];
  meta: PaginationMeta;
  filters: AppliedFilters;
}

export interface AppliedFilters {
  storage?: string[];
  ram?: string[];
  hddType?: string;
  location?: string;
}

export interface StorageOption {
  value: string;
  label: string;
  min: number;
  max: number | null;
}

export interface RamOption {
  value: string;
  label: string;
  sizeGb: number;
}

export interface FilterOptions {
  storage: StorageOption[];
  ram: RamOption[];
  hddType: string[];
  location: string[];
}

export interface FilterOptionsResponse {
  data: FilterOptions;
}

export interface ServerFilters {
  storage: string[];
  ram: string[];
  hddType: string;
  location: string;
  page: number;
  limit: number;
  sort: string;
  order: string;
}

export interface SortOption {
  value: string;
  label: string;
  icon: string;
}

