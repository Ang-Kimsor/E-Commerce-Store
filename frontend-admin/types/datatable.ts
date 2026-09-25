export interface Column {
  key: string;
  label: string;
  align?: 'left' | 'center' | 'right';
  width?: string;
  class?: (item: any) => string;
  sortable?: boolean;
}

export interface PaginationData {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
