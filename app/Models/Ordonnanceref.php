<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Ordonnanceref
 * 
 * @property int $id
 * @property string $refOrd
 * @property int $Annee
 * @property int $numordre
 * @property int $fkidpatient
 * @property int $fkidprescripteur
 * @property Carbon|null $dtPrescript
 * @property int $fkidCabinet
 * @property string $TypeOrdonnance
 *
 * @package App\Models
 */
class Ordonnanceref extends Model
{
	protected $table = 'ordonnanceref';
	public $timestamps = false;

	protected $casts = [
		'Annee' => 'int',
		'numordre' => 'int',
		'fkidpatient' => 'int',
		'fkidprescripteur' => 'int',
		'dtPrescript' => 'datetime',
		'fkidCabinet' => 'int'
	];

	protected $fillable = [
		'refOrd',
		'Annee',
		'numordre',
		'fkidpatient',
		'fkidprescripteur',
		'dtPrescript',
		'fkidCabinet',
		'TypeOrdonnance'
	];
}
