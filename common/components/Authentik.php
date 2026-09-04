<?php

namespace common\components;

use RuntimeException;
use UnexpectedValueException;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\httpclient\Client;
use yii\httpclient\Response;

/**
 * Minimal OIDC Relying-Party client for the BAWES "Universe" Authentik
 * provider (auth.bawes.net).
 *
 * Used by `candidate` AuthController::actionLoginByUniverse() (SHU-29
 * "Continue with Universe"): the endpoint exchanges the one-time
 * authorization code at Authentik's token endpoint **server-side** and then
 * validates the returned id_token (RS256 signature against the Authentik
 * JWKS, aud, iss, exp, iat and optional nonce). The verified subject (email)
 * is the only identity input the login action trusts — the browser never
 * supplies an email.
 *
 * Configuration lives in `common/config/main.php` under the `authentik`
 * component and is env-driven:
 *
 * - AUTHENTIK_ISSUER_URL    (default https://auth.bawes.net)
 * - AUTHENTIK_CLIENT_ID
 * - AUTHENTIK_CLIENT_SECRET
 * - AUTHENTIK_REDIRECT_URI  optional: when set, the callback's redirect_uri
 *                           must match it (defence in depth)
 * - AUTHENTIK_JWKS_URL      optional override; by default the JWKS URL is
 *                           discovered from {issuer}/.well-known/openid-configuration
 *
 * Endpoints follow Authentik's OIDC layout rooted at the issuer:
 * token endpoint = {issuer}/application/o/token/.
 *
 * JWT verification uses firebase/php-jwt (v6, present in composer.lock as a
 * transitive dependency of xeroapi/xero-php-oauth2). If it is ever missing
 * from a deployment, `verifyIdToken()` fails closed.
 */
class Authentik extends Component
{
    /**
     * @var string Authentik base URL (no trailing slash).
     */
    public $issuer = 'https://auth.bawes.net';

    /**
     * @var string OIDC client id of the StudentHub "Universe" application.
     */
    public $clientId = '';

    /**
     * @var string OIDC client secret of the StudentHub "Universe" application.
     */
    public $clientSecret = '';

    /**
     * @var string Registered redirect_uri. When non-empty the callback's
     * redirect_uri parameter must match it.
     */
    public $redirectUri = '';

    /**
     * @var string Optional explicit JWKS URL override (otherwise discovered).
     */
    public $jwksUrl = '';

    /**
     * @var int Clock leeway (seconds) tolerated around exp/iat checks.
     */
    public $leeway = 0;

    /**
     * @var Client|null HTTP client override. Defaults to the shared
     * `httpclient` app component; tests inject a client backed by
     * yii\httpclient\MockTransport here.
     */
    public $httpClient;

    /**
     * @var array|null Instance-level JWKS cache (per request, never shared).
     */
    private $_jwks;

