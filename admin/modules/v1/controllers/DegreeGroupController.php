<?php
namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\DegreeGroup;
use yii\web\NotFoundHttpException;


/**
 * DegreeGroup controller - Manage degree_group as Admin
 */
class DegreeGroupController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
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
            'class' => \yii\filters\auth\HttpBearerAuth::class,
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
     * Return a List of DegreeGroup Accounts available.
     */
    public function actionList()
    {
        $query = DegreeGroup::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load degree_group details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a degree_group account
     */
    public function actionCreate()
    {
        // Attempt to create new degree_group
        $model = new DegreeGroup();

        $model->degree_group_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->degree_group_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->degree_group_sort_order = Yii::$app->request->getBodyParam("sort_order");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the degree_group, please contact us for assistance."
                ];
            }
        }

        Yii::info('[DegreeGroup Created] DegreeGroup "' . $model->degree_group_name_en . '" created by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "DegreeGroup created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a degree_group account
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
                "message" => "DegreeGroup not found."
            ];
        }

        $model->degree_group_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->degree_group_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->degree_group_sort_order = Yii::$app->request->getBodyParam("sort_order");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the degree_group, please contact us for assistance."
                ];
            }
        }

        Yii::info('[DegreeGroup Updated] DegreeGroup "' . $model->degree_group_name_en . '" updated by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "DegreeGroup successfully updated"
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
        $degree_group = $this->findModel($id);

        Yii::info('[DegreeGroup Deleted] DegreeGroup "' . $degree_group->degree_group_name_en . '" account deleted by Admin: "' . Yii::$app->user->identity->admin_name . '"', __METHOD__);

        if ($degree_group->delete()) {

            return [
                "operation" => "success",
                "message" => "DegreeGroup deleted successfully"
            ];
        } else {
            return [
                "operation" => "error",
                "message" => "DegreeGroup deleted failed. Please try again."
            ];
        }

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Finds the DegreeGroup model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DegreeGroup the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DegreeGroup::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
