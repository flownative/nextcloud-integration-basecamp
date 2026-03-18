<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\IntegrationBasecamp\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use Throwable;

class BasecampAPIService {

	private IClient $client;

	public function __construct(
		private LoggerInterface $logger,
		private IConfig $config,
		private ICrypto $crypto,
		IClientService $clientService,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * Get card details from Basecamp API
	 */
	public function getCard(?string $userId, string $accountId, string $projectId, string $cardId): array {
		$url = 'https://3.basecampapi.com/' . $accountId
			. '/buckets/' . $projectId
			. '/card_tables/cards/' . $cardId . '.json';

		return $this->request($userId, $url);
	}

	/**
	 * Get projects for a Basecamp account
	 */
	public function getProjects(?string $userId, string $accountId): array {
		$url = 'https://3.basecampapi.com/' . $accountId . '/projects.json';
		return $this->request($userId, $url);
	}

	/**
	 * Get card tables (kanban boards) for a project by inspecting the project dock
	 */
	public function getCardTables(?string $userId, string $accountId, string $projectId): array {
		$url = 'https://3.basecampapi.com/' . $accountId . '/projects/' . $projectId . '.json';
		$project = $this->request($userId, $url);
		if (isset($project['error'])) {
			return $project;
		}
		$cardTables = [];
		foreach ($project['dock'] ?? [] as $tool) {
			if (($tool['name'] ?? '') === 'kanban_board' && ($tool['enabled'] ?? false)) {
				$cardTables[] = [
					'id' => $tool['id'] ?? 0,
					'title' => $tool['title'] ?? 'Card Table',
				];
			}
		}
		return $cardTables;
	}

	/**
	 * Get columns for a card table (embedded in card table response as "lists")
	 */
	public function getColumns(?string $userId, string $accountId, string $projectId, string $cardTableId): array {
		$url = 'https://3.basecampapi.com/' . $accountId
			. '/buckets/' . $projectId . '/card_tables/' . $cardTableId . '.json';
		$cardTable = $this->request($userId, $url);
		if (isset($cardTable['error'])) {
			return $cardTable;
		}
		return $cardTable['lists'] ?? [];
	}

	/**
	 * Get people assigned to a project
	 */
	public function getProjectPeople(?string $userId, string $accountId, string $projectId): array {
		$url = 'https://3.basecampapi.com/' . $accountId
			. '/projects/' . $projectId . '/people.json';
		return $this->request($userId, $url);
	}

	/**
	 * Create a new card in a column.
	 * Assignees are set via a separate PUT request after creation (Basecamp API limitation).
	 */
	public function createCard(
		?string $userId,
		string $accountId,
		string $projectId,
		string $columnId,
		string $title,
		?string $content = null,
		?string $dueOn = null,
		?array $assigneeIds = null,
	): array {
		$url = 'https://3.basecampapi.com/' . $accountId
			. '/buckets/' . $projectId
			. '/card_tables/lists/' . $columnId . '/cards.json';

		$body = ['title' => $title];
		if ($content !== null && $content !== '') {
			$body['content'] = $content;
		}
		if ($dueOn !== null && $dueOn !== '') {
			$body['due_on'] = $dueOn;
		}

		$card = $this->postRequest($userId, $url, $body);
		if (isset($card['error'])) {
			return $card;
		}

		// Assign people via PUT (not supported in POST)
		if ($assigneeIds !== null && count($assigneeIds) > 0 && isset($card['id'])) {
			$updateUrl = 'https://3.basecampapi.com/' . $accountId
				. '/buckets/' . $projectId
				. '/card_tables/cards/' . $card['id'] . '.json';
			$this->putRequest($userId, $updateUrl, ['assignee_ids' => $assigneeIds]);
		}

		return $card;
	}

	/**
	 * Get authorization info (user identity + accounts)
	 */
	public function getAuthorizationInfo(string $accessToken): array {
		try {
			$response = $this->client->get('https://launchpad.37signals.com/authorization.json', [
				'timeout' => 15,
				'headers' => [
					'Authorization' => 'Bearer ' . $accessToken,
					'User-Agent' => 'Nextcloud Basecamp Integration (https://nextcloud.com)',
				],
			]);
			return json_decode((string)$response->getBody(), true) ?: [];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Failed to get Basecamp authorization info', ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * Exchange OAuth code for access token
	 */
	public function requestOAuthAccessToken(string $clientId, string $clientSecret, string $code, string $redirectUri): array {
		try {
			$url = 'https://launchpad.37signals.com/authorization/token'
				. '?type=web_server'
				. '&client_id=' . urlencode($clientId)
				. '&redirect_uri=' . urlencode($redirectUri)
				. '&client_secret=' . urlencode($clientSecret)
				. '&code=' . urlencode($code);

			$response = $this->client->post($url, [
				'timeout' => 15,
				'headers' => [
					'User-Agent' => 'Nextcloud Basecamp Integration (https://nextcloud.com)',
				],
			]);
			return json_decode((string)$response->getBody(), true) ?: [];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Failed to request OAuth token', ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * Refresh an expired access token using the refresh token
	 */
	public function refreshToken(string $userId): bool {
		$refreshToken = $this->getEncryptedUserValue($userId, 'refresh_token');
		$clientId = $this->getEncryptedAppValue('client_id');
		$clientSecret = $this->getEncryptedAppValue('client_secret');

		if ($refreshToken === '' || $clientId === '' || $clientSecret === '') {
			return false;
		}

		try {
			$url = 'https://launchpad.37signals.com/authorization/token'
				. '?type=refresh'
				. '&refresh_token=' . urlencode($refreshToken)
				. '&client_id=' . urlencode($clientId)
				. '&client_secret=' . urlencode($clientSecret);

			$response = $this->client->post($url, [
				'timeout' => 15,
				'headers' => [
					'User-Agent' => 'Nextcloud Basecamp Integration (https://nextcloud.com)',
				],
			]);
			$result = json_decode((string)$response->getBody(), true) ?: [];

			if (isset($result['access_token'])) {
				$this->setEncryptedUserValue($userId, 'token', $result['access_token']);
				$this->config->setUserValue($userId, Application::APP_ID, 'token_expires_at',
					(string)(time() + ($result['expires_in'] ?? 1209600)));
				return true;
			}
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Failed to refresh Basecamp token', ['exception' => $e]);
		}
		return false;
	}

	/**
	 * Get the access token for a user, refreshing if needed.
	 * Falls back to the admin-configured app-level token.
	 */
	public function getAccessToken(?string $userId): string {
		if ($userId !== null) {
			$token = $this->getEncryptedUserValue($userId, 'token');
			if ($token !== '') {
				// Check if token is expired and refresh if needed
				$expiresAt = (int)$this->config->getUserValue($userId, Application::APP_ID, 'token_expires_at', '0');
				if ($expiresAt > 0 && $expiresAt < time()) {
					if ($this->refreshToken($userId)) {
						return $this->getEncryptedUserValue($userId, 'token');
					}
					return '';
				}
				return $token;
			}
		}

		// Fallback: app-level token (legacy/admin token)
		return $this->getEncryptedAppValue('token');
	}

	// -- Encrypted value helpers --

	public function setEncryptedUserValue(string $userId, string $key, string $value): void {
		if ($value === '') {
			$this->config->setUserValue($userId, Application::APP_ID, $key, '');
			return;
		}
		$this->config->setUserValue($userId, Application::APP_ID, $key, $this->crypto->encrypt($value));
	}

	public function getEncryptedUserValue(string $userId, string $key): string {
		$storedValue = $this->config->getUserValue($userId, Application::APP_ID, $key);
		if ($storedValue === '') {
			return '';
		}
		try {
			return $this->crypto->decrypt($storedValue);
		} catch (Exception $e) {
			$this->logger->warning('Failed to decrypt user value', ['key' => $key, 'exception' => $e]);
			return '';
		}
	}

	public function setEncryptedAppValue(string $key, string $value): void {
		if ($value === '') {
			$this->config->setAppValue(Application::APP_ID, $key, '');
			return;
		}
		$this->config->setAppValue(Application::APP_ID, $key, $this->crypto->encrypt($value));
	}

	public function getEncryptedAppValue(string $key): string {
		$storedValue = $this->config->getAppValue(Application::APP_ID, $key);
		if ($storedValue === '') {
			return '';
		}
		try {
			return $this->crypto->decrypt($storedValue);
		} catch (Exception $e) {
			$this->logger->warning('Failed to decrypt app value', ['key' => $key, 'exception' => $e]);
			return '';
		}
	}

	private function putRequest(?string $userId, string $url, array $body): array {
		$accessToken = $this->getAccessToken($userId);
		if ($accessToken === '') {
			return ['error' => 'No Basecamp API token available.'];
		}

		try {
			$options = [
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Bearer ' . $accessToken,
					'User-Agent' => 'Nextcloud Basecamp Integration (https://nextcloud.com)',
					'Content-Type' => 'application/json',
				],
				'body' => json_encode($body),
			];

			$response = $this->client->put($url, $options);
			return json_decode((string)$response->getBody(), true) ?: [];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Basecamp API PUT error', ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}

	private function postRequest(?string $userId, string $url, array $body): array {
		$accessToken = $this->getAccessToken($userId);
		if ($accessToken === '') {
			return ['error' => 'No Basecamp API token available. Please connect your Basecamp account.'];
		}

		try {
			$options = [
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Bearer ' . $accessToken,
					'User-Agent' => 'Nextcloud Basecamp Integration (https://nextcloud.com)',
					'Content-Type' => 'application/json',
				],
				'body' => json_encode($body),
			];

			$response = $this->client->post($url, $options);
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => 'API request failed with status ' . $respCode];
			}

			return json_decode((string)$response->getBody(), true) ?: [];
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 401 && $userId !== null) {
				if ($this->refreshToken($userId)) {
					return $this->postRequest(null, $url, $body);
				}
			}
			$responseBody = (string)$e->getResponse()->getBody();
			$this->logger->warning('Basecamp API client error (POST)', [
				'status' => $e->getResponse()->getStatusCode(),
				'response_body' => $responseBody,
			]);
			return [
				'error' => $e->getMessage(),
				'body' => json_decode($responseBody, true),
			];
		} catch (ServerException $e) {
			$responseBody = (string)$e->getResponse()->getBody();
			$this->logger->warning('Basecamp API server error (POST)', ['response_body' => $responseBody]);
			return [
				'error' => $e->getMessage(),
				'body' => json_decode($responseBody, true),
			];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Basecamp API request error (POST)', ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}

	private function request(?string $userId, string $url): array {
		$accessToken = $this->getAccessToken($userId);
		if ($accessToken === '') {
			return ['error' => 'No Basecamp API token available. Please connect your Basecamp account.'];
		}

		try {
			$options = [
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Bearer ' . $accessToken,
					'User-Agent' => 'Nextcloud Basecamp Integration (https://nextcloud.com)',
					'Content-Type' => 'application/json',
				],
			];

			$response = $this->client->get($url, $options);
			$body = (string)$response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => 'API request failed with status ' . $respCode];
			}

			return json_decode($body, true) ?: [];
		} catch (ClientException $e) {
			// If 401 and we have a user, try refreshing the token once
			if ($e->getResponse()->getStatusCode() === 401 && $userId !== null) {
				if ($this->refreshToken($userId)) {
					return $this->request(null, $url); // retry without userId to avoid infinite loop
				}
			}
			$responseBody = (string)$e->getResponse()->getBody();
			$this->logger->warning('Basecamp API client error', [
				'status' => $e->getResponse()->getStatusCode(),
				'response_body' => $responseBody,
			]);
			return [
				'error' => $e->getMessage(),
				'body' => json_decode($responseBody, true),
			];
		} catch (ServerException $e) {
			$responseBody = (string)$e->getResponse()->getBody();
			$this->logger->warning('Basecamp API server error', ['response_body' => $responseBody]);
			return [
				'error' => $e->getMessage(),
				'body' => json_decode($responseBody, true),
			];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Basecamp API request error', ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}
}
