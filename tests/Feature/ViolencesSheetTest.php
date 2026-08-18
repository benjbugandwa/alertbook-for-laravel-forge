<?php

namespace Tests\Feature;

use App\Exports\Sheets\ViolencesSheet;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViolencesSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_violations_sheet_contains_aggregated_victim_demographics(): void
    {
        DB::table('provinces')->insert([
            'code_province' => 'P01',
            'nom_province' => 'Province test',
            'is_active' => 'YES',
        ]);
        DB::table('territoires')->insert([
            'code_territoire' => 'T01',
            'nom_territoire' => 'Territoire test',
            'code_province' => 'P01',
        ]);
        DB::table('violences')->insert([
            'id' => 1001,
            'violence_name' => 'Violation test',
            'categorie_name' => 'Catégorie test',
        ]);

        $user = User::factory()->create(['code_province' => 'P01']);
        $incidentId = (string) Str::uuid();

        Incident::create([
            'id' => $incidentId,
            'code_incident' => 'ALT-EXPORT-001',
            'date_incident' => '2026-08-01',
            'created_by' => $user->id,
            'code_province' => 'P01',
            'code_territoire' => 'T01',
            'statut_incident' => Incident::STATUS_VALIDATED,
            'created_at' => '2026-08-01',
        ]);

        DB::table('violence_incidents')->insert([
            'id' => 1,
            'id_incident' => $incidentId,
            'id_violence' => 1001,
            'description_violence' => 'Description test',
            'created_by' => $user->id,
            'created_at' => '2026-08-01',
        ]);

        foreach ([[2, 3], [4, 5]] as [$women, $men]) {
            DB::table('victimes')->insert([
                'incident_id' => $incidentId,
                'violence_id' => 1001,
                'profile_victimes' => 'Résidants',
                'nbre_femme_0a4ans' => $women,
                'nbre_homme_18a59ans' => $men,
                'description_faits' => 'Victimes test',
                'create_at' => '2026-08-01',
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sheet = new ViolencesSheet('2026-08-01', '2026-08-31');
        $row = array_combine($sheet->headings(), $sheet->collection()->first());

        $this->assertSame(6, $row['femmes_0_4_ans']);
        $this->assertSame(8, $row['hommes_18_59_ans']);
        $this->assertSame(14, $row['total_victimes']);
        $this->assertSame(0, $row['femmes_60_plus']);
    }
}
