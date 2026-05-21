<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base for domain repositories.
 *
 * Follows the Repository pattern described in "Architecture Patterns with
 * Python" (a.k.a. Cosmic Python) — see Chapter 2: Repository Pattern. The
 * intent is to decouple the domain layer from Eloquent so we can later
 * swap the persistence backend without touching service-layer code.
 *
 * Concrete subclasses are expected to bind themselves to a specific
 * Eloquent model and surface domain-meaningful query methods. None exist
 * yet — this base lives in the tree to set the convention for the team.
 */
abstract class AbstractRepository
{
	/**
	 * Eloquent model class this repository is responsible for.
	 *
	 * @var class-string<Model>
	 */
	protected string $model;

	public function get(string $id): ?Model
	{
		return ($this->model)::query()->find($id);
	}

	public function all(): Collection
	{
		return ($this->model)::query()->get();
	}

	public function add(Model $entity): Model
	{
		$entity->save();

		return $entity;
	}
}
