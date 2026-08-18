<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\FormatsWorksheetAsTable;
use App\Exports\IncidentExportFilters;
use App\Exports\Support\VictimDemographics;
use App\Models\Victime;
use App\Models\ViolenceIncident;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ViolencesSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
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
        return 'Violences';
    }

    public function headings(): array
    {
        return [
            'code_incident',
            'date_incident',
            'province',
            'territoire',
            'violence_id',
            'violence',
            'categorie_violence',
            'description_violence',
            ...VictimDemographics::headings(),
            'total_victimes',
            'lien_violence_incident_id',
            'cree_par',
            'cree_le',
            'incident_id',
        ];
    }

    public function collection(): Collection
    {
        $links = ViolenceIncident::query()
            ->whereHas('incident', fn ($query) => $this->filters->applyToRelatedIncidentQuery($query))
            ->with(['incident.province', 'incident.territoire', 'violence', 'creator'])
            ->orderBy('id_incident')
            ->orderBy('id_violence')
            ->get();

        $victimsByViolation = VictimDemographics::aggregate(
            Victime::query()
                ->whereHas('incident', fn ($query) => $this->filters->applyToRelatedIncidentQuery($query))
                ->get()
        );

        return $links->map(function (ViolenceIncident $link) use ($victimsByViolation): array {
            $counts = $victimsByViolation->get(
                VictimDemographics::key((string) $link->id_incident, (int) $link->id_violence),
                array_fill(0, count(VictimDemographics::headings()), 0)
            );

            return [
                $link->incident?->code_incident ?? '-',
                optional($link->incident?->date_incident)->format('Y-m-d'),
                $link->incident?->province?->nom_province ?? '-',
                $link->incident?->territoire?->nom_territoire ?? $link->incident?->code_territoire,
                $link->id_violence,
                $link->violence?->violence_name ?? '-',
                $link->violence?->categorie_name ?? '-',
                $link->description_violence,
                ...$counts,
                array_sum($counts),
                $link->id,
                $link->creator?->name ?? $link->created_by ?? '-',
                optional($link->created_at)->format('Y-m-d'),
                $link->id_incident,
            ];
        });
    }
}
