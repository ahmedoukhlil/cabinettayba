<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques des consultations
        $consultationsAujourdhui = Facture::whereDate('DtFacture', Carbon::today())->count();
        $consultationsMois = Facture::whereMonth('DtFacture', Carbon::now()->month)
                                  ->whereYear('DtFacture', Carbon::now()->year)
                                  ->count();

        // Montant total des consultations du jour
        $montantAujourdhui = Facture::whereDate('DtFacture', Carbon::today())
                                  ->sum('TotFacture');

        // Montant total des consultations du mois
        $montantMois = Facture::whereMonth('DtFacture', Carbon::now()->month)
                            ->whereYear('DtFacture', Carbon::now()->year)
                            ->sum('TotFacture');

        // Top 3 des médecins les plus consultés aujourd'hui
        $topMedecins = Facture::whereDate('DtFacture', Carbon::today())
                            ->select('FkidMedecinInitiateur', DB::raw('count(*) as total'))
                            ->with('medecin:idMedecin,Nom')
                            ->groupBy('FkidMedecinInitiateur')
                            ->orderBy('total', 'desc')
                            ->take(3)
                            ->get();

        // Dernières consultations
        $dernieresConsultations = Facture::with(['patient:ID,Nom,Prenom', 'medecin:idMedecin,Nom'])
                                      ->orderBy('DtFacture', 'desc')
                                      ->take(5)
                                      ->get();

        return view('dashboard', compact(
            'consultationsAujourdhui',
            'consultationsMois',
            'montantAujourdhui',
            'montantMois',
            'topMedecins',
            'dernieresConsultations'
        ));
    }
} 