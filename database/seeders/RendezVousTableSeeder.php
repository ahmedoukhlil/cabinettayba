<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RendezVousTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rendezVous = [
            [
                'ActePrevu' => 'Consultation dentaire',
                'DtAjRdv' => now(),
                'dtPrevuRDV' => Carbon::now()->addDays(1)->setHour(9)->setMinute(0),
                'user' => 'Admin',
                'HeureRdv' => Carbon::now()->addDays(1)->setHour(9)->setMinute(0),
                'fkidPatient' => 1,
                'rdvConfirmer' => 'Confirmé',
                'fkidMedecin' => 1,
                'OrdreRDV' => 1,
                'HeureConfRDV' => now(),
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Détartrage',
                'DtAjRdv' => now(),
                'dtPrevuRDV' => Carbon::now()->addDays(2)->setHour(10)->setMinute(30),
                'user' => 'Admin',
                'HeureRdv' => Carbon::now()->addDays(2)->setHour(10)->setMinute(30),
                'fkidPatient' => 2,
                'rdvConfirmer' => 'En Attente',
                'fkidMedecin' => 2,
                'OrdreRDV' => 2,
                'HeureConfRDV' => null,
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Soin carie',
                'DtAjRdv' => now(),
                'dtPrevuRDV' => Carbon::now()->addDays(3)->setHour(14)->setMinute(0),
                'user' => 'Admin',
                'HeureRdv' => Carbon::now()->addDays(3)->setHour(14)->setMinute(0),
                'fkidPatient' => 3,
                'rdvConfirmer' => 'Confirmé',
                'fkidMedecin' => 1,
                'OrdreRDV' => 1,
                'HeureConfRDV' => now(),
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Contrôle',
                'DtAjRdv' => now(),
                'dtPrevuRDV' => Carbon::now()->addDays(4)->setHour(15)->setMinute(30),
                'user' => 'Admin',
                'HeureRdv' => Carbon::now()->addDays(4)->setHour(15)->setMinute(30),
                'fkidPatient' => 4,
                'rdvConfirmer' => 'En Attente',
                'fkidMedecin' => 2,
                'OrdreRDV' => 3,
                'HeureConfRDV' => null,
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Extraction',
                'DtAjRdv' => now(),
                'dtPrevuRDV' => Carbon::now()->addDays(5)->setHour(16)->setMinute(0),
                'user' => 'Admin',
                'HeureRdv' => Carbon::now()->addDays(5)->setHour(16)->setMinute(0),
                'fkidPatient' => 5,
                'rdvConfirmer' => 'En Attente',
                'fkidMedecin' => 1,
                'OrdreRDV' => 4,
                'HeureConfRDV' => null,
                'fkidcabinet' => 1
            ]
        ];

        foreach ($rendezVous as $rdv) {
            DB::table('rendezvous')->insert($rdv);
        }
    }
}
