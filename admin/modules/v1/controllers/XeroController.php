<?php

namespace admin\modules\v1\controllers;

use XeroAPI\XeroPHP\Configuration;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\helpers\Url;
use yii\rest\Controller;
// Use this class to deserialize error caught
use XeroAPI\XeroPHP\AccountingObjectSerializer;

class XeroController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options', 'auth', "callback"];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * redirect to xero for authentication
     * @return void
     */
    public function actionAuth() {

        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => Yii::$app->xero->clientId,
            'clientSecret'            => Yii::$app->xero->clientSecret,
            'redirectUri'             => Url::to(['xero/callback'], true),
            'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
            'urlAccessToken'          => 'https://identity.xero.com/connect/token',
            'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
        ]);

        // Scope defines the data your app has permission to access.
        // Learn more about scopes at https://developer.xero.com/documentation/oauth2/scopes
        $options = [
            //openid email profile offline_access assets projects accounting.settings  accounting.contacts accounting.journals.read accounting.reports.read accounting.attachments
            'scope' => ['accounting.transactions']
        ];

        // This returns the authorizeUrl with necessary parameters applied (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl($options);

        // Save the state generated for you and store it to the session.
        // For security, on callback we compare the saved state with the one returned to ensure they match.
        Yii::$app->cache->set('oauth2state',  $provider->getState());

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit();
    }

    /**
     * callback from xero after auth
     * @return array|string[]|void
     */
    public function actionCallback()
    {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => Yii::$app->xero->clientId,
            'clientSecret'            => Yii::$app->xero->clientSecret,
            'redirectUri'             => Url::to(['xero/callback'], true),// 'http://localhost:8888/xero-php-oauth2-starter/callback.php',
            'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
            'urlAccessToken'          => 'https://identity.xero.com/connect/token',
            'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
        ]);

        // If we don't have an authorization code then get one
        if (!isset($_GET['code'])) {
            echo "Something went wrong, no authorization code found";
            exit("Something went wrong, no authorization code found");

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($_GET['state'] !== Yii::$app->cache->get('oauth2state'))) {
            echo "Invalid State";
            //unset($_SESSION['oauth2state']);
            //Yii::$app->cache->clear('oauth2state')
            exit('Invalid state');
        } else {

            try {
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);

                //XeroAPI\XeroPHP\Configuration
                $config = Configuration::getDefaultConfiguration()
                    ->setAccessToken( (string) $accessToken->getToken() );

                $identityInstance = new \XeroAPI\XeroPHP\Api\IdentityApi(
                    new \GuzzleHttp\Client(),
                    $config
                );

                $result = $identityInstance->getConnections();

                $accessTokenValues = $accessToken->getValues();

                $idToken = isset($accessTokenValues["id_token"])? $accessTokenValues["id_token"]: null;

                // Save my tokens, expiration tenant_id
                Yii::$app->xero->setToken(
                    $accessToken->getToken(),
                    $accessToken->getExpires(),
                    $result[0]->getTenantId(),
                    $accessToken->getRefreshToken(),
                    $idToken
                );

                // redirect to app

                header('Location: ' . Yii::$app->params['adminAppUrl'] . 'bank-transactions-sync');
                exit();

                /*return [
                    "operation" => "success",
                    "token" => Yii::$app->cache->get("xero-session")
                ];*/

            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                echo "Callback failed" . $e->getMessage();
                die();
                /*return [
                    "operation" => "success",
                    "message" => "Callback failed"
                ];*/
            }
        }
    }

    /**
     * start transaction synchronisation
     * @return string[]|void
     */
    public function actionSync()
    {
        //check for access

        if(!Yii::$app->xero->getToken()) {
            return [
                "operation" => "error",
                "redirect" => Url::to(['xero/auth'], true),
            ];
        }

        //todo: ability to resume sync + sync all again

        $page = Yii::$app->request->get("page", 1);

        try {

            return Yii::$app->xero->syncTransactions($page, false);

        } catch (Exception $e) {
            return [
                "operation" => "error",
                "message" => 'Exception when calling AccountingApi->getBankTransactionsHistory: '. $e->getMessage()
            ];
        }
    }
}