<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Temporary export used while incident data exports are suspended.
 *
 * Replace this export with IncidentsWorkbookExport in the controller to
 * restore the existing data export behavior.
 */
class EmptyIncidentsWorkbookExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [new EmptyIncidentsSheet];
    }
}

class EmptyIncidentsSheet implements FromCollection, WithTitle
{
    public function collection(): Collection
    {
        return collect();
    }

    public function title(): string
    {
        return 'Alertes';
    }
}
