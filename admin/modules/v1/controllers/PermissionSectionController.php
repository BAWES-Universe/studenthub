<?php

namespace admin\modules\v1\controllers;

use common\models\Company;
use common\models\PermissionSection;
use common\models\PermissionSubSection;
use common\models\PermissionUser;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Permission Section controller - Manage store as Admin
 */
class PermissionSectionController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
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
            'class' => HttpBearerAuth::class,
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
     */
    public function actionList()
    {
        $query = PermissionSection::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    public function actionCreate()
    {
        // Attempt to create new request
        $model = new PermissionSection();
        $model->section_name = Yii::$app->request->getBodyParam("section_name");

        // Get companies array from request
        $companies = Yii::$app->request->getBodyParam("companies", []);
        if (!is_array($companies)) {
            return [
                "operation" => "error",
                "message" => "Companies must be an array of company IDs."
            ];
        }

        // Validate all company IDs exist
        if (Company::find()->where(['company_id' => $companies])->count() !== count($companies)) {
            return [
            "operation" => "error",
            "message" => "One or more company IDs are invalid."
            ];
        }
        $model->companies = $companies;

        if (!$model->save()) {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Permission section, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Permission Section created successfully"
        ];
    }

    public function actionCreateSubSection()
    {
        // Attempt to create new request
        $model = new PermissionSubSection();

        $model->sub_section_name = Yii::$app->request->getBodyParam("name");
        $model->sub_section_slug = Yii::$app->request->getBodyParam("slug");
        $model->permission_uuid = Yii::$app->request->getBodyParam("permission_uuid");

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
                    "message" => "We've faced a problem creating the Permission section, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Permission Section created successfully"
        ];
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if(!$model){
            return [
                "operation" => "error",
                "message" => "Permission not found."
            ];
        }

        // Get companies array from request
        $companies = Yii::$app->request->getBodyParam("companies", []);
        if (!is_array($companies)) {
            return [
                "operation" => "error",
                "message" => "Companies must be an array of company IDs."
            ];
        }

        // Validate all company IDs exist
        if (Company::find()->where(['company_id' => $companies])->count() !== count($companies)) {
            return [
            "operation" => "error",
            "message" => "One or more company IDs are invalid."
            ];
        }
        $model->companies = $companies;

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
                    "message" => "We've faced a problem creating the Permission section, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Permission Section successfully updated"
        ];
    }

    public function actionUpdateSubSection($id)
    {
        $model = PermissionSubSection::findOne(['permission_sub_section_uuid'=>$id]);

        if(!$model){
            return [
                "operation" => "error",
                "message" => "Permission not found."
            ];
        }

        $model->sub_section_name = Yii::$app->request->getBodyParam("name");
        $model->sub_section_slug = Yii::$app->request->getBodyParam("slug");

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
                    "message" => "We've faced a problem creating the Permission section, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Permission Section successfully updated"
        ];
    }

    public function actionSetPermission($id)
    {

        $type = Yii::$app->request->getBodyParam("type");
        $permission = Yii::$app->request->getBodyParam("permission");

        if ($type == 'staff') {
            PermissionUser::deleteAll(['staff_id'=>$id]);
        } else {
            PermissionUser::deleteAll(['admin_id'=>$id]);
        }

        if (!empty($permission) && is_array($permission)) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($permission as $key => $per) {
                    if (empty($per)) {
                        continue;
                    }

                    $model = new PermissionUser([
                        'permission_sub_section_uuid' => $key,
                        $type . '_id' => $id,
                    ]);

                    if (!$model->save()) {
                        $transaction->rollBack();
                        return [
                            "operation" => "error",
                            "message" => $model->errors
                        ];
                    }
                }
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                return [
                    "operation" => "error",
                    "message" => $e->getMessage()
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "User permission successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Note not found or already deleted"
            ];
        }

        // Delete note
        $model->delete();

        return [
            "operation" => "success",
            "message" => "Note deleted successfully"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDeleteSubSection($id)
    {
        $model = PermissionSubSection::findOne(['permission_sub_section_uuid'=>$id]);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Note not found or already deleted"
            ];
        }

        // Delete note
        $model->delete();

        return [
            "operation" => "success",
            "message" => "Note deleted successfully"
        ];
    }

    /**
     * load store details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * @param $type
     * @param $id
     * @return PermissionUser[]
     */
    public function actionUserPermission($type, $id) {
        $query = PermissionUser::find()
            ->select([
                'permission_user.*',
                'permission_sub_section.permission_uuid as permission_uuid'
            ])
            ->innerJoin('permission_sub_section', 'permission_sub_section.permission_sub_section_uuid = permission_user.permission_sub_section_uuid');
            
        if ($type == 'staff') {
            $query->where(['permission_user.staff_id' => $id]);
        } else {
            $query->where(['permission_user.admin_id' => $id]);
        }
        
        $data = $query->asArray()->all();
        return $data ? $data : [];
    }
    
    /**
     * Finds the Store model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return PermissionSection the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PermissionSection::findOne(['permission_uuid'=>$id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
