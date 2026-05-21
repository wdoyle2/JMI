<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default Hash Driver
	|--------------------------------------------------------------------------
	|
	| This option controls the default hash driver that will be used to hash
	| passwords for your application. By default, the argon2id algorithm is
	| used for parity with Django's argon2-cffi password hasher.
	|
	| Supported: "bcrypt", "argon", "argon2id"
	|
	*/

	'driver' => env('HASH_DRIVER', 'argon2id'),

	/*
	|--------------------------------------------------------------------------
	| Bcrypt Options
	|--------------------------------------------------------------------------
	|
	| Here you may specify the configuration options that should be used when
	| passwords are hashed using the Bcrypt algorithm. This will allow you
	| to control the amount of time it takes to hash the given password.
	|
	*/

	'bcrypt' => [
		'rounds' => env('BCRYPT_ROUNDS', 12),
		'verify' => true,
		'limit' => null,
	],

	/*
	|--------------------------------------------------------------------------
	| Argon Options
	|--------------------------------------------------------------------------
	|
	| Here you may specify the configuration options that should be used when
	| passwords are hashed using the Argon algorithm. These will allow you
	| to control the amount of time it takes to hash the given password.
	|
	*/

	'argon' => [
		'memory' => env('ARGON_MEMORY', 65536),
		'threads' => env('ARGON_THREADS', 1),
		'time' => env('ARGON_TIME', 4),
		'verify' => true,
	],

	/*
	|--------------------------------------------------------------------------
	| Rehash On Login
	|--------------------------------------------------------------------------
	|
	| Setting this option to true will tell Laravel to automatically rehash
	| the user's password during login if the work factor has changed.
	|
	*/

	'rehash_on_login' => true,

];
