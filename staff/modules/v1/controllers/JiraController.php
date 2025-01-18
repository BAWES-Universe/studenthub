<?php


namespace staff\modules\v1\controllers;

use staff\models\Staff;
use Yii;
use yii\helpers\ArrayHelper;
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
            'class' => \yii\filters\Cors::class,
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
            $jql[] = 'status="'.$status.'"';
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

        //todo: filter atlassian + active accounts only

        $jql = [
            'accountType=atlassian',
       //     'active' => true
        ];

        //users/search
        //user/search/query
        //user/assignable/search
        //user/search?username=.

        $response = Yii::$app->jira->get('users/search', [
            'maxResults' => 1000,
            //'query' => 'accountType.atlassian'
            'jql' => implode (" AND ", $jql) . ' ORDER BY created DESC'
        ]);

        $staffs = \status\models\Staff::find()->all();

        $users = [];

        foreach ($response as $jiraUser)
        {
            //$jiraUser = $this->_object_to_array($row);
            //print_r($jiraUser);echo $jiraUser['emailAddress']; die();

            if(!$jiraUser->active || $jiraUser->accountType != 'atlassian')
                continue;

            $staffUser = isset($jiraUser->emailAddress)? $this->_searchByEmail($staffs, $jiraUser->emailAddress): [];

            $users[] = array_merge([
                //'name' => $jiraUser->name,
                'displayName' => $jiraUser->displayName,
                'emailAddress' => isset($jiraUser->emailAddress)? $jiraUser->emailAddress: null,
                'accountId' => $jiraUser->accountId,
                'active' => $jiraUser->active,
                'avatarUrls' => $jiraUser->avatarUrls
            ], $staffUser);
        }

        return $users;
    }

    function _object_to_array($obj, &$arr = []){

        if(!is_object($obj) && !is_array($obj)){
            $arr = $obj;
            return $arr;
        }

        foreach ($obj as $key => $value) {
            if (!empty($value)) {
                $arr[$key] = array();
                $this->_object_to_array($value, $arr[$key]);
            } else {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

    /**
     * @param $staffs
     * @param $emailAddress
     * @return void
     */
    private function _searchByEmail($staffs, $emailAddress)
    {
        foreach ($staffs as $staff) {
            if($staff['staff_email'] == $emailAddress) {

                return [
                    'staff_id' => $staff['staff_id'],
                    'staff_name' => $staff['staff_name'],
                    'staff_job_title' => $staff['staff_job_title'],
                    'staff_salary' => $staff['staff_salary'],
                    'staff_salary_currency' => $staff['staff_salary_currency'],
                ];
            }
        }

        return [];
    }
}