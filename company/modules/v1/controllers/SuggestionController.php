<?php

namespace company\modules\v1\controllers;

use common\models\Story;
use staff\models\Note;
use Yii;
use yii\data\ActiveDataProvider;
use staff\models\Suggestion;
use yii\web\NotFoundHttpException;

/**
 * Suggestion controller - Manage Suggestion as Admin
 */
class SuggestionController extends BaseController
{
    /**
     * Return a List of Suggestion s available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $request_uuid = Yii::$app->request->get("request_uuid");
        $fulltimer_uuid = Yii::$app->request->get("fulltimer_uuid");
        $candidate_id = Yii::$app->request->get("candidate_id");

        $query = Suggestion::find()
            ->joinWith(['request'])
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->andWhere([
                'or',
                'candidate_id is not null',
                'fulltimer_uuid is not null'
            ])
            ->orderBy('suggestion_datetime DESC');

        if($request_uuid) {
            $query->andWhere(['suggestion.request_uuid' => $request_uuid]);
        }

        if($fulltimer_uuid) {
            $query->joinWith(['fulltimer'])
                ->andWhere(['fulltimer_uuid' => $fulltimer_uuid]);
        }

        if($candidate_id) {
            $query->joinWith(['candidate'])
                ->andWhere(['candidate_id' => $candidate_id]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    /**
     * load Suggestion details
     * @param $id
     * @return Suggestion
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * accept a Suggestion
     * @return array
     */
    public function actionAccept($id)
    {
        $reason = Yii::$app->request->getBodyParam("reason");

        $model = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();

        $note = new Note;
        $note->request_uuid = $model->request_uuid;
        $note->company_id = $model->request->company_id;
        $note->candidate_id = $model->candidate_id;
        $note->fulltimer_uuid = $model->fulltimer_uuid;
        $note->suggestion_uuid = $model->suggestion_uuid;
        $note->note_type = Note::TYPE_ACCEPTED;
        $note->note_text = $reason;

        if(!$note->save())
        {
            $transaction->rollBack();

            if(isset($note->errors)){
                return [
                    "operation" => "error",
                    "message" => $note->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Note, please contact us for assistance."
                ];
            }
        }

        $model->suggestion_status = Suggestion::TYPE_ACCEPTED;

        if (!$model->save())
        {
            $transaction->rollBack();

            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Suggestion, please contact us for assistance."
                ];
            }
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Suggestion marked as accepted successfully"
        ];
    }

    /**
     * reject a Suggestion
     * @return array
     */
    public function actionReject($id)
    {
        $reason = Yii::$app->request->getBodyParam("reason");

        $model = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();

        $note = new Note;
        $note->request_uuid = $model->request_uuid;
        $note->company_id = $model->request->company_id;
        $note->candidate_id = $model->candidate_id;
        $note->fulltimer_uuid = $model->fulltimer_uuid;
        $note->suggestion_uuid = $model->suggestion_uuid;
        $note->note_type = Note::TYPE_REJECTED;
        $note->note_text = $reason;

        if(!$note->save())
        {
            $transaction->rollBack();

            if(isset($note->errors)){
                return [
                    "operation" => "error",
                    "message" => $note->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Note, please contact us for assistance."
                ];
            }
        }

        $model->suggestion_status = Suggestion::TYPE_REJECTED;

        if (!$model->save())
        {
            $transaction->rollBack();

            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Suggestion, please contact us for assistance."
                ];
            }
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Suggestion marked as rejected successfully"
        ];
    }

    /**
     * Finds the Suggestion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Suggestion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $model = Suggestion::find()
            ->joinWith(['request'])
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->andWhere(['suggestion_uuid' => $id])
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
