<?php
// Simple env loader returning array of env values used by app.
return [
	'APP_ENV' => getenv('APP_ENV') ?: 'development',
	'DB_DRIVER' => getenv('DB_DRIVER') ?: 'mysql', // usar mysql para MAriaDb pois utiliza para o PDO
	'DB_HOST' => getenv('DB_HOST') ?: '127.0.0.1',
	'DB_NAME' => getenv('DB_NAME') ?: 'jebusiness',
	'DB_USER' => getenv('DB_USER') ?: 'jebusiness',
	'DB_PASS' => getenv('DB_PASS') ?: '_43690',
	'APP_URL'  => getenv('APP_URL') ?: 'http://localhost',
];