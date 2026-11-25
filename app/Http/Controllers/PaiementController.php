<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\CaisseOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaiementController extends Controller
{
    public function printHistorique($patientId)
    {
        $cabinetId = Auth::user()->fkidcabinet;
        $patient = Patient::where('fkidcabinet', $cabinetId)->findOrFail($patientId);
        $paymentHistory = CaisseOperation::where('fkidTiers', $patientId)
            ->where('fkidcabinet', $cabinetId)
            ->orderBy('dateoper', 'desc')
            ->get();
        return view('paiement.print-historique', compact('patient', 'paymentHistory'));
    }
}
