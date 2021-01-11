<?php

namespace company\modules\v1\controllers;

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
        $request_uuid = Yii::$app->request->get("request_uuid");
        $fulltimer_uuid = Yii::$app->request->get("fulltimer_uuid");
        $candidate_id = Yii::$app->request->get("candidate_id");

        $query = Suggestion::find()
            ->joinWith(['fulltimer', 'candidate'])
            ->andWhere([
                'or',
                'candidate.candidate_id is not null',
                'fulltimer.fulltimer_uuid is not null'
                ])

        ->orderBy('suggestion_datetime DESC');

        if($request_uuid) {
            $query->andWhere(['request_uuid' => $request_uuid]);
        }

        if($fulltimer_uuid) {
            $query->andWhere(['fulltimer_uuid' => $fulltimer_uuid]);
        }

        if($candidate_id) {
            $query->andWhere(['candidate_id' => $candidate_id]);
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
     * Finds the Suggestion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Suggestion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Suggestion::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
