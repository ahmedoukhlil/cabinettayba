<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Patient;
use App\Models\Medecin;
use App\Models\Acte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationController extends Controller
{
    public function create()
    {
        return view('consultations.create');
    }

    public function show($id)
    {
        $facture = Facture::with(['patient', 'details.acte'])->findOrFail($id);
        return view('consultations.show', compact('facture'));
    }

    public function showReceipt($factureId)
    {
        // Utiliser le cache pour les données du cabinet
        $cabinet = cache()->remember('cabinet_info_' . Auth::id(), 3600, function() {
            $user = Auth::user();
            return [
                'NomCabinet' => $user->cabinet->NomCabinet ?? 'Medipole',
                'Adresse' => $user->cabinet->Adresse ?? 'Adresse du Cabinet',
                'Telephone' => $user->cabinet->Telephone ?? 'Téléphone du Cabinet'
            ];
        });

        // Précharger toutes les relations nécessaires en une seule requête
        $facture = Facture::with([
            'patient',
            'medecin',
            'details.acte',
            'assureur',
            'rendezVous' => function($query) {
                $query->select('IDRdv', 'OrdreRDV', 'fkidFacture');
            }
        ])->findOrFail($factureId);

        return view('consultations.receipt-preview', compact('facture', 'cabinet'));
    }

    public function showFacturePatient($factureId)
    {
        $facture = Facture::with([
            'patient',
            'medecin',
            'details',
            'assureur',
        ])->findOrFail($factureId);

        // Montant en lettres (exemple simple, à adapter selon votre helper)
        $facture->en_lettres = $this->numberToWords($facture->TotFacture ?? 0);

        return view('consultations.facture-patient', compact('facture'));
    }

    // Helper simple pour montant en lettres (à remplacer par votre propre logique si besoin)
    private function numberToWords($number)
    {
        // Utilisez un package ou une fonction plus complète si besoin
        $f = new \NumberFormatter("fr", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number));
    }
} 