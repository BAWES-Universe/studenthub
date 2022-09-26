<?php


namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;


class JiraController extends Controller
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
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();

        // Return Header explaining what options are available for next request
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * return list of issues
     * @return mixed
     */
    public function actionIssues() {

        $accountId = Yii::$app->request->get('accountId');
        $status = Yii::$app->request->get('status');

        $jql = [];

        if($accountId) {
            //$jql[] = 'assignee = "'.$accountId.'"';
            $jql[] = 'assignee in ('.$accountId.')';
        }

        if($status) {
            $jql[] = 'status = "'.$status.'"';
        }

        $request = Yii::$app->jira->get('search', [
            'jql' => implode (" AND ", $jql) . ' ORDER BY created DESC'
        ]);

        return $request;
    }

    /**
     * return list of users
     * @return mixed
     */
    public function actionUsers() {

        $query = Yii::$app->request->get('query');

        $jql = [
            'accountType' => 'atlassian',
            //'active' => true
        ];

        //users/search
        //user/search/query
        //user/assignable/search
        //user/search?username=.

        $request = Yii::$app->jira->get('users/search', [
            'maxResults' => 1000,
            //'query' => 'accountType.atlassian'
            'jql' => implode (" AND ", $jql) . ' ORDER BY created DESC'
        ]);

        return $request;
    }
}