    /**
     * @var int|null Timestamp the JWKS was fetched at.
     */
    private $_jwksFetchedAt;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if ($this->issuer !== null) {
            $this->issuer = rtrim((string)$this->issuer, '/');
        }
        $this->clientId = (string)$this->clientId;
        $this->clientSecret = (string)$this->clientSecret;
        $this->redirectUri = (string)$this->redirectUri;
        $this->jwksUrl = (string)$this->jwksUrl;
    }

    /**
     * Exchange an authorization code for tokens and return the *validated*
     * id_token claims.
     *
     * Steps:
     *  1. POST grant_type=authorization_code (+ code, redirect_uri,
     *     client_id, client_secret) to the Authentik token endpoint;
     *  2. verify the returned id_token (signature via JWKS, aud, iss,
     *     exp/iat, optional nonce);
     *  3. return the claims as an associative array.
     *
     * The Universe provider is configured with sub_mode=user_email, so the
     * `sub` claim is the user's verified email address.
     *
     * @param string $code authorization code received by the callback
     * @param string|null $redirectUri redirect_uri used for the authorize
     * request (must match AUTHENTIK_REDIRECT_URI when that is configured)
     * @param string|null $nonce optional nonce the SPA expects in the id_token
     * @return array validated claims (associative)
     * @throws InvalidArgumentException on missing code
     * @throws RuntimeException when the exchange/validation fails or the
     * client is not configured
     */
    public function authenticate($code, $redirectUri = null, $nonce = null)
    {
        if ($code === null || $code === '') {
            throw new InvalidArgumentException('Missing authorization code.');
        }

        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException(
                'Authentik OIDC client is not configured (AUTHENTIK_CLIENT_ID / AUTHENTIK_CLIENT_SECRET).'
            );
        }

        if ($this->redirectUri !== ''
            && $redirectUri !== null
            && $redirectUri !== ''
            && $redirectUri !== $this->redirectUri
        ) {
            throw new RuntimeException('redirect_uri does not match the configured Universe redirect URI.');
        }

        if ($redirectUri === null || $redirectUri === '') {
            $redirectUri = $this->redirectUri;
        }

        $tokens = $this->requestTokens($code, $redirectUri);

        $idToken = isset($tokens['id_token']) ? (string)$tokens['id_token'] : '';
        if ($idToken === '') {
            throw new UnexpectedValueException('Token response did not contain an id_token.');
        }

        return $this->verifyIdToken($idToken, $nonce);
    }

    /**
     * Validate an id_token and return its claims.
     *
     * @param string $idToken
     * @param string|null $nonce when provided the id_token `nonce` claim must equal it
     * @return array claims (associative)
     * @throws RuntimeException|UnexpectedValueException on any validation failure
     */
    public function verifyIdToken($idToken, $nonce = null)
    {
        if (!class_exists(\Firebase\JWT\JWT::class)) {
            throw new RuntimeException('firebase/php-jwt is not installed/autoloadable.');
        }

        $keys = $this->getVerificationKeys();

        try {
            // v6 signature: decode($jwt, $keyOrKeyArray) — algorithm is pinned
            // to RS256 through the parsed Key objects.
            $payload = \Firebase\JWT\JWT::decode($idToken, $keys);
        } catch (\Throwable $e) {
            // SignatureInvalidException / ExpiredException / BeforeValidException / ...
            throw new UnexpectedValueException('id_token verification failed: ' . $e->getMessage(), 0, $e);
        }

        $claims = json_decode(json_encode($payload), true);
        if (!is_array($claims)) {
            throw new UnexpectedValueException('id_token claims are not a JSON object.');
        }

        $now = time();

        // aud — php-jwt v6 does not enforce audience, so check it here.
        $aud = isset($claims['aud']) ? $claims['aud'] : null;
        $audOk = is_array($aud)
            ? in_array($this->clientId, $aud, true)
            : ($aud === $this->clientId);
        if (!$audOk) {
            throw new UnexpectedValueException('id_token audience mismatch.');
        }

        // iss — must be the configured issuer or a sub-path of it (Authentik
        // per-application issuers carry a /application/o/{slug} path).
        $iss = isset($claims['iss']) ? (string)$claims['iss'] : '';
        if ($iss !== '' && $this->issuer !== '') {
            if ($iss !== $this->issuer && strpos($iss, $this->issuer . '/') !== 0) {
                throw new UnexpectedValueException('id_token issuer mismatch.');
            }
        }

        // exp / iat — php-jwt enforces exp (and iat when no nbf is present);
        // enforce both explicitly so the checks do not depend on lib quirks.
        if (isset($claims['exp']) && ($now - $this->leeway) >= (int)$claims['exp']) {
            throw new UnexpectedValueException('id_token has expired.');
        }
        if (isset($claims['iat']) && (int)$claims['iat'] > ($now + $this->leeway)) {
            throw new UnexpectedValueException('id_token was issued in the future.');
        }

        // Optional nonce check (only when the caller expects one).
        if ($nonce !== null && $nonce !== '') {
            $tokenNonce = isset($claims['nonce']) ? $claims['nonce'] : null;
            if (!is_string($tokenNonce) || !hash_equals($nonce, $tokenNonce)) {
                throw new UnexpectedValueException('id_token nonce mismatch.');
            }
        }

        $sub = isset($claims['sub']) ? (string)$claims['sub'] : '';
        if ($sub === '') {
            throw new UnexpectedValueException('id_token is missing the subject claim.');
        }

        return $claims;
    }

    /**
     * Authentik token endpoint URL.
     * @return string
     */
    public function getTokenEndpoint()
    {
        return $this->issuer . '/application/o/token/';
    }

    /**
     * Returns the (shared) HTTP client to use.
     * @return Client
     */
    public function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = (\Yii::$app !== null && \Yii::$app->has('httpclient'))
                ? \Yii::$app->get('httpclient')
                : new Client();
        }
        return $this->httpClient;
    }

    /**
     * Exchange the code at the token endpoint.
     *
     * @param string $code
     * @param string $redirectUri
     * @return array decoded token response (id_token/access_token/...)
     * @throws RuntimeException on transport or OAuth error response
     */
    protected function requestTokens($code, $redirectUri)
    {
        $response = $this->getHttpClient()->createRequest()
            ->setMethod('POST')
            ->setUrl($this->getTokenEndpoint())
            ->setData([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ])
            ->send();

        $data = $response->getData();

        if (!$response->isOk || !is_array($data) || (isset($data['error']) && !isset($data['id_token']))) {
            $description = 'unknown error';
            if (is_array($data)) {
                if (isset($data['error_description'])) {
                    $description = (string)$data['error_description'];
                } elseif (isset($data['error'])) {
                    $description = (string)$data['error'];
                }
            } elseif (is_string($response->content) && $response->content !== '') {
                $description = $response->content;
            }
            // Truncate: the body may contain provider internals we must not leak to clients.
            throw new RuntimeException('Authentik token exchange failed: ' . mb_substr($description, 0, 300));
        }

        return $data;
    }

    /**
     * Fetch the JWKS document, discovering its URL when not configured.
     *
     * @return array decoded JWKS (must contain a `keys` member)
     * @throws RuntimeException
     */
    protected function getJwks()
    {
        // Cache per component instance (i.e. per request) for at most 5 minutes.
        if ($this->_jwks !== null && (time() - $this->_jwksFetchedAt) < 300) {
            return $this->_jwks;
        }

        $jwksUrl = $this->jwksUrl;
        if ($jwksUrl === '') {
            $discovery = $this->fetchOpenIdConfiguration();
            $jwksUrl = isset($discovery['jwks_uri']) ? (string)$discovery['jwks_uri'] : '';
        }
        if ($jwksUrl === '') {
            throw new RuntimeException('Unable to resolve the Authentik JWKS URL.');
        }

        $response = $this->getHttpClient()->createRequest()
            ->setMethod('GET')
            ->setUrl($jwksUrl)
            ->send();

        $data = $response->getData();

        if (!$response->isOk || !is_array($data) || !isset($data['keys']) || !is_array($data['keys'])) {
            throw new RuntimeException('Unable to fetch the Authentik JWKS.');
        }

        $this->_jwks = $data;
        $this->_jwksFetchedAt = time();

        return $data;
    }

    /**
     * GET {issuer}/.well-known/openid-configuration
     * @return array discovery document
     * @throws RuntimeException
     */
    protected function fetchOpenIdConfiguration()
    {
        $url = $this->issuer . '/.well-known/openid-configuration';

        $response = $this->getHttpClient()->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->send();

        $data = $response->getData();

        if (!$response->isOk || !is_array($data)) {
            throw new RuntimeException('Unable to fetch the Authentik OpenID configuration.');
        }

        return $data;
    }

    /**
     * Parse the JWKS into firebase/php-jwt Key objects, pinned to RS256.
     * @return array<string, \Firebase\JWT\Key>
     * @throws RuntimeException
     */
    protected function getVerificationKeys()
    {
        try {
            return \Firebase\JWT\JWK::parseKeySet($this->getJwks(), 'RS256');
        } catch (\Throwable $e) {
            throw new RuntimeException('Unable to parse the Authentik JWKS: ' . $e->getMessage(), 0, $e);
        }
    }
}
