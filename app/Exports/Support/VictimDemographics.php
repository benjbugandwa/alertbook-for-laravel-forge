<?php

namespace App\Exports\Support;

use App\Models\Victime;
use Illuminate\Support\Collection;

final class VictimDemographics
{
    private const COLUMNS = [
        'nbre_femme_0a4ans' => 'femmes_0_4_ans',
        'nbre_femme_5a11ans' => 'femmes_5_11_ans',
        'nbre_femme_12a17ans' => 'femmes_12_17_ans',
        'nbre_femme_18a59ans' => 'femmes_18_59_ans',
        'nbre_femme_6Oansouplus' => 'femmes_60_plus',
        'nbre_homme_0a4ans' => 'hommes_0_4_ans',
        'nbre_homme_5a11ans' => 'hommes_5_11_ans',
        'nbre_homme_12a17ans' => 'hommes_12_17_ans',
        'nbre_homme_18a59ans' => 'hommes_18_59_ans',
        'nbre_homme_6Oansouplus' => 'hommes_60_plus',
    ];

    public static function headings(): array
    {
        return array_values(self::COLUMNS);
    }

    public static function counts(Victime $victime): array
    {
        return collect(array_keys(self::COLUMNS))
            ->map(fn (string $column): int => (int) ($victime->{$column} ?? 0))
            ->all();
    }

    public static function aggregate(Collection $victims): Collection
    {
        return $victims
            ->groupBy(fn (Victime $victime): string => self::key($victime->incident_id, $victime->violence_id))
            ->map(function (Collection $group): array {
                return $group->reduce(function (array $totals, Victime $victime): array {
                    foreach (self::counts($victime) as $index => $count) {
                        $totals[$index] += $count;
                    }

                    return $totals;
                }, array_fill(0, count(self::COLUMNS), 0));
            });
    }

    public static function key(string $incidentId, int $violenceId): string
    {
        return $incidentId.'|'.$violenceId;
    }
}
