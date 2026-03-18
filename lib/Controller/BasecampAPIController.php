<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Controller;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCA\IntegrationBasecamp\Service\BasecampAPIService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

class BasecampAPIController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private BasecampAPIService $basecampAPIService,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get user's Basecamp accounts.
	 * If not yet stored, fetches them from the authorization endpoint and caches them.
	 */
	#[NoAdminRequired]
	public function getAccounts(): DataResponse {
		$accountsJson = $this->config->getUserValue($this->userId, Application::APP_ID, 'accounts', '');
		if ($accountsJson !== '') {
			$accounts = json_decode($accountsJson, true) ?: [];
			if (count($accounts) > 0) {
				return new DataResponse($accounts);
			}
		}

		// Fetch from authorization endpoint and store
		$token = $this->basecampAPIService->getAccessToken($this->userId);
		if ($token === '') {
			return new DataResponse(['error' => 'Not connected to Basecamp'], Http::STATUS_UNAUTHORIZED);
		}
		$info = $this->basecampAPIService->getAuthorizationInfo($token);
		if (isset($info['error']) || !isset($info['accounts'])) {
			return new DataResponse(['error' => 'Failed to fetch Basecamp accounts'], Http::STATUS_BAD_REQUEST);
		}

		$bc3Accounts = array_values(array_filter($info['accounts'], static function (array $account) {
			return ($account['product'] ?? '') === 'bc3';
		}));
		$accounts = array_map(static function (array $account) {
			return [
				'id' => (string)($account['id'] ?? ''),
				'name' => $account['name'] ?? '',
			];
		}, $bc3Accounts);

		$this->config->setUserValue($this->userId, Application::APP_ID, 'accounts', json_encode($accounts));
		return new DataResponse($accounts);
	}

	/**
	 * Get projects for an account
	 */
	#[NoAdminRequired]
	public function getProjects(string $accountId): DataResponse {
		$result = $this->basecampAPIService->getProjects($this->userId, $accountId);
		if (isset($result['error'])) {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
		$projects = array_map(static function (array $project) {
			return [
				'id' => $project['id'] ?? 0,
				'name' => $project['name'] ?? '',
				'description' => $project['description'] ?? '',
			];
		}, $result);
		return new DataResponse($projects);
	}

	/**
	 * Get card tables for a project
	 */
	#[NoAdminRequired]
	public function getCardTables(string $accountId, string $projectId): DataResponse {
		$result = $this->basecampAPIService->getCardTables($this->userId, $accountId, $projectId);
		if (isset($result['error'])) {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($result);
	}

	/**
	 * Get columns for a card table
	 */
	#[NoAdminRequired]
	public function getColumns(string $accountId, string $projectId, string $cardTableId): DataResponse {
		$result = $this->basecampAPIService->getColumns($this->userId, $accountId, $projectId, $cardTableId);
		if (isset($result['error'])) {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
		$columns = array_map(static function (array $column) {
			return [
				'id' => $column['id'] ?? 0,
				'title' => $column['title'] ?? '',
				'cards_count' => $column['cards_count'] ?? 0,
			];
		}, $result);
		return new DataResponse($columns);
	}

	/**
	 * Get people for a project (for assignee picker)
	 */
	#[NoAdminRequired]
	public function getProjectPeople(string $accountId, string $projectId): DataResponse {
		$result = $this->basecampAPIService->getProjectPeople($this->userId, $accountId, $projectId);
		if (isset($result['error'])) {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
		$people = array_map(static function (array $person) {
			return [
				'id' => $person['id'] ?? 0,
				'name' => $person['name'] ?? '',
				'avatar_url' => $person['avatar_url'] ?? '',
			];
		}, $result);
		return new DataResponse($people);
	}

	/**
	 * Create a card
	 */
	#[NoAdminRequired]
	public function createCard(
		string $accountId,
		string $projectId,
		string $columnId,
		string $title,
		?string $content = null,
		?string $dueOn = null,
		?array $assigneeIds = null,
	): DataResponse {
		if (trim($title) === '') {
			return new DataResponse(['error' => 'Title is required'], Http::STATUS_BAD_REQUEST);
		}

		$result = $this->basecampAPIService->createCard(
			$this->userId,
			$accountId,
			$projectId,
			$columnId,
			$title,
			$content,
			$dueOn,
			$assigneeIds,
		);

		if (isset($result['error'])) {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse([
			'id' => $result['id'] ?? 0,
			'title' => $result['title'] ?? '',
			'app_url' => $result['app_url'] ?? '',
		]);
	}
}
