<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\FormatsWorksheetAsTable;
use App\Exports\IncidentExportFilters;
use App\Exports\Support\VictimDemographics;
use App\Models\Victime;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class VictimesSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    use FormatsWorksheetAsTable;

    private IncidentExportFilters $filters;

    public function __construct(
        public string $from,
        public string $to,
        public ?string $province = null,
        public ?string $territoire = null,
    ) {
        $this->filters = new IncidentExportFilters($from, $to, $province, $territoire);
    }

    public function title(): string
    {
        return 'Victimes';
    }

    public function headings(): array
    {
        return [
            'code_incident',
            'date_incident',
            'province',
            'territoire',
            'zone_sante',
            'aire_sante',
            'violence_id',
            'violence',
            'categorie_violence',
            'profil_victimes',
            ...VictimDemographics::headings(),
            'total_victimes',
            'description_faits',
            'cree_par',
            'cree_le',
            'victime_id',
            'incident_id',
        ];
    }

    public function collection(): Collection
    {
        return Victime::query()
            ->whereHas('incident', fn ($query) => $this->filters->applyToRelatedIncidentQuery($query))
            ->with(['incident.province', 'incident.territoire', 'incident.zoneSante', 'incident.aireSante', 'violence', 'creator'])
            ->orderBy('incident_id')
            ->orderBy('violence_id')
            ->get()
            ->map(function (Victime $victime): array {
                $counts = VictimDemographics::counts($victime);

                return [
                    $victime->incident?->code_incident ?? '-',
                    optional($victime->incident?->date_incident)->format('Y-m-d'),
                    $victime->incident?->province?->nom_province ?? '-',
                    $victime->incident?->territoire?->nom_territoire ?? $victime->incident?->code_territoire,
                    $victime->incident?->zoneSante?->nom_zonesante ?? $victime->incident?->code_zonesante,
                    $victime->incident?->aireSante?->nom_airesante ?? $victime->incident?->code_airesante,
                    $victime->violence_id,
                    $victime->violence?->violence_name ?? '-',
                    $victime->violence?->categorie_name ?? '-',
                    $victime->profile_victimes,
                    ...$counts,
                    array_sum($counts),
                    $victime->description_faits,
                    $victime->creator?->name ?? $victime->created_by ?? '-',
                    optional($victime->create_at)->format('Y-m-d'),
                    $victime->id,
                    $victime->incident_id,
                ];
            });
    }
}
