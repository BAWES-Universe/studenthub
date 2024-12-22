<?php

namespace staff\modules\v1\controllers;

use common\models\StoreManager;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Store;
use yii\web\NotFoundHttpException;

/**
 * Store controller - Manage store as Admin
 */
class StoreController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
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
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
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
     * Return a List of Store Accounts available.
     * @param null $companyId
     * @return ActiveDataProvider
     */
    public function actionList($companyId = null)
    {
        $query = Store::find();
            /*->with([
                'candidates', 
                'candidates.store', 
                'candidates.company', 
                'candidates.bank',
                'candidates.university'
            ]);*/

        if ($companyId) {
            $query->andWhere(['store.company_id' => $companyId]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    /**
     * Create a store account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new store
        $model = new Store();
        $model->scenario = 'create';
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->store_name = Yii::$app->request->getBodyParam("name");
        $model->store_location = Yii::$app->request->getBodyParam("location");
        $model->brand_uuid = Yii::$app->request->getBodyParam("brand_uuid");
        $model->mall_uuid = Yii::$app->request->getBodyParam("mall_uuid");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the store, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Store Created - '.$model->store_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        $manager = Yii::$app->request->getBodyParam("storeManager");

        if($manager && !empty($manager['name'])) {
            $mmodel = new StoreManager();
            $mmodel->store_id = $model->store_id;
            $mmodel->company_id = $model->company->parent_company_id?
                $model->company->parent_company_id: $model->company_id;
            $mmodel->name = $manager['name'];
            $mmodel->email = $manager['email'];
            $mmodel->phone_number = $manager['phone_number'];
            $mmodel->setPassword($manager['password']);
            $mmodel->save(false);
        }

        return [
            "operation" => "success",
            "message" => "Store successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * update store manager
     * @param type $id
     * @return type
     */
    public function actionUpdateManager($id)
    {
        $model = $this->findModel($id);
        $model->scenario = 'update_manager';

        $model->store_manager_uuid = Yii::$app->request->getBodyParam("contact_uuid");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the store, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Store Manager Updated - '.$model->store_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Store successfully updated"
        ];
    }

    /**
     * remove store manager
     * @param type $id
     * @return type
     */
    public function actionRemoveManager($id)
    {
        $model = $this->findModel($id);

        $model->scenario = 'update_manager';

        $model->store_manager_uuid = null;

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the store, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Store Manager Removed - '.$model->store_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Store successfully updated"
        ];
    }
    
    /**
     * Create a store account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = 'update';
        $model->store_name = Yii::$app->request->getBodyParam("name");
        $model->store_location = Yii::$app->request->getBodyParam("location");
        $model->brand_uuid = Yii::$app->request->getBodyParam("brand_uuid");
        $model->mall_uuid = Yii::$app->request->getBodyParam("mall_uuid");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the store, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Store Updated - '.$model->store_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        $manager = Yii::$app->request->getBodyParam("storeManager");

        if($manager && !empty($manager['name'])) {
            if ($model->storeManager) {
                $mmodel = $model->storeManager;
            } else {
                $mmodel = new StoreManager();
            }

            $mmodel->store_id = $model->store_id;
            $mmodel->company_id = $model->company->parent_company_id?
                $model->company->parent_company_id: $model->company_id;
            $mmodel->name = $manager['name'];
            $mmodel->email = $manager['email'];
            $mmodel->phone_number = $manager['phone_number'];

            if($manager['password'])
                $mmodel->setPassword($manager['password']);

            $mmodel->save(false);
        }

        return [
            "operation" => "success",
            "message" => "Store successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $store = $this->findModel($id);

        //Shouldn't be able to delete a store that has candidates assigned to it

        if($store->candidates) {
            return [
                "operation" => "error",
                "message" => "Store have some candidates assigned to it."
            ];
        }

        Yii::info('[Store Deleted - '.$store->store_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        // soft Delete store
        $store->softDelete();

        return [
            "operation" => "success",
            "message" => "Store deleted successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * store View
     * @param  integer $id
     * @return array
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionContracts($id)
    {
        return $this->findModel($id)
            ->getContracts();
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionLogin($id)
    {
        $model = $this->findModel($id);

        if(!$model->storeManager) {
            return [
                "operation" => "error",
                'message' => "No store manager account found!",
            ];
        }

        $model->storeManager->generateAuthKey();

        if(!$model->storeManager->save(false)) {
            return [
                "operation" => "error",
                'message' => $model->storeManager->errors,
                'redirect' => Yii::$app->params['managerAppUrl']
            ];
        }

        $url = Yii::$app->params['managerAppUrl']. '?auth_key='.$model->storeManager->auth_key;

        return [
            'redirect' => $url
        ];
    }

    /**
     * Finds the Store model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Store::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
