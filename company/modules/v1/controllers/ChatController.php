<?php

namespace company\modules\v1\controllers;

use Yii;
use common\models\Chat;
use common\models\ChatMessage;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class ChatController extends Controller
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
        $behaviors['authenticator']['except'] = ['options', 'click'];

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
     * Return a List
     */
    public function actionList()
    {
        $company_id = Yii::$app->request->get("company_id");
        $store_id = Yii::$app->request->get("store_id");
        $staff_id = Yii::$app->request->get("staff_id");
        $candidate_id = Yii::$app->request->get("candidate_id");

        $query = Chat::find()
            ->andWhere(['contact_uuid' => Yii::$app->user->getId()])
            ->orderBy("created_at DESC");

        if ($candidate_id) {
            $query->andWhere(['candidate_id' => $candidate_id]);
        }

        if ($company_id) {
            $query->andWhere(['company_id' => $company_id]);
        }

        if ($store_id) {
            $query->andWhere(['store_id' => $store_id]);
        }

        if ($staff_id) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionMessages($id)
    {
        $last_index = Yii::$app->request->get("last_index");

        $chat = $this->findModel($id); //validate

        $query = $chat->getChatMessages()
            ->orderBy('message_index DESC');
        //->orderBy("created_at DESC");

        if($last_index > 0) {
            $query->andWhere(['<', 'message_index', $last_index]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    public function actionNewMessages($id) {

        $last_index = Yii::$app->request->get('last_index');

        $chat = $this->findModel($id); //validate

        $query = $chat->getChatMessages()
            ->limit(20)
            ->orderBy('message_index DESC');

        if($last_index) {
            $query->andWhere(['>', 'message_index', $last_index]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    /**
     * @return array
     */
    public function actionUnreadCount()
    {
        $conversations = Chat::find()
            ->where([
                'contact_uuid'=> Yii::$app->user->getId(),
            ])
            ->all();

        $counts = [
            'total' => 0,
            'totalConversation' => sizeof($conversations),
        ];

        foreach($conversations as $conversation) {
            $counts[$conversation->chat_uuid] = [
                'unreadMessageCount' => $conversation->getContactUnreadCount(),
                'recentMessage' => $conversation->recentMessage
            ];

            $counts['total'] += $counts[$conversation->chat_uuid]['unreadMessageCount'];
        }

        return $counts;
    }

    /**
     * start chat with candidate
     * @return array
     */
    public function actionStartChat() {

        $candidate_id = Yii::$app->request->getBodyParam("candidate_id");

        $candidate = Yii::$app->companyManager->getCompany()
            ->getCandidates()
            ->andWhere(['candidate_id' => $candidate_id])
            ->one();

        if (!$candidate) {
            throw new NotFoundHttpException('The requested record does not exist.');
        }

        $model = Chat::find()
            ->where([
                "store_id" => $candidate->store_id,
                "contact_uuid" => Yii::$app->user->getId(),
                'candidate_id' => $candidate_id
            ])
            ->one();

        if ($model) {
            return [
                "operation" => "success",
                "chat" => $model
            ];
        }

        $model = new Chat();
        $model->candidate_id = $candidate_id;
        $model->store_id = $candidate->store_id;
        $model->company_id = $candidate->store->company_id;
        $model->parent_company_id = $candidate->store->company->parent_company_id;
        $model->contact_uuid = Yii::$app->user->getId();

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "chat" => $model,
            "message" => "Chat initiated"
        ];
    }

    /**
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionSendMessage()
    {
        $chat_uuid = Yii::$app->request->getBodyParam("chat_uuid");
        $message = Yii::$app->request->getBodyParam("message");

        $this->findModel($chat_uuid); //validate

        $model = new ChatMessage();
        $model->chat_uuid = $chat_uuid;
        $model->message = $message;
        $model->from = ChatMessage::FROM_CONTACT;
        $model->status = ChatMessage::STATUS_SENT;

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Message sent"
        ];
    }

    /**
     * Mark messages as read on conversation
     */
    public function actionMarkRead($id)
    {
        $model = $this->findModel($id);

        //mark messages from senders as read

        \common\models\ChatMessage::updateAll([
            'status' => ChatMessage::STATUS_READ
        ], [
            "AND",
            [
                "!=", 'from', ChatMessage::FROM_CONTACT
            ],
            ['chat_uuid' => $model->chat_uuid]
        ]);

        return [
            'operation' => 'success',
            'message' => 'Marked as read successfully'
        ];
    }

    /**
     * Return chat detail
     * @param $chat_uuid
     * @return Chat|array
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Finds the Ticket model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = Chat::find()
            ->where([
                "chat_uuid" => $id,
                'contact_uuid' => Yii::$app->user->getId()
            ])
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested record does not exist.');
        }
    }
}