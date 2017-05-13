<?php
namespace staff\models;

use Yii;

/**
 * This is the model class for table "Candidate".
 * It extends from \common\models\Candidate but with custom functionality for this application module
 */
class Candidate extends \common\models\Candidate {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['candidate_auth_key'],
        $fields['candidate_password_hash'],
        $fields['candidate_password_reset_token'],
        $fields['candidate_created_at'],
        $fields['candidate_updated_at']);

        $fields['bank_name'] = function($model) {
            return $this->bank->bank_name;
        };

        $fields['candidate_status'] = function($model) {
            return $model->getStatus();
        };

        $fields['university'] = function($model) {
            return $model->university;
        };

        $fields['country'] = function($model) {
            return $model->country;
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            $this->approved = false; //mark as dirty to send to admin for review

            return true;
        }

        return false;
    }

}
