<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Facture
 * 
 * @property int $Idfacture
 * @property string|null $Nfacture
 * @property int|null $anneeFacture
 * @property int|null $nordre
 * @property Carbon|null $DtFacture
 * @property int|null $IDPatient
 * @property int|null $ISTP
 * @property int|null $fkidEtsAssurance
 * @property float $TXPEC
 * @property float $TotFacture
 * @property float $TotalPEC
 * @property float $TotalfactPatient
 * @property float $TotReglPatient
 * @property float $ReglementPEC
 * @property string|null $ModeReglement
 * @property string|null $Areglepar
 * @property Carbon|null $DtReglement
 * @property float $fkidbordfacture
 * @property int $ispayerAssureur
 * @property string|null $user
 * @property int $estfacturer
 * @property int $FkidMedecinInitiateur
 * @property float $PartLaboratoire
 * @property float $MontantAffectation
 * @property string $Type
 * @property int $fkidCabinet
 *
 * @package App\Models
 */
class Facture extends Model
{
	protected $table = 'facture';
	protected $primaryKey = 'Idfacture';
	public $timestamps = false;

	protected $casts = [
		'anneeFacture' => 'int',
		'nordre' => 'int',
		'DtFacture' => 'datetime',
		'IDPatient' => 'int',
		'ISTP' => 'int',
		'fkidEtsAssurance' => 'int',
		'TXPEC' => 'float',
		'TotFacture' => 'float',
		'TotalPEC' => 'float',
		'TotalfactPatient' => 'float',
		'TotReglPatient' => 'float',
		'ReglementPEC' => 'float',
		'DtReglement' => 'datetime',
		'fkidbordfacture' => 'float',
		'ispayerAssureur' => 'int',
		'estfacturer' => 'int',
		'FkidMedecinInitiateur' => 'int',
		'PartLaboratoire' => 'float',
		'MontantAffectation' => 'float',
		'fkidCabinet' => 'int'
	];

	protected $fillable = [
		'Nfacture',
		'anneeFacture',
		'nordre',
		'DtFacture',
		'IDPatient',
		'ISTP',
		'fkidEtsAssurance',
		'TXPEC',
		'TotFacture',
		'TotalPEC',
		'TotalfactPatient',
		'TotReglPatient',
		'ReglementPEC',
		'ModeReglement',
		'Areglepar',
		'DtReglement',
		'fkidbordfacture',
		'ispayerAssureur',
		'user',
		'estfacturer',
		'FkidMedecinInitiateur',
		'PartLaboratoire',
		'MontantAffectation',
		'Type',
		'fkidCabinet'
	];

	// Relations
	public function patient()
	{
		return $this->belongsTo(Patient::class, 'IDPatient', 'ID');
	}

	public function details()
	{
		return $this->hasMany(DetailFacturePatient::class, 'fkidfacture', 'Idfacture');
	}

	public function medecin()
	{
		return $this->belongsTo(Medecin::class, 'FkidMedecinInitiateur', 'idMedecin');
	}

	public function rendezVous()
	{
		return $this->hasOne(Rendezvou::class, 'fkidFacture', 'Idfacture');
	}

	public function assureur()
	{
		return $this->belongsTo(Assureur::class, 'fkidEtsAssurance', 'IDAssureur');
	}

	public function reglements()
	{
		return $this->hasMany(Reglement::class, 'fkidFactBord', 'Idfacture');
	}
}
