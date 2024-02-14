<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Environment\AuthServerUriCollection;
use izi\prestashop\OAuth2\AuthorizationProviderFactoryInterface;
use izi\prestashop\OAuth2\Exception\AccessTokenRequestException;
use izi\prestashop\OAuth2\Token\AccessTokenInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class InPostApiCredentialsValidator extends ConstraintValidator
{
    private const TRANSLATION_SOURCE = 'apiconfigurationvalidator';
    private const REQUIRED_SCOPES = [
        'izi:basket:write',
    ];

    private $module;
    private $authProviderFactory;

    public function __construct(\Module $module, AuthorizationProviderFactoryInterface $authProviderFactory)
    {
        $this->module = $module;
        $this->authProviderFactory = $authProviderFactory;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof InPostApiCredentials) {
            throw new UnexpectedTypeException($constraint, InPostApiCredentials::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ApiConfigurationInterface) {
            throw new UnexpectedTypeException($value, ApiConfigurationInterface::class);
        }

        if (null === $credentials = $value->getClientCredentials()) {
            return;
        }

        $uriCollection = new AuthServerUriCollection($value->getEnvironment());

        try {
            $token = $this->authProviderFactory
                ->create($uriCollection, $credentials)
                ->getAccessToken();

            $this->validateTokenScopes($token);
        } catch (AccessTokenRequestException $e) {
            $this->context
                ->buildViolation($this->module->l('Invalid client credentials.', self::TRANSLATION_SOURCE))
                ->addViolation();
        } catch (NetworkExceptionInterface $e) {
            $this->context
                ->buildViolation($this->module->l('Could not connect to the authorization server.', self::TRANSLATION_SOURCE))
                ->addViolation();
        } catch (\Exception $e) {
            $this->context
                ->buildViolation($this->module->l('Could not validate client credentials.', self::TRANSLATION_SOURCE))
                ->addViolation();
        }
    }

    private function validateTokenScopes(AccessTokenInterface $token): void
    {
        if (null === $scopes = $token->getScopes()) {
            return;
        }

        if ([] !== array_diff(self::REQUIRED_SCOPES, $scopes)) {
            $this->context
                ->buildViolation($this->module->l('The granted access token does not have all of the required permissions. To resolve this issue, please contact support.', self::TRANSLATION_SOURCE))
                ->addViolation();
        }
    }
}
