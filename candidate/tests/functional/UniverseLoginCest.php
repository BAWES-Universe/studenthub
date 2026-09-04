<?php

namespace candidate\tests;

use candidate\tests\FunctionalTester;
use common\components\Authentik;
use common\fixtures\CandidateFixture;
use Firebase\JWT\JWT;
use yii\httpclient\MockTransport;
use yii\httpclient\Response;

/**
 * SHU-29 "Continue with Universe" — functional tests for
 * POST /v1/auth/login-by-universe (server-side Authentik OIDC exchange).
 *
 * Every test injects a fake `authentik` component whose HTTP client is backed
 * by a MockTransport, so no external network is touched: the token exchange,
 * discovery doc and JWKS responses are all queued in-process, and the id_token
 * is signed by a real 2048-bit RSA key held only in this test.
 */
class UniverseLoginCest
{
    /** @var string|null */
    private static $privateKeyPem = null;

    /** @var string|null */
    private static $publicKeyPem = null;

    private const CLIENT_ID = 'studenthub-test';
    private const ISSUER = 'https://auth.bawes.net';
    private const EMAIL = 'universe-tester@studenthub.test';

    public function _fixtures()
    {
        return [
            'candidates' => CandidateFixture::class,
        ];
    }

    private static function keyPair(): array
    {
        if (self::$privateKeyPem === null) {
            $config = [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];
            $res = openssl_pkey_new($config);
            openssl_pkey_export($res, self::$privateKeyPem);
            $details = openssl_pkey_get_details($res);
            self::$publicKeyPem = $details['key'];
        }
        return [self::$privateKeyPem, self::$publicKeyPem];
    }

    /**
     * Sign a fake id_token (RS256) with the test key.
     */
    private function signIdToken(array $claims): string
    {
        [$private] = self::keyPair();
        return JWT::encode($claims, $private, 'RS256', 'test-key-1');
    }

