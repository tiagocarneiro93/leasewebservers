import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { 
  ServerResponse, 
  FilterOptionsResponse, 
  ServerFilters 
} from '../models/server.model';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ServerService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getServers(filters: Partial<ServerFilters>): Observable<ServerResponse> {
    let params = new HttpParams();

    // Add storage filters
    if (filters.storage && filters.storage.length > 0) {
      filters.storage.forEach(s => {
        params = params.append('storage[]', s);
      });
    }

    // Add RAM filters
    if (filters.ram && filters.ram.length > 0) {
      filters.ram.forEach(r => {
        params = params.append('ram[]', r);
      });
    }

    // Add HDD type filter
    if (filters.hddType) {
      params = params.set('hddType', filters.hddType);
    }

    // Add location filter
    if (filters.location) {
      params = params.set('location', filters.location);
    }

    // Add pagination
    if (filters.page) {
      params = params.set('page', filters.page.toString());
    }

    if (filters.limit) {
      params = params.set('limit', filters.limit.toString());
    }

    // Add sorting
    if (filters.sort) {
      params = params.set('sort', filters.sort);
    }

    if (filters.order) {
      params = params.set('order', filters.order);
    }

    return this.http.get<ServerResponse>(`${this.apiUrl}/servers`, { params });
  }

  getFilters(): Observable<FilterOptionsResponse> {
    return this.http.get<FilterOptionsResponse>(`${this.apiUrl}/filters`);
  }
}

