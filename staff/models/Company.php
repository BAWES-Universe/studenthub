<?php
namespace staff\models;

use common\models\Candidate;
use common\models\Transfer;
use Yii;
use yii\db\Expression;
use yii\helpers\Url;

/**
 * This is the model class for table "Company".
 * It extends from \common\models\Company but with custom functionality for this application module
 */
class Company extends \common\models\Company {

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {
        $scenarios = parent::scenarios();

        $scenarios['update'] = [
            'company_name',
            'company_email',
            'parent_company_id',
            'company_hourly_rate',
            'company_bonus_commission',
            'company_common_name_en',
            'company_common_name_ar', 'company_description_en', 'company_description_ar',
            'company_website',
            'company_logo',
            'company_followup',
            'company_followup_interval_weeks',
            'company_last_followup_datetime'
        ];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        // Whitelisted fields to return
        $field = parent::fields();

        unset(
            $field['company_created_at'],
            $field['company_updated_at']
        );

        $field['company_website'] = function ($model) {
            $url = $model->company_website;

            if ($model->company_website && !preg_match("~^(?:f|ht)tps?://~i", $model->company_website)) {
                $url = "http://" . $model->company_website;
            }
            return $url;
        };

        return $field;
    }

    public function extraFields()
    {
        return array_merge(
            parent::extraFields(),
            [
                'stats',
            ]
        );
    }
    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSubCompanies($modelClass = "\staff\models\Company")
    {
        return parent::getSubCompanies($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\staff\models\Store")
    {
        return parent::getStores($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\staff\models\Transfer")
    {
        return parent::getTransfers($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getParentTransfers($modelClass = "\staff\models\Transfer")
    {
        return parent::getParentTransfers($modelClass);
    }

    /**
     * Send new password to customer
     * @param $model
     * @param string $type
     * @return bool
     */
    public static function companyCreateUpdateMail($model, $type = 'created')
    {
        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $name = ($model->company_common_name_en) ? $model->company_common_name_en : $model->company_name;
        $subject = Yii::$app->user->identity->staff_name. ' '.$type.' client account '.$name;
        return Yii::$app->mailer->compose("report-company-crud",
            [
                "model" => $model,
                "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                'title' => $subject,
                'staff_name' => Yii::$app->user->identity->staff_name,
                'type' => $type
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo(['khalid@bawes.net'=>'Khalid'])
            ->setSubject($subject)
            ->send();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\staff\models\Note")
    {
        return parent::getNotes($modelClass);
    }

    /**
     * @return array
     */
    public function getStats() {
        return [
            'requests' => $this->getRequests()->count(),
            'stores' => $this->getStores()->count(),
            'contacts' => $this->getCompanyContacts()->count(),
            'brands' => $this->getBrands()->count(),
            'malls' => $this->getMalls()->count(),
            'documents' => $this->getFiles()->count(),
            'transfers' => $this->getParentTransfers()->count(),
            'subCompanies' => $this->getSubCompanies()->count(),
        ];
    }
}
