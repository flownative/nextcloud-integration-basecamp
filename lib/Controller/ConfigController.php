<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\Controller;

use OCA\IntegrationBasecamp\AppInfo\Application;
use OCA\IntegrationBasecamp\Service\BasecampAPIService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class ConfigController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IL10N $l,
		private LoggerInterface $logger,
		private BasecampAPIService $basecampAPIService,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Set user config values
	 */
	#[NoAdminRequired]
	public function setConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		return new DataResponse([]);
	}

	/**
	 * Set admin config values (non-sensitive)
	 */
	public function setAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if (in_array($key, ['client_id', 'client_secret'], true)) {
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
			}
			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse([]);
	}

	/**
	 * Set admin config values (sensitive — requires password confirmation)
	 */
	#[PasswordConfirmationRequired]
	public function setSensitiveAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if (in_array($key, ['client_id', 'client_secret'], true)) {
				$this->basecampAPIService->setEncryptedAppValue($key, $value);
			} else {
				$this->config->setAppValue(Application::APP_ID, $key, $value);
			}
		}
		return new DataResponse([]);
	}

	/**
	 * Receive OAuth code and exchange for access token
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function oauthRedirect(string $code = '', string $state = ''): RedirectResponse {
		$configState = $this->config->getUserValue($this->userId, Application::APP_ID, 'oauth_state');
		$clientId = $this->basecampAPIService->getEncryptedAppValue('client_id');
		$clientSecret = $this->basecampAPIService->getEncryptedAppValue('client_secret');

		// Reset state
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'oauth_state');

		if ($clientId === '' || $clientSecret === '' || $configState === '' || $configState !== $state) {
			return new RedirectResponse(
				$this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts'])
				. '?basecampToken=error&message=' . urlencode($this->l->t('Error during OAuth exchanges'))
			);
		}

		$redirectUri = $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkToRoute('integration_basecamp.config.oauthRedirect')
		);

		$result = $this->basecampAPIService->requestOAuthAccessToken($clientId, $clientSecret, $code, $redirectUri);

		if (!isset($result['access_token'])) {
			$this->logger->warning('No access token in Basecamp OAuth response', ['response' => $result]);
			return new RedirectResponse(
				$this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts'])
				. '?basecampToken=error&message=' . urlencode($this->l->t('Error getting OAuth access token'))
			);
		}

		// Store tokens
		$this->basecampAPIService->setEncryptedUserValue($this->userId, 'token', $result['access_token']);
		if (isset($result['refresh_token'])) {
			$this->basecampAPIService->setEncryptedUserValue($this->userId, 'refresh_token', $result['refresh_token']);
		}
		$this->config->setUserValue($this->userId, Application::APP_ID, 'token_expires_at',
			(string)(time() + ($result['expires_in'] ?? 1209600)));

		// Fetch and store user info
		$this->storeUserInfo($result['access_token']);

		return new RedirectResponse(
			$this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts'])
			. '?basecampToken=success'
		);
	}

	/**
	 * Disconnect: remove all user tokens and info
	 */
	#[NoAdminRequired]
	public function disconnect(): DataResponse {
		$keysToDelete = ['token', 'refresh_token', 'token_expires_at', 'oauth_state', 'user_name', 'user_id', 'accounts'];
		foreach ($keysToDelete as $key) {
			$this->config->deleteUserValue($this->userId, Application::APP_ID, $key);
		}
		return new DataResponse([]);
	}

	private function storeUserInfo(string $accessToken): void {
		$info = $this->basecampAPIService->getAuthorizationInfo($accessToken);
		if (isset($info['identity'])) {
			$identity = $info['identity'];
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_id',
				(string)($identity['id'] ?? ''));
			$name = trim(($identity['first_name'] ?? '') . ' ' . ($identity['last_name'] ?? ''));
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', $name);
		}
		// Store Basecamp 3 account IDs for API access
		if (isset($info['accounts']) && is_array($info['accounts'])) {
			$bc3Accounts = array_values(array_filter($info['accounts'], static function (array $account) {
				return ($account['product'] ?? '') === 'bc3';
			}));
			$accountData = array_map(static function (array $account) {
				return [
					'id' => (string)($account['id'] ?? ''),
					'name' => $account['name'] ?? '',
				];
			}, $bc3Accounts);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'accounts', json_encode($accountData));
		}
	}
}
