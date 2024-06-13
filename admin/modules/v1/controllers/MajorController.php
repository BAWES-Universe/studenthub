<?php
namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Major;
use yii\web\NotFoundHttpException;


/**
 * Major controller - Manage major as Admin
 */
class MajorController extends Controller
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
     * Return a List of Major Accounts available.
     */
    public function actionList()
    {
        $query = Major::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load major details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a major account
     */
    public function actionCreate()
    {
        // Attempt to create new major
        $model = new Major();

        $model->major_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->major_name_ar = Yii::$app->request->getBodyParam("name_ar");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the major, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Major Created] Major "' . $model->major_name_en . '" created by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Major created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a major account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Major not found."
            ];
        }

        $model->major_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->major_name_ar = Yii::$app->request->getBodyParam("name_ar");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the major, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Major Updated] Major "' . $model->major_name_en . '" updated by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Major successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $major = $this->findModel($id);

        Yii::info('[Major Deleted] Major "' . $major->major_name_en . '" account deleted by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        if ($major->delete()) {

            return [
                "operation" => "success",
                "message" => "Major deleted successfully"
            ];
        } else {
            return [
                "operation" => "error",
                "message" => "Major deleted failed. Please try again."
            ];
        }

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Finds the Major model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Major the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Major::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
