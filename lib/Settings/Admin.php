<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Settings;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCA\IntegrationBasecamp\Service\BasecampAPIService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
		private BasecampAPIService $basecampAPIService,
	) {
	}

	public function getForm(): TemplateResponse {
		$clientId = $this->basecampAPIService->getEncryptedAppValue('client_id');
		$clientSecret = $this->basecampAPIService->getEncryptedAppValue('client_secret');

		$adminConfig = [
			'client_id' => $clientId,
			'client_secret' => $clientSecret === '' ? '' : 'dummyClientSecret',
			'link_preview_enabled' => $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1',
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);
		Util::addScript(Application::APP_ID, Application::APP_ID . '-adminSettings');
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
