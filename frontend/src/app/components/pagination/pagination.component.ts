import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { MatFormFieldModule } from '@angular/material/form-field';
import { PaginationMeta } from '../../models/server.model';

@Component({
  selector: 'app-pagination',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatButtonModule,
    MatIconModule,
    MatSelectModule,
    MatFormFieldModule,
  ],
  templateUrl: './pagination.component.html',
  styleUrls: ['./pagination.component.scss']
})
export class PaginationComponent {
  @Input() meta: PaginationMeta | null = null;
  @Output() pageChange = new EventEmitter<number>();
  @Output() limitChange = new EventEmitter<number>();

  pageSizeOptions = [10, 20, 50, 100];

  get startItem(): number {
    if (!this.meta) return 0;
    return (this.meta.page - 1) * this.meta.limit + 1;
  }

  get endItem(): number {
    if (!this.meta) return 0;
    return Math.min(this.meta.page * this.meta.limit, this.meta.total);
  }

  onPreviousPage(): void {
    if (this.meta && this.meta.hasPrevPage) {
      this.pageChange.emit(this.meta.page - 1);
    }
  }

  onNextPage(): void {
    if (this.meta && this.meta.hasNextPage) {
      this.pageChange.emit(this.meta.page + 1);
    }
  }

  onFirstPage(): void {
    this.pageChange.emit(1);
  }

  onLastPage(): void {
    if (this.meta) {
      this.pageChange.emit(this.meta.totalPages);
    }
  }

  onPageSizeChange(size: number): void {
    this.limitChange.emit(size);
  }
}

