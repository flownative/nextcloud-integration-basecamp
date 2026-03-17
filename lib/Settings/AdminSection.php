<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Settings;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getID(): string {
		return 'connected-accounts';
	}

	public function getName(): string {
		return $this->l10n->t('Connected accounts');
	}

	public function getPriority(): int {
		return 10;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app.png');
	}
}
