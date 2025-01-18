<?php

namespace staff\modules\v1\controllers;

use Yii;
use common\models\Ticket;
use common\models\TicketComment;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class TicketController extends Controller
{
    public function behaviors() {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => \Yii::$app->params['allowedOrigins'],
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
    public function actions() {
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
     * @return array
     */
    public function actionStats() {

        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $data['avg_response_time'] = Ticket::find()
            ->andWhere(['!=', 'ticket_status', Ticket::STATUS_PENDING])
            ->filterByDateRange($start_date, $end_date)
            ->average('response_time');

        $data['avg_resolution_time'] = Ticket::find()
            ->andWhere(['!=', 'ticket_status', Ticket::STATUS_PENDING])
            ->filterByDateRange($start_date, $end_date)
            ->average('resolution_time');

        $data['assigned'] = Ticket::find()
            ->andWhere(['!=', 'ticket_status', Ticket::STATUS_COMPLETED])
            ->andWhere(['staff_id' => Yii::$app->user->getId()])
            ->filterByDateRange($start_date, $end_date)
            ->count();

        $data['unassigned'] = Ticket::find()
            ->andWhere(['!=', 'ticket_status', Ticket::STATUS_COMPLETED])
            ->andWhere(new Expression('staff_id IS NULL'))
            ->filterByDateRange($start_date, $end_date)
            ->count();

        $data['totalPending'] = Ticket::find()
            ->andWhere(['ticket_status' => Ticket::STATUS_PENDING])
            ->filterByDateRange($start_date, $end_date)
            ->count();

        $data['totalInProgress'] = Ticket::find()
            ->andWhere(['ticket_status' => Ticket::STATUS_IN_PROGRESS])
            ->filterByDateRange($start_date, $end_date)
            ->count();

        $data['totalCompleted'] = Ticket::find()
            ->andWhere(['ticket_status' => Ticket::STATUS_COMPLETED])
            ->filterByDateRange($start_date, $end_date)
            ->count();

        return $data;
    }

    /**
     * Get all tickets
     * @param $store_uuid
     * @return ActiveDataProvider
     */
    public function actionList() {

        $status = Yii::$app->request->get('status');
        $squery = Yii::$app->request->get('query');

        $query = Ticket::find()
            ->orderBy('updated_at DESC');

        if(!is_null($status)) {
            $query->andWhere(['ticket_status' => $status]);
        }

        if($squery) {
            $query->joinWith(['staff', 'candidate'])
                ->andWhere([
                    'OR',
                    ['like', 'staff.staff_name', $squery],
                    ['like', 'candidate.candidate_name', $squery]
                ]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create voucher
     * @return array
     */
    public function actionCreate() {
 
        $model = new Ticket();
        $model->staff_id = Yii::$app->request->getBodyParam("staff_id");
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
//        $model->staff_id =  Yii::$app->user->getId();
        $model->ticket_detail =  Yii::$app->request->getBodyParam("detail");
        $model->ticket_status = Ticket::STATUS_PENDING;

        $model->attachments = ArrayHelper::getColumn(
            Yii::$app->request->getBodyParam("attachments"),
            'Key'
        );

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('app', "Ticket created successfully"),
        ];
    }

    /**
     * Assign ticket to staff 
     * @return array
     */
    public function actionAssign($ticket_uuid) {

        //validate access

        $model = $this->findModel($ticket_uuid);

        $model->staff_id =  Yii::$app->request->getBodyParam("staff_id");

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        $model->sendTicketAssignedMail();

        return [
            "operation" => "success",
            "message" => Yii::t('app', "Staff assigned successfully"),
        ];
    }

    /**
     * Create voucher
     * @return array
     */
    public function actionComment($ticket_uuid) {

        //validate access

        $ticket = $this->findModel($ticket_uuid);

        $status = Yii::$app->request->getBodyParam("status");

        if ($ticket->ticket_status != $status) {
            $ticket->ticket_status = $status;
            $ticket->save();
        }

        $model = new TicketComment();
        $model->ticket_uuid = $ticket_uuid;
        $model->staff_id =  Yii::$app->user->getId();
        $model->ticket_comment_detail =  Yii::$app->request->getBodyParam("comment_detail");

        $model->attachments = ArrayHelper::getColumn(
            Yii::$app->request->getBodyParam("attachments"),
            'Key'
        );

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('app', "Ticket comment added successfully"),
        ];
    }

    /**
     * return ticket comments
     */  
    public function actionComments($id)
    {
        return $this->findModel($id)->ticketComments;
    }

    /**
     * Return Ticket detail
     * @param $ticket_uuid
     * @return Ticket|array
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Finds the Ticket model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $ticket_uuid
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($ticket_uuid)
    {
        $model = Ticket::find ()
            ->where([
                'ticket_uuid' => $ticket_uuid
            ])
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested record does not exist.');
        }
    }
}

