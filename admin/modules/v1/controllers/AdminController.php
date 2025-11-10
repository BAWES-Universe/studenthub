<?php

namespace admin\modules\v1\controllers;

use admin\models\AdminToken;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Admin;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * Admin controller - Manage Admin accounts as Admin
 * 
 * @OA\Tag(
 *     name="Admin Management",
 *     description="Manage admin accounts, permissions, and settings"
 * )
 */
class AdminController extends Controller
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
     * List all admin accounts
     * 
     * @OA\Get(
     *     path="/admin",
     *     summary="List admin accounts",
     *     description="Get a paginated list of all admin accounts",
     *     tags={"Admin Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of admin accounts",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Admin"))
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Admin::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Get admin account details
     * 
     * @OA\Get(
     *     path="/admin/{id}",
     *     summary="Get admin account",
     *     description="Get details of a specific admin account",
     *     tags={"Admin Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Admin ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin account details",
     *         @OA\JsonContent(ref="#/components/schemas/Admin")
     *     ),
     *     @OA\Response(response=404, description="Admin not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return Admin
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a new admin account
     * 
     * @OA\Post(
     *     path="/admin",
     *     summary="Create admin account",
     *     description="Create a new admin account",
     *     tags={"Admin Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", description="Admin name"),
     *             @OA\Property(property="email", type="string", format="email", description="Admin email"),
     *             @OA\Property(property="password", type="string", format="password", description="Admin password"),
     *             @OA\Property(property="limited_access", type="boolean", description="Limited access flag"),
     *             @OA\Property(property="enable_two_step_auth", type="boolean", description="Enable two-step authentication")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin account created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Admin account successfully created")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return array|string[]
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $model = new Admin();
        $model->scenario = "newAccount";

        $model->admin_name = Yii::$app->request->getBodyParam("name");
        $model->admin_email =Yii::$app->request->getBodyParam("email");
        $model->admin_password_hash = Yii::$app->request->getBodyParam("password");
        $model->admin_limited_access = Yii::$app->request->getBodyParam("limited_access");
        $model->enable_two_step_auth = Yii::$app->request->getBodyParam("enable_two_step_auth");

        if (!$model->signup())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Admin Account Created] Admin "'.$model->admin_email.'" created by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Admin account successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Update admin account
     * 
     * @OA\Patch(
     *     path="/admin/{id}",
     *     summary="Update admin account",
     *     description="Update an existing admin account",
     *     tags={"Admin Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Admin ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", description="Admin name"),
     *             @OA\Property(property="email", type="string", format="email", description="Admin email"),
     *             @OA\Property(property="limited_access", type="boolean", description="Limited access flag"),
     *             @OA\Property(property="enable_two_step_auth", type="boolean", description="Enable two-step authentication")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin account updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Admin account successfully updated")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Admin not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Admin account not found"
                ];
        }

        $model->admin_name = Yii::$app->request->getBodyParam("name");
        $model->admin_email =Yii::$app->request->getBodyParam("email");
        $model->admin_limited_access = Yii::$app->request->getBodyParam("limited_access");
        $model->enable_two_step_auth = Yii::$app->request->getBodyParam("enable_two_step_auth");

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
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Admin Account Updated] Admin "'.$model->admin_email.'" updated by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Admin account successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete admin account
     * 
     * @OA\Delete(
     *     path="/admin/{id}",
     *     summary="Delete admin account",
     *     description="Delete an admin account",
     *     tags={"Admin Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Admin ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin account deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Admin account deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Admin not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
//        $count = Admin::find()->andWhere(['admin_limited_access'=>1])->count();
//        if ($count == 1) {
//            return [
//                "operation" => "error",
//                "message" => "One admin should be exist"
//            ];
//        }
        $member = $this->findModel((int)$id);

        if($member)
        {
            Yii::info('[Admin Account Deleted] Admin "'.$member->admin_email.'" deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

            // Delete the account
            $member->delete();

            return [
                "operation" => "success",
                "message" => "Admin account deleted successfully"
            ];
        }else{
            return [
                "operation" => "error",
                "message" => "Admin account not found or already deleted"
            ];
        }

        // Error for cases not accounted for
        return [
            "operation" => "error",
            "message" => "Unknown error occured, please contact us for assistance."
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Reset admin password
     * 
     * @OA\Patch(
     *     path="/admin/reset-password/{id}",
     *     summary="Reset admin password",
     *     description="Generate and send a new password to the admin's email",
     *     tags={"Admin Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Admin ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="New password sent to registered email successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Admin not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "admin not found",
                "code" => 1
            ];
        }

        $password = Yii::$app->security->generateRandomString(5);

        $model->setPassword($password);
        $model->save(false);

        //Send Email to user
        Admin::passwordMail($model, $password);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
    }

    /**
     * Update admin status
     * 
     * @OA\Patch(
     *     path="/admin/status-change/{id}",
     *     summary="Update admin status",
     *     description="Update the status of an admin account (active/inactive)",
     *     tags={"Admin Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Admin ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", description="Status (0=inactive, 1=active)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin status updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Admin status changed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Admin not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param  integer $id
     * @return array
     */
    public function actionStatus($id)
    {
        $status = Yii::$app->request->post('status', 0);
        $model = $this->findModel((int)$id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Invalid Account"
            ];
        }
        $model->admin_status = $status;
        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }
        // reset token
        AdminToken::deleteAll(['admin_id'=>$id]);
        return [
            "operation" => "success",
            "message" => "Admin status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    /**
     * Finds the admin model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Admin the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Admin::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
