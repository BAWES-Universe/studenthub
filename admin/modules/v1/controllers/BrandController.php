<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Brand;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Brand controller - Manage brand as Admin
 */
class BrandController extends Controller
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
     * Return a List of Brand Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Brand::find();
        
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load brand details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a brand account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new brand
        $model = new Brand();

        $model->brand_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->brand_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        
        $brand_logo = Yii::$app->request->getBodyParam('logo');
        
        if($brand_logo)
            $model->setLogo($brand_logo);
        
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
                    "message" => "We've faced a problem creating the brand, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Brand created successfully"
        ];
    }

    /**
     * Create a brand account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Brand not found."
                ];
        }

        $model->brand_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->brand_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        
        $brand_logo = Yii::$app->request->getBodyParam('logo');
        
        if($brand_logo)
            $model->setLogo($brand_logo);
        
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
                    "message" => "We've faced a problem updating the brand, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Brand successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $brand = $this->findModel((int)$id);

        if(!$brand) {
            return [
                "operation" => "error",
                "message" => "Brand not found or already deleted"
            ];
        }

        // Delete brand
        $brand->delete();

        return [
            "operation" => "success",
            "message" => "Brand deleted successfully"
        ];
    }
    
    /**
     * Finds the Brand model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Brand::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
