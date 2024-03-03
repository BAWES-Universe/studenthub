<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "mail_log".
 *
 * @property string $mail_uuid
 * @property string|null $from
 * @property string|null $to
 * @property string|null $subject
 * @property string|null $app
 * @property string $created_at
 * @property string $updated_at
 */
class MailLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mail_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['from', 'to', 'subject'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['mail_uuid'], 'string', 'max' => 60],
            [['from', 'to', 'subject', 'app'], 'string', 'max' => 255],
            [['mail_uuid'], 'unique'],
        ];
    }

    /**
     *
     * @return type
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'mail_uuid',
                ],
                'value' => function () {
                    if (!$this->mail_uuid) {
                        $this->mail_uuid = 'mail_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    }

                    return $this->mail_uuid;
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
            'mail_uuid' => Yii::t('app', 'Mail Uuid'),
            'from' => Yii::t('app', 'From'),
            'to' => Yii::t('app', 'To'),
            'subject' => Yii::t('app', 'Subject'),
            'app' => Yii::t('app', 'App'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $today = date("Y-m-d");

        $threshold = Yii::$app->params["mailThreshold"];

        $count = self::find()
            ->andWhere(new Expression("DATE('".$today."') = DATE(created_at)"))
            ->count();

        //if already triggered today, then use increased value

        $cacheObject = Yii::$app->cache->get("mailThreshold");

        if($cacheObject && $cacheObject["date"] = $today) {
            $threshold = $cacheObject["threshold"];
        }

        if($count > $threshold) {

            //will send slack notification

            Yii::error("Mail delivery exceed threshold value of ". Yii::$app->params["mailThreshold"]);

            //set new threshold

            $cacheObject = [
                "threshold" => Yii::$app->params["mailThreshold"] + $threshold,
                "date" => $today
            ];

            Yii::$app->cache->set("mailThreshold", $cacheObject);
        }

        return true;
    }

    /**
     * @return array
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public static function getTotalByDays($days = 7)
    {
        $cacheDuration = 60 * 60;// 1 hour then delete from cache

        $cacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `mail_log`',
        ]);

        $customer_data = [];

        $date_start = strtotime('-'.($days - 1).' days');//date('w')

        for ($i = 0; $i < $days; $i++) {

            $date = date('Y-m-d', $date_start + ($i * 86400));

            $customer_data[date('w', strtotime($date))] = array(
                'day' => date('D', strtotime($date)),
                'total' => 0
            );
        }

        $rows = MailLog::getDb()->cache(function ($db) use ($days) {

            return MailLog::find()
                ->select(new Expression('created_at, COUNT(*) as total'))
                ->andWhere(new Expression("DATE(created_at) >= DATE(NOW() - INTERVAL ".($days - 1)." DAY)"))
                ->groupBy(new Expression('DAYNAME(created_at)'))
                ->asArray()
                ->all();

        }, $cacheDuration, $cacheDependency);

        foreach ($rows as $result) {
            $customer_data[date('w', strtotime($result['created_at']))] = array(
                'day' => date('D', strtotime($result['created_at'])),
                'total' => (int)$result['total']
            );
        }

        return array_values($customer_data);
    }
}
