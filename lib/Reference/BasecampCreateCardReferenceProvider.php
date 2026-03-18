<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Reference;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * Smart Picker provider for creating Basecamp cards.
 *
 * This is a picker-only provider: matchReference() and resolveReference()
 * always return false/null. The actual UI is provided by the custom picker
 * element registered in reference.js. URL resolution is handled separately
 * by BasecampCardReferenceProvider.
 */
class BasecampCreateCardReferenceProvider extends ADiscoverableReferenceProvider {

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
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
		return false;
	}

	public function resolveReference(string $referenceText): ?IReference {
		return null;
	}

	public function getCachePrefix(string $referenceId): string {
		return '';
	}

	public function getCacheKey(string $referenceId): ?string {
		return null;
	}
}
