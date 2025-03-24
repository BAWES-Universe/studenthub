<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\Console;
use Segment\Segment;

/**
 * This is the model class for table "fulltimer".
 *
 * @property string $fulltimer_uuid
 * @property int $nationality_id
 * @property int $country_id
 *
 * @property int $university_id
 * @property boolean $fulltimer_employed
 * @property int $fulltimer_gender
 * @property boolean $fulltimer_driving_license
 * @property string $fulltimer_birth_date
 *
 * @property string $fulltimer_area_uuid
 * @property string $fulltimer_latitude
 * @property string $fulltimer_longitude
 * @property string $fulltimer_name
 * @property string $fulltimer_phone
 * @property string $fulltimer_email
 * @property string $fulltimer_current_salary
 * @property string $fulltimer_expected_salary
 * @property string $fulltimer_pdf_cv
 * @property string $fulltimer_created_datetime
 * @property string $fulltimer_updated_datetime
 * @property string $currency_code
 * @property Country $country
 * @property Area $fulltimerAreaUu
 * @property Country $nationality
 * @property FulltimerTags[] $fulltimerTags
 */
class Fulltimer extends \yii\db\ActiveRecord
{
    public $tags;

    //Gender values for `gender`
    const YES = 1;
    const NO = 2;

    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulltimer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fulltimer_name', 'fulltimer_email', 'nationality_id', 'country_id', 'fulltimer_area_uuid', 'fulltimer_latitude', 'fulltimer_longitude', 'fulltimer_name', 'fulltimer_phone', 'fulltimer_email', 'currency_code'], 'required'],
            [['fulltimer_current_salary', 'fulltimer_expected_salary'], 'number', 'min' => 0],
            [['nationality_id', 'country_id', 'fulltimer_gender'], 'integer'],
            [['fulltimer_latitude', 'fulltimer_longitude'], 'number'],
            [['fulltimer_created_datetime', 'fulltimer_updated_datetime','fulltimer_current_salary', 'fulltimer_expected_salary'], 'safe'],
            [['fulltimer_uuid', 'fulltimer_area_uuid'], 'string', 'max' => 60],
            [['fulltimer_employed'], 'boolean'],
            [['currency_code'], "string", "max" => 3],
            [['fulltimer_birth_date'], 'date', 'format' => 'yyyy-M-d'],
            ['fulltimer_gender', 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER]],
            ['fulltimer_driving_license', 'in', 'range' => [self::YES, self::NO]],
            [['fulltimer_name', 'fulltimer_phone', 'fulltimer_email', 'fulltimer_pdf_cv','fulltimer_current_salary','fulltimer_expected_salary'], 'string', 'max' => 255],
            [
                ['fulltimer_pdf_cv'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => "Please upload pdf resume",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'extensions' => 'pdf,doc,docx',
                'when' => function($model, $attribute) {
                    return (trim($model->fulltimer_pdf_cv) && $model->{$attribute} !== $model->getOldAttribute($attribute));
                }
            ],
            [['fulltimer_uuid', 'fulltimer_email','fulltimer_name','fulltimer_phone'], 'unique'],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'country_id']],
            [['fulltimer_area_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Area::class, 'targetAttribute' => ['fulltimer_area_uuid' => 'area_uuid']],
            [['nationality_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['nationality_id' => 'country_id']],
            [['university_id'], 'exist', 'skipOnError' => true, 'targetClass' => University::class, 'targetAttribute' => ['university_id' => 'university_id']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'fulltimer_uuid',
                ],
                'value' => function() {
                    if (!$this->fulltimer_uuid)
                        $this->fulltimer_uuid = 'fulltimer_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->fulltimer_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'fulltimer_created_datetime',
                'updatedAtAttribute' => 'fulltimer_updated_datetime',
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
            'fulltimer_uuid' => Yii::t('app', 'Fulltimer Uuid'),
            'nationality_id' => Yii::t('app', 'Nationality ID'),
            'country_id' => Yii::t('app', 'Country ID'),
            'fulltimer_area_uuid' => Yii::t('app', 'Fulltimer Area Uuid'),
            'fulltimer_latitude' => Yii::t('app', 'Fulltimer Latitude'),
            'fulltimer_longitude' => Yii::t('app', 'Fulltimer Longitude'),
            'fulltimer_name' => Yii::t('app', 'Fulltimer Name'),
            'fulltimer_phone' => Yii::t('app', 'Fulltimer Phone'),
            'fulltimer_email' => Yii::t('app', 'Fulltimer Email'),
            'fulltimer_current_salary' => Yii::t('app', 'Fulltimer current salary'),
            'fulltimer_expected_salary' => Yii::t('app', 'Fulltimer expected salary'),
            'fulltimer_pdf_cv' => Yii::t('app', 'Fulltimer Pdf Cv'),
            'fulltimer_created_datetime' => Yii::t('app', 'Fulltimer Created Datetime'),
            'fulltimer_updated_datetime' => Yii::t('app', 'Fulltimer Updated Datetime'),
            'university_id' => Yii::t('app', 'University ID'),
            'fulltimer_employed' => Yii::t('app', 'Fulltimer employed?'),
            'fulltimer_gender' => Yii::t('app', 'Gender'),
            'fulltimer_driving_license' => Yii::t('app', 'Driving License'),
            'fulltimer_birth_date' => Yii::t('app', 'Birth Date'),
            "currency_code" => Yii::t('app','Currency Code'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'nationality',
            'country',
            'area',
            'fulltimerTags',
            'notes',
            'acceptanceRatio',
            'rejectionRatio',
            'suggested',
            'suggestionAccepted',
            'suggestionRejected',
            'fulltimerSkills',
            'fulltimerExperiences',
            'university'
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     * @return bool|void
     */
    public function afterSave($insert, $changedAttributes) {

        if(!$this->tags) {
            return true;
        }

        //remove old 

        FulltimerTags::deleteAll(['fulltimer_uuid' => $this->fulltimer_uuid]);

        if(!is_array($this->tags)) {
            $this->tags = json_decode($this->tags);
        }

        //add tags 
    
        foreach($this->tags as $flltimerTag) {
            $model = new FulltimerTags;
            $model->fulltimer_uuid = $this->fulltimer_uuid;
            $model->tag = is_object($flltimerTag)? $flltimerTag->tag: $flltimerTag['tag'];

            if(!$model->tag) {
                continue;
            }
            
            if(!$model->save()) {
                print_r($model->errors);
                die();
            }
        }

        $this->updateAlgoliaIndex($insert);

        if(YII_ENV == 'prod') {
            if ($insert)
            {
                Yii::$app->eventManager->track('Fulltimer Created', [
                        'fulltimer_uuid' => $this->fulltimer_uuid
                    ]);
            }
            else
            {
                Yii::$app->eventManager->track('Fulltimer Updated', [
                        'fulltimer_uuid' => $this->fulltimer_uuid
                    ]);
            }
        }

        return true;
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave ($insert)) {
            return false;
        }

        //on resume uploaded

        if($insert && $this->fulltimer_pdf_cv) {
            return $this->updateResume();
        }

        //on resume updated

        if(!$insert && $this->fulltimer_pdf_cv && $this->fulltimer_pdf_cv != $this->oldAttributes['fulltimer_pdf_cv']) {

            //remove old resume 

            if($this->oldAttributes['fulltimer_pdf_cv']) {
                Yii::$app->resourceManager->delete("fulltimer-resume/" . $this->oldAttributes['fulltimer_pdf_cv']);
            }

            return $this->updateResume();
        }

        //on resume removed

        if(!$insert && !$this->fulltimer_pdf_cv && $this->oldAttributes['fulltimer_pdf_cv']) {
            return $this->deleteResume();
        }

        if(Yii::$app->request instanceof \yii\web\Request) {

            // Get initial IP address of requester
            $ip = Yii::$app->request->getRemoteIP();

            // Check if request is forwarded via load balancer or cloudfront on behalf of user
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];

                // as "X-Forwarded-For" is usually a list of IP addresses that have routed
                if ($forwardedFor) {
                    $IParray = array_values(array_filter(explode(',', $forwardedFor)));

                    // Get the first ip from forwarded array to get original requester
                    if ($IParray) {
                        $ip = $IParray[0];
                    }
                }
            }

            $this->ip_address = $ip;
        }

        if(!$this->currency_code) {
            $this->currency_code = "KWD";
        }

        return true;
    }

    /**
     * Update/Insert data on algolia index
     * @param bool $insert
     */
    public function updateAlgoliaIndex($insert = false) {

        $data = $this->prepareAlgoliaData($insert);

        //if profile incomplete

        if (!$data) {
            return false;
        }

        if ($insert) { // candidate registered
            Yii::$app->algolia->add(Yii::$app->params['algolia_fulltimer_index'], $data);
        } else { // candidate data updated
            Yii::$app->algolia->partialUpdate(Yii::$app->params['algolia_fulltimer_index'], $data);
        }
    }

    /**
     * Return array of job detail to update in algolia index
     */
    public function prepareAlgoliaData($insert = false) {

        $data = [
            'objectID' => $this->fulltimer_uuid,
            'fulltimer_name' => $this->fulltimer_name,
            'fulltimer_phone' => $this->fulltimer_phone,
            'fulltimer_email' => $this->fulltimer_email,
            'fulltimer_pdf_cv' => $this->fulltimer_pdf_cv,
            'fulltimer_current_salary' => $this->fulltimer_current_salary,
            'fulltimer_expected_salary' => $this->fulltimer_expected_salary,
            "currency_code" => $this->currency_code,
            'fulltimer_created_datetime' => $this->fulltimer_created_datetime,
            'fulltimer_updated_datetime' => $this->fulltimer_updated_datetime,
            'have_resume' => $this->fulltimer_pdf_cv? 'Yes': 'No',
            'fulltimer_employed' => $this->fulltimer_employed? 'Yes': 'No',
            'fulltimer_birth_timestamp' => $this->fulltimer_birth_date?
                strtotime($this->fulltimer_birth_date): null,
            'fulltimer_driving_license' => $this->fulltimer_driving_license
        ];

        //to make gender label visible to filter instead of 1,0

        if ($this->fulltimer_gender == self::GENDER_FEMALE) {
            $data['fulltimer_gender'] = 'Female';
        } elseif ($this->fulltimer_gender == self::GENDER_MALE) {
            $data['fulltimer_gender'] = 'Male';
        } else {
            $data['fulltimer_gender'] = 'Other';
        }

        if($this->nationality) {
            $data['nationality'] = [
                'nationality_id' => $this->nationality_id,
                'nationality_name_en' => $this->nationality->country_nationality_name_en,
                'nationality_name_ar' => $this->nationality->country_nationality_name_ar
            ];
        }

        if($this->area) {
            $data['area'] = [
                'area_name_en' => $this->area->area_name_en,
                'area_name_ar' => $this->area->area_name_ar
            ];
        }

        if($this->country) {
            $data['country'] = [
                'country_id' => $this->country_id,
                'country_name_en' => $this->country->country_name_en,
                'country_name_ar' => $this->country->country_name_ar
            ];
        }

        //geo location

        if ($this->fulltimer_latitude && $this->fulltimer_longitude) {
            $data["_geoloc"] = [
                "lat" => (float) $this->fulltimer_latitude,
                "lng" => (float) $this->fulltimer_longitude,
            ];
        } elseif ($this->area && $this->area->area_latitude && $this->area->area_longitude) {
            $data["_geoloc"] = [
                "lat" => (float) $this->area->area_latitude,
                "lng" => (float) $this->area->area_longitude
            ];
        } else {
            $data["_geoloc"] = [
                "lat" => 0,
                "lng" => 0
            ];
        }

        if ($this->area && $this->area->country) {

            $data['currentLocations']['en'] = [
                $this->area->country->country_name_en,
                $this->area->area_name_en . ', ' . $this->area->country->country_name_en,
            ];

            $data['currentLocations']['ar'] = [
                $this->area->country->country_name_ar,
                $this->area->area_name_ar . ', ' . $this->area->country->country_name_ar
            ];
        }

        if ($insert) {
            $data['fulltimer_created_datetime'] = date('Y-m-d H:i:s');
            $data['fulltimer_updated_datetime'] = date('Y-m-d H:i:s');
            $data['fulltimer_created_timestamp'] = time();
            $data['fulltimer_updated_timestamp'] = time();
        } else {
            $data['fulltimer_created_datetime'] = $this->fulltimer_created_datetime;
            //could be `new Expression('NOW()')` on update
            $data['fulltimer_updated_datetime'] = is_string($this->fulltimer_updated_datetime) ?
                $this->fulltimer_updated_datetime : date('Y-m-d H:i:s');
            $data['fulltimer_created_timestamp'] = strtotime($this->fulltimer_created_datetime);
            $data['fulltimer_updated_timestamp'] = $this->fulltimer_updated_datetime?
                strtotime($this->fulltimer_updated_datetime): null;
        }

        //fulltimer_tags

        $data['fulltimerTags'] = [];

        foreach ($this->getFulltimerTags()->all() as $fulltimerTag) {
            $data['fulltimerTags'][] = [
                'tag' => $fulltimerTag->tag
            ];
        }

        //fulltimer_experience

        $data['fulltimerExperiences'] = [];

        foreach ($this->getFulltimerExperiences()->all() as $experience) {
            $data['fulltimerExperiences'][] = [
                'experience' => $experience->experience
            ];
        }

        //fulltimer_skill

        $data['fulltimerSkills'] = [];

        foreach ($this->getFulltimerSkills()->select('skill')->all() as $candidateSkill) {
            $data['fulltimerSkills'][] = [
                'skill' => $candidateSkill->skill
            ];
        }

        if($this->university) {

            $university_name = [];

            if($this->university->university_name_en) {
                $university_name[] = $this->university->university_name_en;
            }

            if($this->university->university_name_ar) {
                $university_name[] = $this->university->university_name_ar;
            }

            $data['university'] = [
                'university_id' => $this->university_id,
                'university_name_en' => $this->university->university_name_en,
                'university_name_ar' => $this->university->university_name_ar,
                'university_name' => $university_name
            ];
        }

        /**
         *
        fulltimer_employed
        fulltimer_gender
        fulltimer_birth_timestamp
        fulltimer_updated_at_timestamp
        fulltimer_driving_license

        fulltimerSkills.skill
        fulltimerExperiences.experience
        university.university_name
         */
        return $data;
    }

    /**
     * Synch with algolia
     * @return type
     */
    public static function synchWithAlgolia($type = "all") {

        //delete all objects

        //Yii::$app->algolia->clearObjects(Yii::$app->params['algolia_fulltimer_index']);

        //call api in batch

        $query = self::find()
            ->joinWith([
                'fulltimerTags'
            ]);

        $total = $query->count();

        //send 100 in each request

        Console::startProgress(0, $total);

        $n = 0;

        foreach ($query->batch(100) as $fulltimers) {

            $data = [];

            foreach ($fulltimers as $fulltimer) {

                $algoliaData = $fulltimer->prepareAlgoliaData();

                if ($algoliaData)
                    $data[] = $algoliaData;
            }

            if ($data)
                Yii::$app->algolia->updates(Yii::$app->params['algolia_fulltimer_index'], $data);

            $n += sizeof($data);

            Console::updateProgress($n, $total);
        }

        return $total;
    }

    /**
     * save resume to permanent bucket
     * @return boolean
     */
    public function updateResume() {

        $fileName = $this->fulltimer_pdf_cv;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        $targetPath = "fulltimer-resume/" . $fileName;

        // Copy using S3ResourceManager Component

        try {

            Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('fulltimer_pdf_cv', Yii::t('app', 'Resume not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('fulltimer_pdf_cv', Yii::t('app', 'Resume not available to save.'));

            return false;
        }

        return true;
    }

    /**
     * delete resume from permanent bucket
     * @return boolean
     */
    public function deleteResume() {

        try {

            Yii::$app->resourceManager->delete("fulltimer-resume/" . $this->fulltimer_pdf_cv);

            $this->fulltimer_pdf_cv = null;

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('fulltimer_pdf_cv', Yii::t('app', 'Resume not available to delete.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'staff');

            $this->addError('fulltimer_pdf_cv', Yii::t('app', 'Resume not available to delete.'));

            return false;
        }

        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimerExperiences($modelClass = "\common\models\FulltimerExperience")
    {
        return $this->hasMany($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimerSkills($modelClass = "\common\models\FulltimerSkill")
    {
        return $this->hasMany($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\common\models\University")
    {
        return $this->hasOne($modelClass::className(), ['university_id' => 'university_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'country_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\common\models\Area")
    {
        return $this->hasOne($modelClass::className(), ['area_uuid' => 'fulltimer_area_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'nationality_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimerTags($modelClass = "\common\models\FulltimerTags")
    {
        return $this->hasMany($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\common\models\Suggestion")
    {
        return $this->hasMany($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestInterview($modelClass = "\common\models\RequestInterview")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid'])
            ->orderBy("created_at DESC");
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestInterviews($modelClass = "\common\models\RequestInterview")
    {
        return $this->hasMany($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid'])
            ->orderBy("created_at DESC");
    }

    public function getSuggested() {
        return $this->getSuggestion()->count();
    }

    public function getSuggestionAccepted() {
        return $this->getSuggestion()->andWhere(['suggestion_status' => Suggestion::TYPE_ACCEPTED])->count();
    }

    public function getSuggestionRejected() {
        return $this->getSuggestion()->andWhere(['suggestion_status' => Suggestion::TYPE_REJECTED])->count();
    }

    /**
     * get user accepted suggestion ratio
     * @return float|int
     */
    public function getAcceptanceRatio() {
        $total = $this->getSuggestion()->count();
        $accepted = $this->getSuggestion()->andWhere(['suggestion_status'=>Suggestion::TYPE_ACCEPTED])->count();

        return ($total && $accepted) ? round(($accepted/$total)  * 100): null;
    }
    /**
     * get user rejected suggestion ratio
     * @return float|int
     */
    public function getRejectionRatio() {
        $total = $this->getSuggestion()->count();
        $rejected = $this->getSuggestion()->andWhere(['suggestion_status'=>Suggestion::TYPE_REJECTED])->count();
        return ($total && $rejected) ? round(($rejected/$total) * 100): null;
    }

    public static function find()
    {
        return new query\FulltimerQuery(get_called_class());
    }
}
