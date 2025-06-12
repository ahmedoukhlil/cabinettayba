<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Detailfacturepatient
 * 
 * @property int $idDetfacture
 * @property int $fkidfacture
 * @property Carbon|null $DtAjout
 * @property string|null $Actes
 * @property float|null $PrixRef
 * @property float|null $PrixFacture
 * @property float|null $Quantite
 * @property int $fkidMedecin
 * @property Carbon|null $DTajout2
 * @property string|null $user
 * @property string|null $Dents
 * @property Carbon|null $DtActe
 * @property float|null $fkidacte
 * @property int $IsAct
 * @property string $ActesArab
 * @property int $fkidcabinet
 *
 * @package App\Models
 */
class Detailfacturepatient extends Model
{
	protected $table = 'detailfacturepatient';
	protected $primaryKey = 'idDetfacture';
	public $timestamps = false;

	protected $casts = [
		'fkidfacture' => 'int',
		'DtAjout' => 'datetime',
		'PrixRef' => 'float',
		'PrixFacture' => 'float',
		'Quantite' => 'float',
		'fkidMedecin' => 'int',
		'DTajout2' => 'datetime',
		'DtActe' => 'datetime',
		'fkidacte' => 'float',
		'IsAct' => 'int',
		'fkidcabinet' => 'int'
	];

	protected $fillable = [
		'fkidfacture',
		'DtAjout',
		'Actes',
		'PrixRef',
		'PrixFacture',
		'Quantite',
		'fkidMedecin',
		'DTajout2',
		'user',
		'Dents',
		'DtActe',
		'fkidacte',
		'IsAct',
		'ActesArab',
		'fkidcabinet'
	];

	// Relations
	public function acte()
	{
		return $this->belongsTo(Acte::class, 'fkidacte', 'ID');
	}
}
