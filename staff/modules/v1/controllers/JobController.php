<?php

namespace staff\modules\v1\controllers;

use candidate\models\CandidateSkill;
use common\models\JobInterest;
use common\models\Candidate;
use common\models\Area;
use staff\models\Country;
use Yii;
use common\models\Job;
use common\models\JobSkills;
use staff\models\Request;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;


class JobController extends BaseController
{
    /**
     * @param $id
     * @return JobInterest|null
     * @throws NotFoundHttpException
     */
    public function actionViewInterest($id) {
        return $this->findJobInterest($id);
    }

    /**
     * @return array
     */
    public function actionInterestsFilter() {
        $job_uuid = Yii::$app->request->get("job_uuid");

        $candidateIDQuery = JobInterest::find()
            ->select('candidate_id')
            ->orderBy("created_at DESC")
            ->andWhere(['job_uuid' => $job_uuid]);

        $candidateQuery = Candidate::find()
            ->select(['country_id', 'candidate_area_uuid', 'candidate_id'])
            ->andWhere(['IN', 'candidate_id', $candidateIDQuery])
            ->all();

        $countries = Country::find()
            ->andWhere([
                'IN',
                'country_id',
                ArrayHelper::getColumn($candidateQuery, 'country_id')
            ])
            //->select(['area_name_en', 'area_name_ar', 'country_id'])
            //->distinct('area_name_en')
            //->asArray()
            ->all();

        $areas = Area::find()
            ->andWhere([
                'IN',
                'area_uuid',
                ArrayHelper::getColumn($candidateQuery, 'candidate_area_uuid')
            ])
            //->select(['area_name_en', 'area_name_ar', 'area_uuid'])
            //->distinct('area_name_en')
            //->asArray()
            ->all();

        $skills = CandidateSkill::find()
            ->andWhere([
                'IN',
                'candidate_id',
                ArrayHelper::getColumn($candidateQuery, 'candidate_id')
            ])
            ->select(['skill'])
            ->distinct('skill')
            ->asArray()
            ->all();

        return [
            "nationalities" => $countries,
            "skills" => $skills,
            "areas" => $areas
        ];
    }

