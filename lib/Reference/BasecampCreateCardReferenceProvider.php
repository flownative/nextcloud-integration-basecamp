<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Reference;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class BasecampCreateCardReferenceProvider extends ADiscoverableReferenceProvider {

	private const URL_PATTERN = '/^https:\/\/3\.basecamp\.com\/([0-9]+)\/buckets\/([0-9]+)\/card_tables\/cards\/([0-9]+)/i';

	public function __construct(
		private BasecampCardReferenceProvider $cardProvider,
		private IConfig $config,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ?string $userId,
	) {
	}

	public function getId(): string {
		return 'create-basecamp-card';
	}

	public function getTitle(): string {
		return $this->l10n->t('Create a Basecamp card');
	}

	public function getOrder(): int {
		return 20;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app.png')
		);
	}

	public function matchReference(string $referenceText): bool {
		return preg_match(self::URL_PATTERN, $referenceText) === 1;
	}

	public function resolveReference(string $referenceText): ?IReference {
		return $this->cardProvider->resolveReference($referenceText);
	}

	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	public function getCacheKey(string $referenceId): ?string {
		return $referenceId;
	}
}
