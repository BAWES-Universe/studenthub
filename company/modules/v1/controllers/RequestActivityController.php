<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use staff\models\Note;

/**
 * RequestActivity controller
 */
class RequestActivityController extends BaseController
{
    /**
     * request activity list
     * @param $id
     * @return RequestActivity[]
     */
    public function actionRequestActivities($id)
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $query = Note::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->andWhere(['request_uuid' => $id])
            //https://www.pivotaltracker.com/story/show/176153241 looking for all type of activities
//            ->andWhere(['NOT IN', 'note_type', [NOTE::TYPE_SUGGESTED, NOTE::TYPE_ACCEPTED, NOTE::TYPE_REJECTED]])
            ->orderBy('note_created_datetime desc');

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }


    /**
     * return request activity detail
     * @param $request_uuid
     *
     * @return RequestActivity
     * @throws NotFoundHttpException
     */
    public function actionDetail($id)
    {
        return $this->findModel($id);
    }

    /**
     * Finds the Request model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RequestActivity the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $model = Note::find()
            ->andWhere(['note_uuid' => $id])
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }
    }
}
