<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $cabinetId = Auth::user()->fkidcabinet;

        // Statistiques des consultations
        $consultationsAujourdhui = Facture::where('fkidCabinet', $cabinetId)
            ->whereDate('DtFacture', Carbon::today())->count();
        $consultationsMois = Facture::where('fkidCabinet', $cabinetId)
                                  ->whereMonth('DtFacture', Carbon::now()->month)
                                  ->whereYear('DtFacture', Carbon::now()->year)
                                  ->count();

        // Montant total des consultations du jour
        $montantAujourdhui = Facture::where('fkidCabinet', $cabinetId)
                                  ->whereDate('DtFacture', Carbon::today())
                                  ->sum('TotFacture');

        // Montant total des consultations du mois
        $montantMois = Facture::where('fkidCabinet', $cabinetId)
                            ->whereMonth('DtFacture', Carbon::now()->month)
                            ->whereYear('DtFacture', Carbon::now()->year)
                            ->sum('TotFacture');

        // Top 3 des médecins les plus consultés aujourd'hui
        $topMedecins = Facture::where('fkidCabinet', $cabinetId)
                            ->whereDate('DtFacture', Carbon::today())
                            ->select('FkidMedecinInitiateur', DB::raw('count(*) as total'))
                            ->with('medecin:idMedecin,Nom')
                            ->groupBy('FkidMedecinInitiateur')
                            ->orderBy('total', 'desc')
                            ->take(3)
                            ->get();

        // Dernières consultations
        $dernieresConsultations = Facture::with(['patient:ID,Nom,Prenom', 'medecin:idMedecin,Nom'])
                                      ->where('fkidCabinet', $cabinetId)
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
