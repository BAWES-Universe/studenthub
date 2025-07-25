<?php

namespace staff\modules\v1\controllers;

use common\models\PermissionUser;
use Yii;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;


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
                    'X-Pagination-Total-Count',
                    'X-totalPendingRequests',
                    'X-totalClosedRequests',
                    'X-totalInvitations',
                    'X-totalNoOfHours',
                    'X-totalNoOfMinutes',
                    'X-totalNoOfSeconds',
                    'X-totalVelocity'
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
     * @param $type
     * @param $id
     * @return PermissionUser[]
     */
    public function actionUserPermission($type, $id) {
        $query = PermissionUser::find()
            ->select([
                'permission_user.*',
                'permission_sub_section.*',
                'permission_section.*'
            ])
            ->innerJoin('permission_sub_section', 'permission_sub_section.permission_sub_section_uuid = permission_user.permission_sub_section_uuid')
            ->innerJoin('permission_section', 'permission_section.permission_uuid = permission_sub_section.permission_uuid');
            
        if ($type == 'staff') {
            $query->where(['permission_user.staff_id' => $id]);
        } else {
            $query->where(['permission_user.admin_id' => $id]);
        }
        
        $data = $query->asArray()->all();
        return array_map(function ($item) {
            $item['companies'] = json_decode($item['companies'], true);
            return $item;
        }, $data);
    }
}
