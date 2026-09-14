<?php

namespace App\Http\Controllers;

use App\Exports\EmptyIncidentsWorkbookExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class IncidentExportController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after:from'],
            'format' => ['nullable', 'in:xlsx'],
            'province' => ['nullable', 'string', 'exists:provinces,code_province'],
            'territoire' => ['nullable', 'string', 'exists:territoires,code_territoire'],

            'include_notes' => ['nullable', 'in:0,1'],
            'include_referencements' => ['nullable', 'in:0,1'],
            'include_violences' => ['nullable', 'in:0,1'],
            'include_victimes' => ['nullable', 'in:0,1'],
            'include_reponses' => ['nullable', 'in:0,1'],
        ]);

        if (! $user->hasRole('superadmin')) {
            $data['province'] = $user->code_province;
        }

        $this->ensureTerritoireBelongsToProvince(
            $data['territoire'] ?? null,
            $data['province'] ?? null,
        );

        // The incident data export is intentionally suspended. Keep the
        // validation and download workflow intact, but return a blank workbook.
        // Restore IncidentsWorkbookExport here when the suspension is lifted.
        $export = new EmptyIncidentsWorkbookExport;

        $filename = 'Export-alertes-'.$data['from'].'_au_'.$data['to'].'.xlsx';

        return Excel::download($export, $filename);
    }

    private function ensureTerritoireBelongsToProvince(?string $territoire, ?string $province): void
    {
        if (! $territoire || ! $province) {
            return;
        }

        $exists = DB::table('territoires')
            ->where('code_territoire', $territoire)
            ->where('code_province', $province)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'territoire' => 'Le territoire selectionne ne correspond pas a la province choisie.',
            ]);
        }
    }
}
