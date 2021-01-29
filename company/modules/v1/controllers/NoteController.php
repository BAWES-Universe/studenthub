<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use company\models\Note;
use company\models\Request;
use yii\web\NotFoundHttpException;


/**
 * Note controller - Manage brand as Admin
 */
class NoteController extends BaseController
{
    /**
     * Return a List of Brand Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $fulltimer_uuid = Yii::$app->request->get('fulltimer_uuid');
        $candidate_id = Yii::$app->request->get('candidate_id');
        $request_uuid = Yii::$app->request->get('request_uuid');
        $company_id = Yii::$app->request->get('company_id');
        $contact_uuid = Yii::$app->request->get('contact_uuid');
        $staff_id = Yii::$app->request->get('staff_id');

        $page = Yii::$app->request->get('page');

        $query = Note::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->orderBy('note_created_datetime DESC');

        if($staff_id) {
            $query->filterCreatedBy($staff_id);
        }

        if($company_id && in_array ($company_id, $companyIds)) {
            $query->filterCompany($company_id);
        }

        if($staff_id) {
            $query->filterStaff($staff_id);
        }

        if($request_uuid) {
            $query->filterRequest($request_uuid);
        }

        if($fulltimer_uuid) {
            $query->filterFulltimer($fulltimer_uuid);
        }

        if($candidate_id) {
            $query->filterCandidate($candidate_id);
        }

        if($contact_uuid) {
            $query->filterContact($contact_uuid);
        }

        if(!$page)
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false
            ]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Note
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a Note account
     * @return array
     */
    public function actionCreate()
    {
        $company = Yii::$app->companyManager->getCompany();

        $model = new Note();

        $model->note_text = htmlentities(Yii::$app->request->getBodyParam("note"));
        $model->note_type = Yii::$app->request->getBodyParam("type");
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $model->fulltimer_uuid = Yii::$app->request->getBodyParam("fulltimer_uuid");
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");

        $model->company_id = $company->company_id;

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
                    "message" => "We've faced a problem creating the Note, please contact us for assistance."
                ];
            }
        }

        $request_updated_at = '';

        if($model->request_uuid) {
            $request_updated_at = Request::findOne($model->request_uuid)->request_updated_datetime;
        }

        return [
            "operation" => "success",
            "message" => "Note created successfully",
            "request_updated_at" => $request_updated_at
        ];
    }

    /**
     * Create a Note account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        if(!$model){
            return [
                "operation" => "error",
                "message" => "Note not found."
            ];
        }

        $model->note_text = htmlentities(Yii::$app->request->getBodyParam("note"));
        $model->note_type = Yii::$app->request->getBodyParam("type");
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $model->fulltimer_uuid = Yii::$app->request->getBodyParam("fulltimer_uuid");
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");

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
                    "message" => "We've faced a problem updating the Note, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Note successfully updated"
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

        $model->delete();

        return [
            "operation" => "success",
            "message" => "Note deleted successfully"
        ];
    }

    /**
     * Finds the Brand model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Note the loaded model
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
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
