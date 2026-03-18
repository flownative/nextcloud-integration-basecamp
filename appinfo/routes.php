<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'config#setSensitiveAdminConfig', 'url' => '/sensitive-admin-config', 'verb' => 'PUT'],
		['name' => 'config#oauthRedirect', 'url' => '/oauth-redirect', 'verb' => 'GET'],
		['name' => 'config#disconnect', 'url' => '/config/disconnect', 'verb' => 'PUT'],

		// Basecamp API endpoints for Smart Picker
		['name' => 'basecampAPI#getAccounts', 'url' => '/api/accounts', 'verb' => 'GET'],
		['name' => 'basecampAPI#getProjects', 'url' => '/api/accounts/{accountId}/projects', 'verb' => 'GET'],
		['name' => 'basecampAPI#getCardTables', 'url' => '/api/accounts/{accountId}/projects/{projectId}/card-tables', 'verb' => 'GET'],
		['name' => 'basecampAPI#getColumns', 'url' => '/api/accounts/{accountId}/projects/{projectId}/card-tables/{cardTableId}/columns', 'verb' => 'GET'],
		['name' => 'basecampAPI#getProjectPeople', 'url' => '/api/accounts/{accountId}/projects/{projectId}/people', 'verb' => 'GET'],
		['name' => 'basecampAPI#createCard', 'url' => '/api/cards', 'verb' => 'POST'],
	],
];