    /**
     * list interest
     * @return ActiveDataProvider
     */
    public function actionListInterest()
    {
        $job_uuid = Yii::$app->request->get("job_uuid");
        $status = Yii::$app->request->get("status");
        $candidate_id = Yii::$app->request->get("candidate_id");
        $country_id = Yii::$app->request->get("country_id");
        $nationality_country_id = Yii::$app->request->get("nationality_country_id");
        $age_from = Yii::$app->request->get("age_from");
        $age_to = Yii::$app->request->get("age_to");
        $gender = Yii::$app->request->get("gender");
        $skills = Yii::$app->request->get("skills");
        $areas = Yii::$app->request->get("areas");

        $query = JobInterest::find()
            ->joinWith(['candidate', 'candidate.area', 'candidate.candidateSkills'])
            ->orderBy("created_at DESC");

        if ($job_uuid) {
            $query->andWhere(['job_uuid' => $job_uuid]);
        }

        if ($candidate_id) {
            $query->andWhere(['candidate_id' => $candidate_id]);
        }

        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        if ($country_id) {
            $query->andWhere(['area.country_id' => $country_id]);
        }

        if ($nationality_country_id) {
            $query->andWhere(['candidate.country_id' => $nationality_country_id]);
        }

        if ($age_from && $age_to) {
            $query->andWhere(new Expression('TIMESTAMPDIFF(YEAR, candidate.candidate_birth_date, 
                CURDATE()) BETWEEN '.$age_from.' AND '.$age_to));
        }

        if ($gender) {
            $query->andWhere(['candidate.candidate_gender' => $gender]);
        }

        if ($areas) {
            $query->andWhere(['IN', 'candidate.candidate_area_uuid', explode(",", $areas)]);
        }

        if ($skills) {
            $query->andWhere(['IN', 'candidate_skill.skill', explode(",", $skills)]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $status = Yii::$app->request->get("status");
        $story_uuid = Yii::$app->request->get("story_uuid");
        $request_uuid = Yii::$app->request->get("request_uuid");
        $area_uuid = Yii::$app->request->get("area_uuid");
        $position = Yii::$app->request->get("position");
        $description = Yii::$app->request->get("description");
        $hours_per_day = Yii::$app->request->get("hours_per_day");
        $days_per_week = Yii::$app->request->get("days_per_week");
        $compensation_type = Yii::$app->request->get("compensation_type");

        $compensation_amount = Yii::$app->request->get("compensation_amount");
        $compensation_description = Yii::$app->request->get("compensation_description");
        $min_age = Yii::$app->request->get("min_age");
        $max_age = Yii::$app->request->get("max_age");
        $gender = Yii::$app->request->get("gender");
        $from = Yii::$app->request->get("from");
        $to = Yii::$app->request->get("to");

        $query = Job::find()
            ->orderBy("created_at DESC");

        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        if ($story_uuid) {
            $query->andWhere(['story_uuid' => $story_uuid]);
        }

        if ($request_uuid) {
            $query->andWhere(['request_uuid' => $request_uuid]);
        }

        if ($area_uuid) {
            $query->andWhere(['area_uuid' => $area_uuid]);
        }

        if ($position) {
            $query->andWhere(['position' => $position]);
        }

        if ($description) {
            $query->andWhere(['description' => $description]);
        }

        if ($hours_per_day) {
            $query->andWhere(['hours_per_day' => $hours_per_day]);
        }

        if ($days_per_week ) {
            $query->andWhere(['days_per_week' => $days_per_week]);
        }

        if ($compensation_type) {
            $query->andWhere(['compensation_type' => $compensation_type]);
        }

        if ($compensation_amount) {
            $query->andWhere(['compensation_amount' => $compensation_amount]);
        }

        if ($compensation_description) {
            $query->andWhere(['compensation_description' => $compensation_description]);
        }

        if ($min_age) {
            $query->andWhere(new Expression('min_age >= ' .$min_age));
        }

        if ($max_age) {
            $query->andWhere(new Expression('max_age <= ' .$max_age));
        }

        if ($gender) {
            $query->andWhere(['gender' => $gender]);
        }

        if ($from) {
            $query->andWhere( new Expression('DATE(available_from) >= 
                DATE("'.date("Y-m-d", strtotime($from)).'")'));
        }

        if ($to) {
            $query->andWhere( new Expression('DATE(available_to) <= 
                DATE("'.date("Y-m-d", strtotime($to)).'")'));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Job
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a Job 
     * @return array
     */
    public function actionCreate()
    {
        $model = new Job();

        $model->story_uuid = Yii::$app->request->getBodyParam("story_uuid");
        //$model->request_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->area_uuid = Yii::$app->request->getBodyParam("area_uuid");
        $model->position = Yii::$app->request->getBodyParam("position");
        $model->position_ar = Yii::$app->request->getBodyParam("position_ar");
        $model->description = Yii::$app->request->getBodyParam("description");
        $model->description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->hours_per_day = Yii::$app->request->getBodyParam("hours_per_day");
        $model->days_per_week = Yii::$app->request->getBodyParam("days_per_week");
        $model->compensation_type = Yii::$app->request->getBodyParam("compensation_type");
        $model->compensation_amount = Yii::$app->request->getBodyParam("compensation_amount");
        $model->compensation_description = Yii::$app->request->getBodyParam("compensation_description");
        $model->compensation_description_ar = Yii::$app->request->getBodyParam("compensation_description_ar");
        $model->min_age = Yii::$app->request->getBodyParam("min_age");
        $model->max_age = Yii::$app->request->getBodyParam("max_age");
        $model->gender = Yii::$app->request->getBodyParam("gender");
        $model->available_from = Yii::$app->request->getBodyParam("available_from");
        $model->available_to = Yii::$app->request->getBodyParam("available_to");
        $model->status = Yii::$app->request->getBodyParam('status');

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
                    "message" => "We've faced a problem creating the Job, please contact us for assistance."
                ];
            }
        }

        $jobSkills = Yii::$app->request->getBodyParam('jobSkills');

        if(!$jobSkills) {
            $jobSkills = [];
        }

        foreach ($jobSkills as $jobSkill) {

            if(empty($jobSkill['skill'])) {
                continue;
            }

            $job_skill = new JobSkills();
            $job_skill->job_uuid = $model->job_uuid;
            $job_skill->skill = $jobSkill['skill'];
            $job_skill->skill_ar = $jobSkill['skill_ar'];

            if(!$job_skill->save()) {
                return [
                    "operation" => "error",
                    "message" => $job_skill->errors,
                    "job_uuid" => $model->job_uuid
                ];
            }
        }

        $request_updated_at = '';

        if($model->request_uuid) {
            $request_updated_at = Request::findOne($model->request_uuid)->request_updated_datetime;
        }

        return [
            "operation" => "success",
            "message" => "Job created successfully",
            "request_updated_at" => $request_updated_at
        ];
    }

    /**
     * Create a Job account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        $model->story_uuid = Yii::$app->request->getBodyParam("story_uuid");
        //$model->request_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->area_uuid = Yii::$app->request->getBodyParam("area_uuid");
        $model->position = Yii::$app->request->getBodyParam("position");
        $model->position_ar = Yii::$app->request->getBodyParam("position_ar");
        $model->description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->description = Yii::$app->request->getBodyParam("description");
        $model->hours_per_day = Yii::$app->request->getBodyParam("hours_per_day");
        $model->days_per_week = Yii::$app->request->getBodyParam("days_per_week");
        $model->compensation_type = Yii::$app->request->getBodyParam("compensation_type");
        $model->compensation_amount = Yii::$app->request->getBodyParam("compensation_amount");
        $model->compensation_description = Yii::$app->request->getBodyParam("compensation_description");
        $model->compensation_description_ar = Yii::$app->request->getBodyParam("compensation_description_ar");
        $model->min_age = Yii::$app->request->getBodyParam("min_age");
        $model->max_age = Yii::$app->request->getBodyParam("max_age");
        $model->gender = Yii::$app->request->getBodyParam("gender");
        $model->available_from = Yii::$app->request->getBodyParam("available_from");
        $model->available_to = Yii::$app->request->getBodyParam("available_to");
        $model->status = Yii::$app->request->getBodyParam('status');

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
                    "message" => "We've faced a problem updating the Job, please contact us for assistance."
                ];
            }
        }

        $alreadyAdded = ArrayHelper::getColumn($model->getJobSkills()->all(), "skill");

        $jobSkills = Yii::$app->request->getBodyParam('jobSkills');

        if(!$jobSkills) {
            $jobSkills = [];
        }

        foreach ($jobSkills as $jobSkill) {

            if(empty($jobSkill['skill'])) {
                continue;
            }

            if(in_array($jobSkill['skill'], $alreadyAdded)) {
                continue;
            }

            $job_skill = new JobSkills();
            $job_skill->job_uuid = $model->job_uuid;
            $job_skill->skill = $jobSkill['skill'];
            $job_skill->skill_ar = $jobSkill['skill_ar'];

            if(!$job_skill->save()) {
                return [
                    "operation" => "error",
                    "message" => $job_skill->errors
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Job successfully updated"
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
 
        $model->delete();

        return [
            "operation" => "success",
            "message" => "Job deleted successfully"
        ];
    }
    
    /**
     * Finds the Job model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Job the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Job::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @param $id
     * @return JobInterest|null
     * @throws NotFoundHttpException
     */
    protected function findJobInterest($id)
    {
        if (($model = JobInterest::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}