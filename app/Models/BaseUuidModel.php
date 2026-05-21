<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base Eloquent model mirroring Django's BaseModel.
 *
 * Provides a UUID primary key (string, non-incrementing) for every
 * descendant so Laravel models reach parity with the Django apps that
 * rely on uuid4 ids.
 */
abstract class BaseUuidModel extends Model
{
	use HasUuids;

	/**
	 * The data type of the primary key.
	 *
	 * @var string
	 */
	protected $keyType = 'string';

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var bool
	 */
	public $incrementing = false;
}