    /**
     * Install a fake `authentik` component on the app whose httpClient uses a
     * MockTransport preloaded with the token response (id_token signed by the
     * test key), the OIDC discovery doc and the JWKS doc.
     */
    private function installFakeAuthentik(FunctionalTester $I, array $claims, string $code = 'test-code', string $redirectUri = 'https://student.studenthub.co/auth/callback'): void
    {
        [, $public] = self::keyPair();

        $idToken = $this->signIdToken($claims);

        $transport = new MockTransport();

        // 1) token endpoint response
        $tokenResp = new Response();
        $tokenResp->setContent(json_encode([
            'id_token' => $idToken,
            'access_token' => 'fake-access',
            'token_type' => 'Bearer',
            'expires_in' => 300,
        ]));
        $tokenResp->setFormat(\yii\httpclient\Client::FORMAT_JSON);
        $tokenResp->setHeaders(['http-code' => 200]);
        $transport->appendResponse($tokenResp);

        // 2) discovery doc (issuer-root discovery returns 404 on Authentik;
        //    the component resolves jwks_uri from it when jwksUrl is unset,
        //    so we point jwksUrl directly in the component and skip discovery)
        // 3) JWKS doc
        $jwksResp = new Response();
        $jwksResp->setContent(json_encode([
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'kid' => 'test-key-1',
                    'n' => $this->b64url($this->rsaComponent($public, 'n')),
                    'e' => $this->b64url($this->rsaComponent($public, 'e')),
                ],
            ],
        ]));
        $jwksResp->setFormat(\yii\httpclient\Client::FORMAT_JSON);
        $jwksResp->setHeaders(['http-code' => 200]);
        $transport->appendResponse($jwksResp);

        $authentik = new Authentik();
        $authentik->issuer = self::ISSUER;
        $authentik->clientId = self::CLIENT_ID;
        $authentik->clientSecret = 'test-secret';
        $authentik->redirectUri = $redirectUri;
        $authentik->jwksUrl = self::ISSUER . '/application/o/studenthub/jwks/';
        $authentik->httpClient = new \yii\httpclient\Client(['transport' => $transport]);

        \Yii::$app->set('authentik', $authentik);
    }

    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function rsaComponent(string $pem, string $component)
    {
        $pub = openssl_pkey_get_public($pem);
        $details = openssl_pkey_get_details($pub);
        return $details['rsa'][$component];
    }

    // ---- tests -------------------------------------------------------------

    /**
     * Happy path: no existing candidate -> account auto-created verified and a
     * bearer token returned.
     */
    public function loginCreatesAccountWhenNoneExists(FunctionalTester $I)
    {
        $this->installFakeAuthentik($I, [
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'sub' => self::EMAIL,
            'email' => self::EMAIL,
            'name' => 'Universe Tester',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $I->sendPOST('/v1/auth/login-by-universe', [
            'code' => 'test-code',
            'redirect_uri' => 'https://student.studenthub.co/auth/callback',
            'state' => 'abc123',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['operation' => 'success']);
        $I->seeResponseContainsJson(['state' => 'abc123']);
        $I->seeResponseJsonMatchesJsonPath('$.token');
        $I->seeResponseJsonMatchesJsonPath('$.email');
        $I->seeResponseContainsJson(['email' => self::EMAIL]);

        // Account exists now, verified, active.
        $I->seeRecord('candidate\models\Candidate', [
            'candidate_email' => self::EMAIL,
            'deleted' => 0,
        ]);
    }

    /**
     * Existing verified candidate -> logs straight in, no new account.
     */
    public function loginFindsExistingVerifiedCandidate(FunctionalTester $I)
    {
        // Seed the candidate directly.
        $I->haveRecord('candidate\models\Candidate', [
            'candidate_email' => 'existing-verified@studenthub.test',
            'candidate_name' => 'Existing Tester',
            'candidate_email_verification' => 1,
            'candidate_status' => 10,
            'approved' => 1,
            'deleted' => 0,
        ]);

        $this->installFakeAuthentik($I, [
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'sub' => 'existing-verified@studenthub.test',
            'email' => 'existing-verified@studenthub.test',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $I->sendPOST('/v1/auth/login-by-universe', [
            'code' => 'test-code',
            'redirect_uri' => 'https://student.studenthub.co/auth/callback',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['operation' => 'success']);
        $I->seeResponseContainsJson(['email' => 'existing-verified@studenthub.test']);
    }

    /**
     * An id_token whose audience is not our client id must be rejected.
     */
    public function wrongAudienceIsRejected(FunctionalTester $I)
    {
        $this->installFakeAuthentik($I, [
            'iss' => self::ISSUER,
            'aud' => 'some-other-client',
            'sub' => 'wrong-aud@studenthub.test',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $I->sendPOST('/v1/auth/login-by-universe', [
            'code' => 'test-code',
            'redirect_uri' => 'https://student.studenthub.co/auth/callback',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['operation' => 'error']);
        $I->seeResponseContainsJson(['errorType' => 'invalid-code']);

        // No account may be created from an unvalidated token.
        $I->dontSeeRecord('candidate\models\Candidate', ['candidate_email' => 'wrong-aud@studenthub.test']);
    }

    /**
     * An expired id_token must be rejected.
     */
    public function expiredTokenIsRejected(FunctionalTester $I)
    {
        $this->installFakeAuthentik($I, [
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'sub' => 'expired@studenthub.test',
            'iat' => time() - 3600,
            'exp' => time() - 1800,
        ]);

        $I->sendPOST('/v1/auth/login-by-universe', [
            'code' => 'test-code',
            'redirect_uri' => 'https://student.studenthub.co/auth/callback',
        ]);
        $I->seeResponseContainsJson(['operation' => 'error']);
        $I->dontSeeRecord('candidate\models\Candidate', ['candidate_email' => 'expired@studenthub.test']);
    }

    /**
     * Missing code -> clean error, nothing happens.
     */
    public function missingCodeIsRejected(FunctionalTester $I)
    {
        $I->sendPOST('/v1/auth/login-by-universe', [
            'redirect_uri' => 'https://student.studenthub.co/auth/callback',
        ]);
        $I->seeResponseContainsJson(['operation' => 'error']);
        $I->seeResponseContainsJson(['errorType' => 'invalid-code']);
    }
}
