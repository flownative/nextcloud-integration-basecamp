<?php

declare(strict_types=1);

namespace OCA\IntegrationBasecamp\AppInfo;

use OCA\IntegrationBasecamp\Listener\BasecampReferenceListener;
use OCA\IntegrationBasecamp\Reference\BasecampCardReferenceProvider;
use OCA\IntegrationBasecamp\Reference\BasecampCreateCardReferenceProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_basecamp';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerReferenceProvider(BasecampCardReferenceProvider::class);
		$context->registerReferenceProvider(BasecampCreateCardReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class, BasecampReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
