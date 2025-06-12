<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Medicament
 * 
 * @property int $IDMedic
 * @property string $LibelleMedic
 * @property int $fkidtype
 *
 * @package App\Models
 */
class Medicament extends Model
{
	protected $table = 'medicaments';
	protected $primaryKey = 'IDMedic';
	public $timestamps = false;

	protected $casts = [
		'fkidtype' => 'int'
	];

	protected $fillable = [
		'LibelleMedic',
		'fkidtype'
	];
}
