<?php

namespace Jasara\AmznSPA\Resources;

use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Jasara\AmznSPA\Constants\Marketplace;
use Jasara\AmznSPA\Contracts\ResourceContract;
use Jasara\AmznSPA\DataTransferObjects\ApplicationKeysDTO;
use Jasara\AmznSPA\DataTransferObjects\AuthTokensDTO;
use Jasara\AmznSPA\DataTransferObjects\GrantlessTokenDTO;
use Jasara\AmznSPA\Exceptions\AmznSPAException;
use Jasara\AmznSPA\Exceptions\AuthenticationException;
use Jasara\AmznSPA\Traits\ValidatesParameters;

class LwaResource implements ResourceContract
{
    use ValidatesParameters;

    public const ENDPOINT = 'https://api.amazon.com/auth/o2/token';

    public function __construct(
        private Factory $http,
        private Marketplace $marketplace,
        private ?string $redirect_url,
        private ApplicationKeysDTO $application_keys,
        private ?Closure $save_lwa_tokens_callback,
        private ?Closure $authentication_exception_callback,
        private ?Closure $response_callback,
    ) {
    }

    public function getAuthUrl(?string $state = null): string
    {
        $redirect_url = $this->redirect_url;

        $params = http_build_query(compact('redirect_url', 'state'));
        $url = $this->getBaseUrlFromMarketplace() . '/apps/authorize/consent';

        if ($params) {
            $url .= '?' . $params;
        }

        return $url;
    }

    /**
     * @param array $parameters Array containing the data sent by Amazon on the redirect
     *      $parameters = [
     *          'state'             => (string) Required, should match the original state that was sent to Amazon
     *          'spapi_oauth_code'  => (string) Required, the authorization code
     *      ]
     */
    public function getTokensFromRedirect(string $original_state, array $parameters): AuthTokensDTO
    {
        $this->validateObjectProperties($this, ['redirect_url']);
        $this->validateArrayParameters($parameters, ['state', 'spapi_oauth_code']);

        if (! $this->isRedirectValid($original_state, $parameters['state'])) {
            throw new AmznSPAException('State returned from Amazon does not match the original state');
        }

        return $this->callGetTokens($parameters['spapi_oauth_code']);
    }

    public function getTokensFromAuthorizationCode(string $authorization_code): AuthTokensDTO
    {
        $this->validateObjectProperties($this, ['redirect_url']);

        return $this->callGetTokens($authorization_code);
    }

    private function callGetTokens(string $spapi_oauth_code): AuthTokensDTO
    {
        $response = $this->http->post(self::ENDPOINT, [
            'grant_type' => 'authorization_code',
            'code' => $spapi_oauth_code,
            'redirect_uri' => $this->redirect_url,
            'client_id' => $this->application_keys->lwa_client_id,
            'client_secret' => $this->application_keys->lwa_client_secret,
        ]);

        if ($response->failed()) {
            $this->handleError($response);
        }

        $tokens = $this->formatTokenResponse($response->json());

        $this->storeLwaTokens($tokens);

        return $tokens;
    }

    public function getAccessTokenFromRefreshToken(string $refresh_token): AuthTokensDTO
    {
        $response = $this->http->post(self::ENDPOINT, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->application_keys->lwa_client_id,
            'client_secret' => $this->application_keys->lwa_client_secret,
        ]);

        if ($this->response_callback) {
            $callback = $this->response_callback;

            $callback($response);
        }

        if ($response->failed()) {
            $this->handleError($response);
        }

        $tokens = $this->formatTokenResponse($response->json());

        $this->storeLwaTokens($tokens);

        return $tokens;
    }

    public function getGrantlessAccessToken(string $scope): GrantlessTokenDTO
    {
        $response = $this->http->post(self::ENDPOINT, [
            'grant_type' => 'client_credentials',
            'scope' => $scope,
            'client_id' => $this->application_keys->lwa_client_id,
            'client_secret' => $this->application_keys->lwa_client_secret,
        ]);

        if ($response->failed()) {
            $this->handleError($response);
        }

        return $this->formatGrantlessTokenResponse($response->json());
    }

    private function isRedirectValid(string $original_state, string $amzn_state): bool
    {
        if ($original_state === $amzn_state) {
            return true;
        }

        return false;
    }

    private function storeLwaTokens(AuthTokensDTO $tokens)
    {
        if ($this->save_lwa_tokens_callback) {
            $callback = $this->save_lwa_tokens_callback;
            $callback($tokens);
        }
    }

    private function formatTokenResponse(array $response_data): AuthTokensDTO
    {
        return new AuthTokensDTO(
            access_token: $response_data['access_token'],
            refresh_token: $response_data['refresh_token'],
            expires_at: $response_data['expires_in'],
        );
    }

    private function formatGrantlessTokenResponse(array $response_data): GrantlessTokenDTO
    {
        return new GrantlessTokenDTO(
            access_token: $response_data['access_token'],
            expires_at: $response_data['expires_in'],
        );
    }

    private function getBaseUrlFromMarketplace(): string
    {
        return $this->marketplace->getBaseUrl();
    }

    private function handleError(Response $response)
    {
        if ($response->status() === 401) {
            throw new AuthenticationException(
                $response,
                $this->authentication_exception_callback,
            );
        }

        return $response->throw();
    }
}
