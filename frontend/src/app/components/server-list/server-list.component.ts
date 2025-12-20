import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Server } from '../../models/server.model';

@Component({
  selector: 'app-server-list',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatIconModule,
    MatChipsModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './server-list.component.html',
  styleUrls: ['./server-list.component.scss']
})
export class ServerListComponent {
  @Input() servers: Server[] = [];
  @Input() loading = false;
  @Input() total = 0;

  getHddTypeClass(type: string): string {
    switch (type.toUpperCase()) {
      case 'SSD': return 'chip-ssd';
      case 'SAS': return 'chip-sas';
      case 'SATA': return 'chip-sata';
      default: return '';
    }
  }

  formatStorage(gb: number): string {
    if (gb >= 1000) {
      return (gb / 1000).toFixed(1).replace('.0', '') + ' TB';
    }
    return gb + ' GB';
  }

  formatRam(gb: number): string {
    return gb + ' GB';
  }

  getLocationDisplay(location: string): string {
    // Extract city name from location code like "AmsterdamAMS-01"
    const match = location.match(/^([A-Za-z\s]+)[A-Z]{3}-\d+$/);
    return match ? match[1] : location;
  }

  getLocationCode(location: string): string {
    const match = location.match(/([A-Z]{3}-\d+)$/);
    return match ? match[1] : '';
  }
}

