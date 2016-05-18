<?php

namespace Uecode\Bundle\ApiKeyBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use FOS\UserBundle\Model\UserInterface;
use Uecode\Bundle\ApiKeyBundle\Security\Authentication\Token\ApiKeyUserToken;
use Uecode\Bundle\ApiKeyBundle\Security\Authentication\Provider\ApiKeyUserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class ApiKeyProvider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Attempts to authenticate a TokenInterface object.
     *
     * @param TokenInterface $token The TokenInterface instance to authenticate
     *
     * @return TokenInterface An authenticated TokenInterface instance, never null
     *
     * @throws AuthenticationException if the authentication fails
     */
    public function authenticate(TokenInterface $token)
    {
        if($this->userProvider instanceof ChainUserProvider) {
            foreach ($this->userProvider->getProviders() as $provider) {
               $result = $this->doAuth($provider, $token);
                if($result !== false) {
                    return $result;
                }
            }
        } else {
            $result = $this->doAuth($this->userProvider, $token);
            if ($result !== false) {
                return $result;
            }
        }
    }

    /**
     * @param UserProviderInterface $provider
     * @param TokenInterface        $token
     *
     * @return bool|ApiKeyUserToken
     * @throws AuthenticationException
     */
    protected function doAuth($provider, TokenInterface $token)
    {
        if (! $provider instanceof ApiKeyUserProviderInterface) {
            return false;
        }

        /** @var UserInterface $user */
        $user = $provider->loadUserByApiKey($token->getCredentials());

        if ($user && $user->isEnabled()) {
            $user->setRoles(['ROLE_API']);
            $authenticatedToken = new ApiKeyUserToken($user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        throw new AuthenticationCredentialsNotFoundException();
    }

    /**
     * Checks whether this provider supports the given token.
     *
     * @param TokenInterface $token A TokenInterface instance
     *
     * @return Boolean true if the implementation supports the Token, false otherwise
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof ApiKeyUserToken;
    }
}
