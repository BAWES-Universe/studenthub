<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\console\Exception;
use yii\db\Expression;
use yii\helpers\Console;

/**
 * This is the model class for table "email_campaign".
 *
 * @property string $campaign_uuid
 * @property string $subject
 * @property string $message
 * @property int $progress
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property EmailCampaignFilter[] $emailCampaignFilters
 */
class EmailCampaign extends \yii\db\ActiveRecord
{
    const STATUS_DRAFT = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_READY = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email_campaign';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['campaign_uuid'], 'required'],
            [['message'], 'string'],
            [['progress', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['campaign_uuid'], 'string', 'max' => 60],
            [['subject'], 'string', 'max' => 255],
            [['campaign_uuid'], 'unique'],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'campaign_uuid',
                ],
                'value' => function() {
                    if (!$this->campaign_uuid)
                        $this->campaign_uuid = 'campaign_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->campaign_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function getStatusName() {
        switch ($this->status) {
            case 0:
                return 'Draft';
            case 1:
                return 'In Process';
            case 2:
                return 'Completed';
            case 3:
                return 'In Queue';

        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'campaign_uuid' => Yii::t('app', 'Campaign Uuid'),
            'subject' => Yii::t('app', 'Subject'),
            'message' => Yii::t('app', 'Message'),
            'progress' => Yii::t('app', 'Progress'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function extraFields()
    {
        return array_merge(["emailCampaignFilters"], parent::extraFields());
    }

    /**
     * process campaign
     * @return void
     */
    public function process() {

        $this->status = self::STATUS_IN_PROGRESS;

        if(!$this->save()) {
            throw new Exception(print_r($this->errors, true));
        }

        $query = Candidate::find();

        $filters = $this->getEmailCampaignFilters()->all();

        foreach ($filters as $filter) {
            
            if ($filter['param'] == "filterAssigned")
            {
                $query->filterAssigned();
            } 
            else if ($filter['param'] == "filterNotAssigned")
            {
                $query->filterNotAssigned();
            }
            else if ($filter['param'] == "filterStore")
            {
                $query->filterStore($filter['value']);
            }
            else if ($filter['param'] == "filterCountry")
            {
                $query->filterCountry($filter['value']);
            }
            else if ($filter['param'] == "filterUniversity")
            {
                $query->filterUniversity($filter['value']);
            }
            else if ($filter['param'] == "idExpired")
            {
                $query->idExpired();
            }
            else if ($filter['param'] == "byApprovalStatus")
            {
                $query->byApprovalStatus($filter['value']);
            }
            else if ($filter['param'] == "verifiedProfile")
            {
                $query->verifiedProfile();
            }
            else if ($filter['param'] == "withoutBankInfo")
            {
                $query->withoutBankInfo();
            }
            else if ($filter['param'] == "candidateMomKuwaitiFieldIsNull")
            {
                $query->candidateMomKuwaitiFieldIsNull();
            }
            else if($filter['param'] == "ageRange")
            {
                $values = explode(":", $filter['value']);

                $query->andWhere(new Expression("YEAR(CURDATE()) - YEAR(candidate_birth_date) BETWEEN ".$values[0].
                    " AND ".$values[1]));
            }
        }

        $total = $query->count();
 
        Console::startProgress(0, $total);

        $processed = 0;

        foreach ($query->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {
                try {
                    $this->sendEmail($candidate);
                } catch (\Exception $e) {
                    Yii::error($e, 'campaign');
                    continue;
                }
            }

            $processed += sizeof($candidates);

            $this->progress = ceil($processed * 100 / $total);

            if(!$this->save()) {
                throw new Exception(print_r($this->errors, true));
            }

            Console::updateProgress($processed, $total);

            sleep(10);
        }

        $this->status = self::STATUS_COMPLETED;

        if(!$this->save()) {
            throw new Exception(print_r($this->errors, true));
        }
    }

    /**
     * send campaign message
     * @param $candidate
     * @return void
     */
    public function sendEmail($candidate) {

        if(!$candidate->candidate_email_verification) {
            return false;
        }

        $arrSearch = [
            "[candidate_name]",
            "[candidate_name_ar]",
            "[candidate_email]"
        ];

        $arrReplace = [
            $candidate->candidate_name,
            $candidate->candidate_name_ar,
            $candidate->candidate_email
        ];

        $message = str_replace($arrSearch, $arrReplace, $this->message);

        $ml = new MailLog();
        $ml->to = $candidate->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = $this->subject;
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = \Yii::$app->mailer->compose()
            ->setHtmlBody($message)
            ->setSubject($this->subject)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($candidate->candidate_email);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmailCampaignFilters($modelClass = "\common\models\EmailCampaignFilter")
    {
        return $this->hasMany($modelClass::className(), ['campaign_uuid' => 'campaign_uuid']);
    }
}
