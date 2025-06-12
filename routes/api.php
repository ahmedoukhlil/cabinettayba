<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/patients/search', function (Request $request) {
    $search = $request->get('search');
    $query = \App\Models\Patient::query();

    if (strlen($search) >= 2) {
        $query->where(function($q) use ($search) {
            $q->where('Nom', 'like', '%' . $search . '%')
              ->orWhere('Prenom', 'like', '%' . $search . '%')
              ->orWhere('NNI', 'like', '%' . $search . '%')
              ->orWhere('Telephone1', 'like', '%' . $search . '%')
              ->orWhere('Telephone2', 'like', '%' . $search . '%');
        });
    }

    $query->where('fkidcabinet', auth()->user()->fkidcabinet);

    return $query->select('ID', 'Nom', 'Prenom', 'NNI', 'Telephone1', 'Telephone2')
                ->orderBy('Nom')
                ->paginate(10);
})->middleware('auth:sanctum');
