<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "firing_hitmap".
 *
 * @property string $fh_uuid
 * @property int $company_id
 * @property int $firing_month
 * @property int $firing_year
 * @property int $total
 * @property boolean $is_alerted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Company $company
 */
class FiringHitmap extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'firing_hitmap';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'firing_month', 'firing_year'], 'required'],//'fh_uuid', 
            [['company_id', 'firing_month', 'firing_year', 'total'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['fh_uuid'], 'string', 'max' => 60],
            [['is_alerted'], 'boolean'],
            [['fh_uuid'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'fh_uuid',
                ],
                'value' => function() {
                    if (!$this->fh_uuid)
                        $this->fh_uuid = 'fh_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->fh_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'fh_uuid' => Yii::t('app', 'Fh Uuid'),
            'company_id' => Yii::t('app', 'Company ID'),
            'firing_month' => Yii::t('app', 'Firing Month'),
            'firing_year' => Yii::t('app', 'Firing Year'),
            'total' => Yii::t('app', 'Total'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return void
     */
    public static function updateHitMap($month = "MONTH(NOW())", $year = "YEAR(NOW())") {

        $firing_month = $month == "MONTH(NOW())" ? date("m"): $month;
        $firing_year = $year == "YEAR(NOW())" ? date("Y"): $year;

        $query = Company::find()
            ->filterParent()
            ->notDeleted();

        foreach ($query->batch() as $companies) {

            foreach ($companies as $company) {

                $total = CandidateWorkHistory::find()
                    ->andWhere(['parent_company_id' => $company->company_id])
                    ->andWhere(new Expression("MONTH(end_date) = ".$month. " AND YEAR(end_date) = ". $year))
                    ->count();

                $model = FiringHitmap::find()
                    ->andWhere([
                        'company_id' => $company->company_id,
                        "firing_month" => $firing_month,
                        "firing_year" => $firing_year
                    ])
                    ->one();

                if(!$model) {
                    $model = new FiringHitmap();
                    $model->company_id = $company->company_id;
                    $model->firing_month = $firing_month;
                    $model->firing_year = $firing_year;
                }

                $model->total = $total;

                if(!$model->save()) {
                    print_r($model->errors);
                    die();
                }

                //check if spike

                if (!$model->is_alerted) {

                    $hitmap = FiringHitmap::find()
                        ->orderBy("firing_year DESC, firing_month DESC")
                        ->andWhere(['company_id' => $company->company_id])
                        ->limit(12)
                        ->all();

                    $sumForAvg = 0;

                    foreach ($hitmap as $value) {
                        $sumForAvg += $value['total'];
                    }

                    $average = $sumForAvg / 12;

                    // if total > average of last 12 months
                    // if total > 2 * average of last 12 months

                    if ($total > 2 * $average) {
                        //danger
                        self::firingSpike($company);
                        $model->is_alerted = true;
                        $model->save();
                    } else if ($total > $average) {
                        // warning
                        self::firingSpike($company, "warning");
                        $model->is_alerted = true;
                        $model->save();
                    }
                }
            }
        }
    }

    /**
     * @param $company
     * @param $type
     * @return bool|void
     */
    public static function firingSpike($company, $type = "danger") {

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $name = !empty($company->company_common_name_en) ? $company->company_common_name_en : $company->company_name;

        $subject = "Firing spike for " . $name;

        $ml = new MailLog();
        $ml->to = \Yii::$app->params['operationsEmail'];
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = $subject;
        $ml->save();

        $mailer = Yii::$app->mailer->compose("staff/company-firing-spike-html",
            [
                "model" => $company,
                "name" => $name,
                "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                'title' => $subject,
                'type' => $type
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo([Yii::$app->params['operationsEmail'] => 'operations'])
            ->setCc(['khalid@bawes.net'=>'Khalid'])
            ->setSubject($subject);

        try {
            return  $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        return array_merge(["company"], parent::extraFields());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }
}
