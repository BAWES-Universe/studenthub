<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "daily_standup_question".
 *
 * @property string $question_uuid
 * @property string $question
 * @property string $created_at
 * @property string $updated_at
 *
 * @property DailyStandupAnswer[] $dailyStandupAnswers
 */
class DailyStandupQuestion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'daily_standup_question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['question_uuid', 'created_at', 'updated_at'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['question_uuid'], 'string', 'max' => 60],
            [['question'], 'string', 'max' => 255],
            [['question_uuid'], 'unique'],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'question_uuid',
                ],
                'value' => function() {
                    if (!$this->question_uuid)
                        $this->question_uuid = 'question_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->question_uuid;
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

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'question_uuid' => Yii::t('app', 'Question Uuid'),
            'question' => Yii::t('app', 'Question'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public static function standupReport()
    {
        $absents = StaffLeave::find()
            ->andWhere(new Expression("DATE(from_date) <= DATE('".date('Y-m-d')."') AND 
                DATE(to_date) >= DATE('".date('Y-m-d')."')"))
            ->all();

        $attended = StaffWorkSession::find()
            ->andWhere(new Expression("DATE(created_at) = DATE('".date('Y-m-d')."')"))
            ->all();

        $staffIds = array_merge(
            ArrayHelper::getColumn($absents, 'staff_id'),
            ArrayHelper::getColumn($attended, 'staff_id')
        );

        $didnt_attended = Staff::find()
            ->notDeleted()
            ->andWhere(['NOT IN', 'staff_id', $staffIds])
            ->all();

        $ml = new MailLog();
        $ml->to = \Yii::$app->params['adminEmail'];
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Stand-up report";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

            $mailer = Yii::$app->mailer->compose("stand-up-report",
                [
                    'logo' => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                    'absents' => $absents,
                    'attended' => $attended,
                    'didnt_attended' => $didnt_attended
                ])
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject('Stand-up report');

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
    public function getDailyStandupAnswers($modelClass = "\common\models\DailyStandupAnswer")
    {
        return $this->hasMany($modelClass::className(), ['question_uuid' => 'question_uuid']);
    }
}
