<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Reference;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCA\IntegrationBasecamp\Service\BasecampAPIService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class BasecampCardReferenceProvider extends ADiscoverableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_card';

	// Pattern: https://3.basecamp.com/{account_id}/buckets/{project_id}/card_tables/cards/{card_id}
	private const URL_PATTERN = '/^https:\/\/3\.basecamp\.com\/([0-9]+)\/buckets\/([0-9]+)\/card_tables\/cards\/([0-9]+)/i';

	public function __construct(
		private BasecampAPIService $basecampAPIService,
		private IConfig $config,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ?string $userId,
	) {
	}

	public function getId(): string {
		return 'basecamp-card';
	}

	public function getTitle(): string {
		return $this->l10n->t('Basecamp cards');
	}

	public function getOrder(): int {
		return 10;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app.png')
		);
	}

	public function matchReference(string $referenceText): bool {
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled) {
			return false;
		}
		return preg_match(self::URL_PATTERN, $referenceText) === 1;
	}

	public function resolveReference(string $referenceText): ?IReference {
		if (!$this->matchReference($referenceText)) {
			return null;
		}

		$urlParts = $this->parseCardUrl($referenceText);
		if ($urlParts === null) {
			return null;
		}

		[$accountId, $projectId, $cardId] = $urlParts;
		$cardInfo = $this->basecampAPIService->getCard($this->userId, $accountId, $projectId, $cardId);

		$reference = new Reference($referenceText);

		if (isset($cardInfo['error'])) {
			$reference->setTitle('Basecamp Card');
			$reference->setDescription($cardInfo['error']);
			$reference->setRichObject(self::RICH_OBJECT_TYPE, [
				'basecamp_type' => 'card-error',
				'error' => $cardInfo['error'],
				'link' => $referenceText,
			]);
			return $reference;
		}

		$title = $cardInfo['title'] ?? 'Basecamp Card';
		$columnName = $cardInfo['parent']['title'] ?? '';
		$projectName = $cardInfo['bucket']['name'] ?? '';
		$completed = $cardInfo['completed'] ?? false;
		$dueOn = $cardInfo['due_on'] ?? null;

		$description = $projectName;
		if ($columnName !== '') {
			$description .= ' > ' . $columnName;
		}
		if ($completed) {
			$description .= ' [' . $this->l10n->t('Completed') . ']';
		}
		if ($dueOn !== null) {
			$description .= ' | ' . $this->l10n->t('Due') . ': ' . $dueOn;
		}

		$reference->setTitle($title);
		$reference->setDescription($description);
		$reference->setRichObject(self::RICH_OBJECT_TYPE, [
			'basecamp_type' => 'card',
			'card_id' => $cardId,
			'project_id' => $projectId,
			'account_id' => $accountId,
			'title' => $title,
			'completed' => $completed,
			'due_on' => $dueOn,
			'column' => $columnName,
			'project' => $projectName,
			'assignees' => $this->formatAssignees($cardInfo['assignees'] ?? []),
			'creator' => $cardInfo['creator']['name'] ?? '',
			'comments_count' => $cardInfo['comments_count'] ?? 0,
			'created_at' => $cardInfo['created_at'] ?? '',
			'updated_at' => $cardInfo['updated_at'] ?? '',
			'app_url' => $cardInfo['app_url'] ?? $referenceText,
			'link' => $referenceText,
		]);

		return $reference;
	}

	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	public function getCacheKey(string $referenceId): ?string {
		$urlParts = $this->parseCardUrl($referenceId);
		if ($urlParts === null) {
			return $referenceId;
		}
		return implode('/', $urlParts);
	}

	private function parseCardUrl(string $url): ?array {
		preg_match(self::URL_PATTERN, $url, $matches);
		if (count($matches) < 4) {
			return null;
		}
		return [$matches[1], $matches[2], $matches[3]];
	}

	private function formatAssignees(array $assignees): array {
		return array_map(static function (array $assignee) {
			return [
				'name' => $assignee['name'] ?? '',
				'avatar_url' => $assignee['avatar_url'] ?? '',
			];
		}, $assignees);
	}
}
