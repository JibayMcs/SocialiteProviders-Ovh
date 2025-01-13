<?php

namespace SocialiteProviders\Ovh;

use Ovh\Api as Ovh;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class Provider.
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'OVH';

    public static function additionalConfigKeys(): array
    {
        return ['endpoint'];
    }

    protected function getAuthUrl($state): string
    {
        $ovh = new Ovh(
            $this->clientId,
            $this->clientSecret,
            $this->getConfig('endpoint')
        );

        $request = $ovh->requestCredentials(
            [
                [
                    'method'    => 'GET',
                    'path'      => '/me',
                ],
                [
                    'method'    => 'GET',
                    'path'      => '/domain*',
                ]
            ],
            $this->redirectUrl.'?state='.$state
        );

        $this->request->session()->flash($state, $request['consumerKey']);

        return $request['validationUrl'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCode()
    {
        return $this->request->input('state');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($state)
    {
        return [
            'access_token' => $this->request->session()->get($state),
        ];
    }

    protected function getTokenUrl(): string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $ovh = new Ovh(
            $this->clientId,
            $this->clientSecret,
            $this->getConfig('endpoint'),
            $token
        );

        return $ovh->get('/me');
    }

    /**
     * {@inheritdoc}
     */
    protected function hasInvalidState()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map(
            [
                'avatar'   => null,
                'email'    => $user['email'],
                'id'       => $user['customerCode'],
                'name'     => $user['firstname'].' '.$user['name'],
                'nickname' => $user['nichandle'],
            ]
        );
    }
}
