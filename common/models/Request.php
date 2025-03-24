<?php

namespace common\models;

use staff\models\Staff;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\helpers\ArrayHelper;
use Segment\Segment;
use yii\helpers\Console;
use function Sentry\continueTrace;

/**
 * This is the model class for table "request".
 *
 * @property string $request_uuid
 * @property int $company_id Which company is this request for?
 * @property string $contact_uuid Which contact from this company made the request?
 * @property string $staff_id
 * @property int $request_created_by
 * @property int $request_updated_by
 * @property int $request_position_type 1 - Fulltime, 2 - Partime
 * @property string $request_position_title the job title being requested
 * @property string $request_job_description
 * @property string $request_compensation
 * @property int $request_number_of_employees
 * @property int $no_of_employees_per_story
 * @property string $request_location
 * @property string $request_additional_info
 * @property string $request_status
 * @property string $request_feedback
 * @property string $num_hours_followup_interval
 * @property string $request_started_at
 * @property string $request_assigned_at
 * @property string $request_finished_at
 * @property string $request_re_worked_at
 * @property string $request_delivered_at
 * @property string $request_cancelled_at
 * @property string $request_created_datetime
 * @property string $request_updated_datetime
 * @property int $request_priority
 * @property int $is_old
 * @property int $request_time_spent
 * @property int $nationality_id
 * @property int $gender
 * @property int $our_fees
 * @property string $our_fees_unit
 * @property Company $company
 * @property RequestSkill[] $requestSkills
 * @property CompanyContact $contactUu
 * @property Staff $requestCreatedBy
 * @property Staff $requestUpdatedBy
 */
