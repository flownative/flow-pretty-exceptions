<?php
namespace Flownative\PrettyExceptions\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Error\DebugExceptionHandler;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context;
use Neos\Flow\Utility\Environment;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Exception handler that produces nice output with code snippets and lots of debug information
 *
 * @Flow\Scope("singleton")
 */
class PrettyDebugExceptionHandler extends DebugExceptionHandler
{
    /**
     * Echoes an exception for the web.
     *
     * @param \Exception|\Throwable $exception
     * @return void
     */
    protected function echoExceptionWeb($exception)
    {
        $prettyPageHandler = $this->getPrettyPageHandler();

        $whoops = new Run;
        $whoops->pushHandler($prettyPageHandler);
        $whoops->sendHttpCode(500);
        $whoops->writeToOutput(true);

        $whoops->handleException($exception);
    }

    /**
     * Initialize Whoops handler for Flow.
     *
     * @return PrettyPageHandler
     */
    protected function getPrettyPageHandler()
    {

        $prettyPageHandler = new PrettyPageHandler();
        $prettyPageHandler->addResourcePath(__DIR__ . '/../../Resources/Private/HandlerIncludes/');
        $prettyPageHandler->addCustomCss('Neos.css');

        $prettyPageHandler->addDataTableCallback('Flow Environment', function () {
            $result = [];
            $environment = Bootstrap::$staticObjectManager->get(Environment::class);
            if ($environment !== null) {
                $result['context'] = (string)$environment->getContext();
                $result['temporary directory'] = $environment->getPathToTemporaryDirectory();
            }

            return $result;
        });

        $prettyPageHandler->addDataTableCallback('Flow Security', function () {
            $result = [];
            $securityContext = Bootstrap::$staticObjectManager->get(Context::class);
            if ($securityContext instanceof Context) {
                $result['Initialized'] = $securityContext->isInitialized() ? 'YES' : 'NO';
                if ($securityContext->isInitialized() === true) {
                    $result['Active Roles'] = implode(', ', array_keys($securityContext->getRoles()));
                    $account = $securityContext->getAccount();
                    if ($account instanceof Account) {
                        $result['Active Account Identifier'] = $account->getAccountIdentifier();
                        $result['Active Account Authentication Provider'] = $account->getAuthenticationProviderName();
                    }
                }
            }

            return $result;
        });

        return $prettyPageHandler;
    }
}
