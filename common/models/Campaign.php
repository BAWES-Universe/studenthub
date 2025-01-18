<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "campaign".
 *
 * @property string $utm_uuid
 * @property string $utm_source e.g. newsletter, twitter, google, etc.
 * @property string $utm_medium e.g. email, social, cpc, etc.
 * @property string $utm_campaign e.g. promotion, sale, etc.
 * @property string $utm_content Any call-to-action or headline, e.g. buy-now.
 * @property string $utm_term Keywords for your paid search campaigns
 * @property int $no_of_signups
 * @property int $no_of_clicks
 * @property string $investment
 * @property string $total_revenue Total revenue made by us
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate[] $candidates
 * @property Contact[] $contacts
 */
class Campaign extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'campaign';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['utm_uuid'], 'required'],
            [['no_of_signups', 'no_of_clicks'], 'integer'],
            [['investment', 'total_revenue'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['utm_uuid'], 'string', 'max' => 60],
            [['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'], 'string', 'max' => 100],
            [['utm_uuid'], 'unique'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'utm_uuid',
                ],
                'value' => function () {
                    if (!$this->utm_uuid)
                        $this->utm_uuid = 'utm_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                    return $this->utm_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                //'createdAtAttribute' => 'candidate_created_at',
                //'updatedAtAttribute' => 'candidate_updated_at',
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
            'utm_uuid' => Yii::t('app', 'Utm Uuid'),
            'utm_source' => Yii::t('app', 'Utm Source'),
            'utm_medium' => Yii::t('app', 'Utm Medium'),
            'utm_campaign' => Yii::t('app', 'Utm Campaign'),
            'utm_content' => Yii::t('app', 'Utm Content'),
            'utm_term' => Yii::t('app', 'Utm Term'),
            'no_of_signups' => Yii::t('app', 'No Of Signups'),
            'no_of_clicks' => Yii::t('app', 'No Of Clicks'),
            'investment' => Yii::t('app', 'Investment'),
            'total_revenue' => Yii::t('app', 'Total Revenue'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['no_of_signups'] = function($model) {
            return (int) $model->no_of_signups;
        };

        $fields['no_of_clicks'] = function($model) {
            return (int) $model->no_of_clicks;
        };

        $fields['total_revenue'] = function($model) {
            return (int) $model->total_revenue;
        };

        return $fields;
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        $fields = parent::extraFields();

        return array_merge($fields, [
            'candidateCampaignChartData',
            'contactCampaignChartData',
        ]);
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $props = [
                "source" => $this->utm_source,
                "medium" => $this->utm_medium,
                "campaign" => $this->utm_campaign,
                "term" => $this->utm_term,
                "content" => $this->utm_content
            ];

            Yii::$app->eventManager->track("Campaign Created", $props);
        }
    }

    /**
     * return campaign usage by months
     * @return array
     */
    public function getCandidateCampaignChartData() {

        $campaign_chart_data = [];

        /*$date_start = $this->valid_from;

        if(strtotime($this->valid_until) < time()) {
            $date_end = $this->valid_until;
        } else {
            $date_end = date('Y') . '-' . date('m') . '-1';
        }

        $months = $this->getMonthsBetween($date_start, $date_end);

        for ($i = 0; $i < $months; $i++) {

            $month = date('m', strtotime('-'.($months - $i).' month'));

            $voucher_chart_data[$month] = array(
                'month'   => date('F', strtotime('-'.($months - $i).' month')),
                'total' => 0
            );
        }*/

        $rows = $this->getCandidates()
            ->select ('candidate_created_at, COUNT(*) as total')
            //->andWhere('DATE(`order_created_at`) >= DATE("'.$date_start.'") AND DATE(`order_created_at`) < DATE("'.$date_end.'")')
            ->groupBy (new Expression('MONTH(candidate_created_at), YEAR(candidate_created_at)'))
            ->orderBy('candidate_created_at')
            ->asArray()
            ->all();

        foreach ($rows as $result) {
            $campaign_chart_data[date ('m', strtotime ($result['candidate_created_at']))] = array(
                'month' => Yii::t('app', date ('M', strtotime ($result['candidate_created_at']))),
                'total' => (int) $result['total']
            );
        }

        return array_values($campaign_chart_data);
    }

    /**
     * @return array
     */
    public function getContactCampaignChartData() {

        $campaign_chart_data = [];

        /*$date_start = $this->valid_from;

        if(strtotime($this->valid_until) < time()) {
            $date_end = $this->valid_until;
        } else {
            $date_end = date('Y') . '-' . date('m') . '-1';
        }

        $months = $this->getMonthsBetween($date_start, $date_end);

        for ($i = 0; $i < $months; $i++) {

            $month = date('m', strtotime('-'.($months - $i).' month'));

            $voucher_chart_data[$month] = array(
                'month'   => date('F', strtotime('-'.($months - $i).' month')),
                'total' => 0
            );
        }*/

        $rows = $this->getContacts()
            ->select ('contact_created_at, COUNT(*) as total')
            //->andWhere('DATE(`order_created_at`) >= DATE("'.$date_start.'") AND DATE(`order_created_at`) < DATE("'.$date_end.'")')
            ->groupBy (new Expression('MONTH(contact_created_at), YEAR(contact_created_at)'))
            ->orderBy('contact_created_at')
            ->asArray()
            ->all();

        foreach ($rows as $result) {
            $campaign_chart_data[date ('m', strtotime ($result['contact_created_at']))] = array(
                'month' => Yii::t('app', date ('M', strtotime ($result['contact_created_at']))),
                'total' => (int) $result['total']
            );
        }

        return array_values($campaign_chart_data);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['utm_uuid' => 'utm_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContacts($modelClass = "\common\models\Contact")
    {
        return $this->hasMany($modelClass::className(), ['utm_uuid' => 'utm_uuid']);
    }
}
