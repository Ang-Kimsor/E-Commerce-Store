<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesExport implements WithMultipleSheets
{
    use Exportable;
    
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new SalesReportSheet($this->data),
            new SalesOrderDetailsSheet($this->data)
        ];
    }
}
