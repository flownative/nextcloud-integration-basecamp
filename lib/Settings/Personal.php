<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Settings;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCA\IntegrationBasecamp\Service\BasecampAPIService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

class Personal implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
		private IURLGenerator $urlGenerator,
		private BasecampAPIService $basecampAPIService,
		private ?string $userId,
	) {
	}

	public function getForm(): TemplateResponse {
		$clientId = $this->basecampAPIService->getEncryptedAppValue('client_id');

		$token = '';
		$userName = '';
		if ($this->userId !== null) {
			$token = $this->basecampAPIService->getEncryptedUserValue($this->userId, 'token');
			$userName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		}

		$oauthUrl = '';
		if ($clientId !== '') {
			$redirectUri = $this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->linkToRoute('integration_basecamp.config.oauthRedirect')
			);
			$oauthUrl = 'https://launchpad.37signals.com/authorization/new'
				. '?type=web_server'
				. '&client_id=' . urlencode($clientId)
				. '&redirect_uri=' . urlencode($redirectUri);
		}

		$personalConfig = [
			'token' => $token !== '' ? 'dummyToken' : '',
			'user_name' => $userName,
			'oauth_url' => $oauthUrl,
			'client_id_configured' => $clientId !== '',
		];
		$this->initialStateService->provideInitialState('personal-config', $personalConfig);
		Util::addScript(Application::APP_ID, Application::APP_ID . '-personalSettings');
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