class Request extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_STARTED = 'started';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FINISHED = 'finished_by_recruitment';
    const STATUS_RE_WORK = 're_work';

    //Gender values for `gender`
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;
    const GENDER_ANY = 0;

    const OUR_FEES_PER_HOUR = "per hour";
    const OUR_FEES_PER_MONTH = "per month";

    const POSITION_TYPE_PART_TIME = 2;
    const POSITION_TYPE_FULL_TIME = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'request_position_title', 'request_job_description','request_compensation'], 'required'],
            [['company_id', 'request_position_type', 'request_number_of_employees','num_hours_followup_interval', 'request_priority', 'request_time_spent','is_old'], 'integer'],
            ['request_status', 'in', 'range' => [self::STATUS_STARTED, self::STATUS_DELIVERED, self::STATUS_CANCELLED, self::STATUS_PENDING, self::STATUS_FINISHED, self::STATUS_RE_WORK]],
            [['request_created_datetime', 'request_updated_datetime'], 'safe'],
            [['request_additional_info','request_job_description','request_compensation', 'request_location'], 'string'],
            [['request_position_title', 'request_feedback'], 'string', 'max' => 255],
            [['our_fees_unit'], "string", "max" => 10],
            [['request_started_at', 'request_assigned_at', 'request_delivered_at', 'request_cancelled_at'], 'safe'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
            [['contact_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyContact::class, 'targetAttribute' => ['contact_uuid' => 'contact_uuid']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            //['contact_uuid', 'validateContact'] contact can be removed from company
            [["our_fees", 'num_hours_followup_interval'], 'number', 'min' => 0],

            ['gender', 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER, self::GENDER_ANY]],
            [['nationality_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class,
                'targetAttribute' => ['nationality_id' => 'country_id']],

            [['request_number_of_employees', 'no_of_employees_per_story'], 'number', 'min' => 1],
            ['no_of_employees_per_story', 'validateNoOfEmplPerStory']
        ];
    }

    /**
     * Validate no of employees per story
     */
    public function validateNoOfEmplPerStory($attribute, $params, $validator) {

        if ($this->no_of_employees_per_story > $this->request_number_of_employees) {

            $this->addError('no_of_employees_per_story', "No of employees per story can not be greater than no of employees in request.");
        }
    }

    /**
     * Validate contact belong to request owner
     */ 
    public function validateContact($attribute, $params, $validator) {

        if ($this->contact_uuid && $this->company_id) {

            $exist = CompanyContact::find()->andWhere([
                'contact_uuid' => $this->contact_uuid,
                'company_id' => $this->company_id
            ])->exists();

            if (!$exist) {
                $this->addError('contact_uuid', "Contact Detail not belongs to this company.");
            }
        }
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'request_uuid',
                ],
                'value' => function() {
                    if (!$this->request_uuid)
                        $this->request_uuid = 'request_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->request_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'request_created_datetime',
                'updatedAtAttribute' => 'request_updated_datetime',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'request_created_by',
                'updatedByAttribute' => 'request_updated_by',
            ],
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave ($insert)) {
            return false;
        }

        if($this->request_status == self::STATUS_STARTED && !$this->request_started_at) {
            $this->request_started_at = new Expression('NOW()');
        }

        if($this->request_status == self::STATUS_CANCELLED && !$this->request_cancelled_at) {
            $this->staff_id = null;
            $this->request_cancelled_at = new Expression('NOW()');
        }

        if($this->request_status == self::STATUS_DELIVERED && !$this->request_delivered_at) {
            $this->request_delivered_at = new Expression('NOW()');
        }

        if($this->staff_id && !$this->request_assigned_at) {
            $this->request_assigned_at = new Expression('NOW()');
        }

        if($this->request_status == self::STATUS_FINISHED) {
            $this->request_finished_at = new Expression('NOW()');
        }

        if($this->request_status == self::STATUS_RE_WORK) {
            $this->request_re_worked_at = new Expression('NOW()');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'company_id' => Yii::t('app', 'Which company is this request for?'),
            'contact_uuid' => Yii::t('app', 'Which contact from this company made the request?'),
            'staff_id' => Yii::t('app', 'Consultant'),
            'request_created_by' => Yii::t('app', 'Request Created By'),
            'request_updated_by' => Yii::t('app', 'Request Updated By'),
            'request_position_type' => Yii::t('app', '1 - Fulltime, 2 - Partime'),
            'request_position_title' => Yii::t('app', 'the job title being requested'),
            'request_job_description' => Yii::t('app', 'Job Description'),
            'request_compensation' => Yii::t('app', 'Compensation'),
            'request_number_of_employees' => Yii::t('app', 'Request Number Of Employees'),
            'no_of_employees_per_story' => Yii::t('app', 'Number Of Employees per Story'),
            'request_location' => Yii::t('app', 'Request Location'),
            'request_additional_info' => Yii::t('app', 'Request Additional Info'),
            'request_status' => Yii::t('app', 'Request Status'),
            'request_feedback' => Yii::t('app', 'Request Feedback'),
            'num_hours_followup_interval' => Yii::t('app', 'num hours followup interval'),
            'request_started_at' =>  Yii::t('app', 'Request started at'),
            'request_assigned_at' => Yii::t('app', 'Request assigned at'),
            'request_delivered_at' => Yii::t('app', 'Request delivered at'),
            'request_re_worked_at' => Yii::t('app', 'Request re worked at'),
            'request_finished_at' => Yii::t('app', 'Request finished at'),
            'request_cancelled_at' => Yii::t('app', 'Request cancelled at'),
            'request_created_datetime' => Yii::t('app', 'Request Created Datetime'),
            'request_updated_datetime' => Yii::t('app', 'Request Updated Datetime'),
            'request_priority' => Yii::t('app', 'Request Priority'),
            'is_old' => Yii::t('app', 'Is Old'),
            'request_time_spent' => Yii::t('app', 'Request Time Spent'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'nationality',
            'requestCreatedBy',
            'requestUpdatedBy',
            'requestCreatedByContact',
            'requestUpdatedByContact',
            'contact',
            'company',
            'lastActivity',
            'requestActivities',
            'suggestions',
            'activeSuggestions',
            'invitations',
            'stats',
            'staff',
            'staffs',
            'stories',
            'storyOwners',
            'requestSkills',
            'requestApplication',
            'newApplicationCount' => function($model) {
                return $model->getRequestApplication()->count();
            },
            "activeSuggestionCount" => function($model) {
                return $model->getActiveSuggestions()->count();
            }
        ];
    }

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios ();

        $scenarios['staffUpdate'] = ['company_id', 'contact_uuid', 'request_position_type', 'request_position_title',
            'request_location', 'request_additional_info', 'request_job_description', 'request_compensation'
        ];

        return $scenarios;
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function fields()
    {
        $fields = parent::fields();
        $fields['story_count'] = function($model) {
            return $model->getStories()->count();
        };
        return $fields;
    }

    /**
     * request owner
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'nationality_id']);
    }

    /**
     * request owner
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id'])
            ->andWhere(['staff.deleted'=>'0']);
    }

    /**
     * all staffs who have worked in this request
     * @return \yii\db\ActiveQuery
     */
    public function getStaffs($modelClass = "\common\models\Staff")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'created_by'])
            ->andWhere(['staff.deleted'=>'0'])
            ->via('requestActivities');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'request_created_by'])
            ->andWhere(['staff.deleted'=>'0']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'request_updated_by'])
            ->andWhere(['staff.deleted'=>'0']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedByContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'request_created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestSkills($modelClass = "\common\models\RequestSkill")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedByContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'request_updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestApplication($modelClass = "\common\models\RequestApplication")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastActivity($modelClass = "\common\models\Note")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('note_created_datetime DESC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('note_created_datetime DESC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestActivities($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('note_created_datetime DESC');
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestions($modelClass = "\common\models\Suggestion") {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('suggestion_datetime DESC');
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\common\models\Invitation") {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('invitation_created_at DESC');
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getActiveSuggestions($modelClass = "\common\models\Suggestion") {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->andWhere(['suggestion_status'=> Suggestion::TYPE_SUGGESTED]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStories($modelClass = "\common\models\Story")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * all staffs who have worked in this request
     * @return \yii\db\ActiveQuery
     */
    public function getStoryStaffs($modelClass = "\common\models\Staff")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id'])
            ->andWhere(['staff.deleted'=>'0'])
            ->via('stories');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStoryOwners($modelClass = "\common\models\Staff")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id'])
            ->via('stories');
    }

    /**
     * create activity record for request
     * @param type $detail
     * @return type
     */
    public function createRequestActivity($detail = null)
    {
        $model = new Note();
        $model->request_uuid = $this->request_uuid;
        $model->contact_uuid = $this->contact_uuid;
        $model->company_id = $this->company_id;
        $model->note_text = $detail;
        $model->save(false);
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     * @throws \yii\db\Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if($insert)
        {
            $this->company->last_request_datetime = new Expression("NOW()");
            $this->company->save(false);

            //Add stories based on request_number_of_employees

//            $count = ceil($this->request_number_of_employees / $this->no_of_employees_per_story);
//
//            $total = 0;
//
//            for ($i=0; $i < $count; $i++) {
//
//                //assigned to story
//
//                $total += $this->no_of_employees_per_story;
//
//                $story = new Story();
//                $story->staff_id = $this->staff_id;
//                $story->request_uuid = $this->request_uuid;
//                $story->story_status = Story::STATUS_UNSTARTED;
//                $story->number_of_employees = $total <= $this->request_number_of_employees ?
//                    $this->no_of_employees_per_story: $this->no_of_employees_per_story - ($total - $this->request_number_of_employees);
//
//                if(!$story->save()) {
//                    Yii::error($story->errors);
//                }
//            }

                $story = new Story();
                $story->staff_id = $this->staff_id;
                $story->request_uuid = $this->request_uuid;
                $story->story_status = Story::STATUS_UNSTARTED;
                $story->number_of_employees = 1;
                // TODO need to change once team is easy, they can add as many as stories

                if(!$story->save()) {
                    Yii::error($story->errors);
                }
        }

        /**
         * If they close the request then all stories () under that
         * request will change its status to closed
         */
        if(isset($changedAttributes['request_status'])) {

            if($this->request_status == self::STATUS_CANCELLED) 
            {
                //todo: check story activity time not getting added in velocity

                Story::updateAll (['story_status' => Story::STATUS_CANCELLED], [
                    'request_uuid' => $this->request_uuid
                ]);
                /**
                 [
                    'IN',
                    'story_status',
                    [
                        Story::STATUS_STARTED,
                        Story::STATUS_UNSTARTED,
                        Story::STATUS_DELIVERED,
                        Story::STATUS_REJECTED
                    ]
                 ]*/
            }
        }

        Company::updateRequest($this->company_id);

        if(YII_ENV == 'prod' && !Yii::$app->user->isGuest) {

            if ($insert)
            {
                Yii::$app->eventManager->track('Request Created', [
                        'company_id' => $this->company_id,
                        'company' => $this->company->company_name,
                        'request_uuid' => $this->request_uuid
                    ]);
            }
            else
            {
                Yii::$app->eventManager->track('Request Updated', [
                        'company_id' => $this->company_id,
                        'company' => $this->company->company_name,
                        'request_uuid' => $this->request_uuid,
                        'request_status' => $this->request_status,
                        'staff_id' => $this->staff_id
                    ]);
            }
        }
    }

    /**
     * @param $currency_code
     * @return bool|int|string|null
     */
    public static function activeRequestCount($currency_code = "KWD")
    {
        $query = Request::find()
            ->needUpdate();

        if($currency_code) {
            $query->joinWith('company')
                ->andWhere(['company.currency_code' => $currency_code]);
        }

        return $query->count();
    }

    /**
     * @param $currency_code
     * @return bool|int|string|null
     */
    public static function totalRequestCount($currency_code = "KWD") {
        $query = Request::find()
            ->activeRequest();

        if($currency_code) {
            $query->joinWith('company')
                ->andWhere(['company.currency_code' => $currency_code]);
        }

        return $query->count();
    }

    /**
     * @param $start_date
     * @param $end_date
     * @param $request_status
     * @param $company_name
     * @param $company_id
     * @param $companyIds
     * @return bool|int|string|null
     */
    public static function totalRequestCountByStatus(
        $request_status = null,
        $companyIds = [],
        $company_id = null,
        $company_name = null,
        $start_date = null,
        $end_date = null
    ) {
        $query = Request::find();

        if (sizeof($companyIds) > 0) {
            $query->andWhere(['in', 'company_id', $companyIds]);
        }

        if ($company_id) {
            $query->andWhere(['company_id' => $company_id]);
        }

        if ($company_name) {
            $query->joinWith('company')
                ->andWhere([
                    'OR',
                    ['like', 'company_common_name_en', $company_name],
                    ['like', 'company_common_name_ar', $company_name],
                    ['like', 'company_name', $company_name]
                ]);
        }

        if($start_date) {
            $query->startDate($start_date);
        }

        if($end_date) {
            $query->endDate($end_date);
        }

        if($request_status) {
            $query->andWhere(['request_status' => $request_status]);
        }

        return $query->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestInterview($modelClass = "\common\models\RequestInterview")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy("created_at DESC");
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestInterviews($modelClass = "\common\models\RequestInterview")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy("created_at DESC");
    }

    /**
     * @inheritdoc
     * @return query\RequestQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\RequestQuery(get_called_class());
    }

    /**
     * @return string
     */
    public function getSuggestionEmailSubject() {
        $type = ($this->request_position_type == 1) ? 'full-time' : 'part-time';
        return 'Suggested candidates for your ' . $type . ' ' . $this->request_position_title . ' position @ ' . $this->company->company_common_name_en;
    }

    /**
     * @return false|string[]|void
     * @throws \Mpdf\MpdfException
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function suggestionCandidateNotification()
    {
        Yii::$app->controller->layout = '@common/mail/layouts/pdf';

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $suggestionGroup = [];

        $latestSuggestion = $this->getSuggestions()
            ->joinWith(['note'])
            ->andWhere([
                "note.note_type" => 'Suggested',
                "suggestion.mail_to_company" => 0
            ])
            ->orderBy('suggestion_datetime DESC')//lastest suggestion
            ->one();

        if($latestSuggestion && $latestSuggestion->note->createdBy) {
            $staff = $latestSuggestion->note->createdBy;
        } else {
            $staff = ($this->requestCreatedBy) ?
                $this->requestCreatedBy :
                $this->requestUpdatedBy;
        }

        $message = Yii::$app->mailer->compose('company/suggestion-notification', [
            'model' => $this,
            'staff' => $staff
        ]);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $message->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        // fetch all suggestion make for each not mailed request

        $suggestions = $this->getSuggestions()
            ->filterNotMailed()
            ->all();

        //  update suggestion table to set mail to company
        Suggestion::updateAll(['mail_to_company' => 1], [
            "IN",
            'suggestion_uuid',
            ArrayHelper::getColumn($suggestions, 'suggestion_uuid')
        ]);

        foreach ($suggestions as $suggestion)
        {
            if (!$suggestion->note) {
                return [
                    "operation" => "error",
                    "message" => "No suggestion note found"
                ];
            }

            if (!isset($suggestionGroup[$suggestion->note->created_by])) {
                $suggestionGroup[$suggestion->note->created_by] = [];
            }

            // grouping of suggestion which are suggested by staff
            $suggestionGroup[$suggestion->note->created_by][] = $suggestion;
        }

        $output = [];

        foreach ($suggestionGroup as $suggestionByStaff) {

            // looping for each suggestion

            $noOfAttachments = 0;

            foreach ($suggestionByStaff as $eachSuggestion) {

                $suggestedByStaff = $eachSuggestion->note->createdBy;

                if (!$eachSuggestion->candidate) {
                    Yii::error('No Candidate on suggestions :' . print_r($eachSuggestion, true));
                    continue;
                    // throw new \yii\console\Exception('Resume not available to attach');
                }

                //get invitation accepted note

                $inviation = Invitation::find()
                    ->where([
                        'candidate_id' => $eachSuggestion->candidate_id,
                        'request_uuid' => $this->request_uuid
                    ])
                    ->one();

                $inviationAcceptedNote = null;

                if($inviation) {
                    $inviationAcceptedNote = Note::find ()
                        ->where ([
                            'invitation_uuid' => $inviation->invitation_uuid,
                            'candidate_id' => $eachSuggestion->candidate_id,
                            'note_type' => Note::TYPE_INVITATION_ACCEPTED
                        ])
                        ->one ();
                }

                $content = Yii::$app->controller->render(
                    '@console/controllers/views/candidate-resume-pdf',
                    [
                        'candidate' => $eachSuggestion->candidate,
                        'withNumber' => true,
                        'staff' => $suggestedByStaff,
                        'because' => $inviationAcceptedNote? $inviationAcceptedNote->note_text: $suggestion->note->note_text,
                        'positionTitle' => $this->request_position_title
                    ]
                );

                $message->attachContent(
                    Suggestion::getPdfObj($eachSuggestion->note, $content),
                    [
                        'fileName' => $eachSuggestion->candidate_id . '.pdf',
                        'contentType' => 'application/pdf'
                    ]
                );

                $noOfAttachments++;
            }

            /**
             * send mail only when cv available
             */
            if($noOfAttachments == 0) {
                Yii::error('No CV on suggestions :' . print_r($suggestionByStaff, true));

                continue;
            }

            // in case if contact doesn't have email address
            if ($this->contact->email && $this->contact->contact_email_verification) {
                $setTo = [$this->contact->email => $this->contact->contact_name];
            } else {
                $setTo = array_unique(Suggestion::getContactEmailByRequest($this));
            }

            /*$setCc = array_merge(
                [
                    Yii::$app->params['operationsEmail'] => 'Operations',
                    $suggestedByStaff->staff_email => $suggestedByStaff->staff_name
                ],
                array_unique(self::getContactEmailByRequest($this))
            );

            $author = ($this->requestCreatedBy) ? $this->requestCreatedBy : $this->requestUpdatedBy;

            if($author && $author->staff_email != $suggestedByStaff->staff_email) {
                $setCc[$author->staff_email] = $author->staff_name;
            }*/

            $setCc = [
                Yii::$app->params['operationsEmail'] => 'Operations',
                Yii::$app->params['accountManagerEmail'] => 'Account Manager'
            ];

            $ml = new MailLog();
            $ml->to = implode(',', $setTo);
            $ml->from = \Yii::$app->params['supportEmail'];
            $ml->subject = $this->suggestionEmailSubject;
            if (!$ml->save()) {
                Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
            }

            $message->setFrom([Yii::$app->params['recruitmentEmail'] => "Recruitment team"])
                //->setFrom([Yii::$app->params['operationsEmail'] => "Recruitment team"])
                //->setReplyTo([$staff->staff_email => $staff->staff_name])
                ->setReplyTo([Yii::$app->params['recruitmentEmail'] => "Recruitment team"])
                ->setTo($setTo)
                ->setCc($setCc)
                //->setBcc([$staff->staff_email => $staff->staff_name])
                ->setSubject($this->suggestionEmailSubject);

            try {
                $message->send();
            } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                // Handle email transport-specific exceptions
                Yii::error( "Failed to send email: " . $e->getMessage());
            } catch (\Exception $e) {
                // Handle any other exceptions
                Yii::error( "An error occurred: " . $e->getMessage());
            }

            if ($staff->staff_email)  {
                $info = "email sent from staff ($staff->staff_email) for request : `($this->request_position_title)` total candidates: " . count($suggestionByStaff) . " \n";
            } else {
                $info = "email sent for request : `($this->request_position_title)` total fulltimer candidates: " . count($suggestionByStaff) . " \n";
            }

            Yii::info($info);

            $output[] = $info;
        }

        return [
            "operation" => "success",
            "message"  => $output
        ];
    }
}
