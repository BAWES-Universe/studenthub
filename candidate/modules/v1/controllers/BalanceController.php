<?php

namespace candidate\modules\v1\controllers;

use common\models\Transfer;
use common\models\WalletUser;
use Yii;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\data\ActiveDataProvider;
use common\models\BalanceAccount;

/**
 * Balance controller
 */
class BalanceController extends Controller
{
    /**
     * @return array
     */
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
                    'X-Pagination-Total-Count',
                    'Balance'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

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
     * return user payable list
     * @return ActiveDataProvider
     */
    public function actionPayableList()
    {
        $user = WalletUser::findByEmail(Yii::$app->user->identity->candidate_email);

        $account = \common\models\BalanceAccount::find()
            ->andWhere([
                'account_uuid' => $user->user_uuid,
                'type' => BalanceAccount::TYPE_USER_PAYABLE
            ])
            ->one();

        if(!$account) {
            $account = new BalanceAccount;
            $account->account_uuid = $user->user_uuid;
            $account->type = BalanceAccount::TYPE_USER_PAYABLE;
            $account->save();
        }

        $headers = Yii::$app->response->headers;

        $headers->add('Balance', $account->balance);

        $query = $account->getBalanceTransactions()
            ->orderBy('created_at DESC, balance_transaction_uuid DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * pay by wallet amount
     * @return ActiveDataProvider
     */
    public function actionPayByWallet()
    {
        $to_uuid = Yii::$app->request->getBodyParam('to_uuid');
        $email = Yii::$app->request->getBodyParam('email');
        $username = Yii::$app->request->getBodyParam('username');
        $amount = Yii::$app->request->getBodyParam('amount');

        $user = WalletUser::findByEmail(Yii::$app->user->identity->candidate_email);

        $to = null;

        if($to_uuid)
        {
            $to = $this->findUser($to_uuid);
        }
        else if($email)
        {
            $to = WalletUser::findByEmail($email);
        }
        else if($username)
        {
            $to = WalletUser::findByUsername($username);
        }

        if(!$to)
        {
            return [
                'operation' => 'error',
                'message' => 'User not found'
            ];
        }

        if($amount < 0.001) {
            return [
                'operation' => 'error',
                'message' => 'Amount can not be less than 0.001'
            ];
        }

        $account = \common\models\BalanceAccount::find()
            ->andWhere([
                'account_uuid' => $user->user_uuid,
                'type' => BalanceAccount::TYPE_USER_PAYABLE
            ])
            ->one();

        if(!$account) {
            $account = new BalanceAccount;
            $account->account_uuid = $user->user_uuid;
            $account->type = BalanceAccount::TYPE_USER_PAYABLE;
            $account->save();
        }

        if($amount > $account->balance)
        {
            return [
                'operation' => 'error',
                'message' => 'Transaction amount can not be greater then balance'
            ];
        }

        BalanceAccount::payByWallet($user, $amount, $to);

        return [
            'operation' => 'success',
            'message' => 'Transaction successful'
        ];
    }

    /**
     * initialize transfer
     * @return array|string[]
     */
    public function actionInitTransfer()
    {
        $user = WalletUser::findByEmail(Yii::$app->user->identity->candidate_email);

        $transfer = new Transfer();
        $transfer->user_uuid = $user->user_uuid;
        $transfer->bank_uuid = $user->bank_uuid;//Yii::$app->request->getBodyParam('bank_uuid');
        $transfer->transfer_benef_name = $user->bank_account_name;
        $transfer->transfer_benef_iban = $user->iban;
        $transfer->transfer_total = Yii::$app->request->getBodyParam('transfer_total');//< balance
        $transfer->transfer_status = Transfer::STATUS_INITIATED;

        if(!$transfer->save())
        {
            return [
                'operation' => 'error',
                'message' => $transfer->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => 'Transfer initialized successful'
        ];
    }

    /**
     * @param $id
     * @return User|null
     * @throws \yii\web\NotFoundHttpException
     */
    protected function findUser($id)
    {
        $model = WalletUser::findOne($id);

        if ($model !== null) {
            return $model;
        } else {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }
    }
}
