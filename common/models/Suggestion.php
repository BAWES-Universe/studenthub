<?php

namespace common\models;

use kartik\mpdf\Pdf;
use Yii;
use yii\base\BaseObject;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\VarDumper;


/**
 * This is the model class for table "suggestion".
 *
 * @property string $suggestion_uuid
 * @property string $request_uuid
 * @property string $fulltimer_uuid
 * @property int $candidate_id
 * @property string $note_uuid
 * @property int $suggestion_status 1-Suggested , 2- rejected, 3- accepted
 * @property string $suggestion_datetime
 *
 * @property Candidate $candidate
 * @property Fulltimer $fulltimer
 * @property Note $note
 * @property Request $request
 */
class Suggestion extends \yii\db\ActiveRecord
{
    const TYPE_PENDING = 0;
    const TYPE_SUGGESTED = 1;
    const TYPE_REJECTED = 2;
    const TYPE_ACCEPTED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suggestion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_uuid', 'note_uuid'], 'required'],
            [['candidate_id'], 'integer'],

            ['suggestion_status', 'in', 'range' => [self::TYPE_PENDING, self::TYPE_SUGGESTED, self::TYPE_ACCEPTED, self::TYPE_REJECTED]],

            [['suggestion_datetime'], 'safe'],
            [['candidate_id', 'fulltimer_uuid'], 'validateCandidate', 'skipOnEmpty' => false],
            [['request_uuid'], 'validateDuplicateRequest'],
            [['suggestion_uuid', 'request_uuid', 'fulltimer_uuid', 'note_uuid'], 'string', 'max' => 60],
            [['suggestion_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::className(), 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
            [['note_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Note::className(), 'targetAttribute' => ['note_uuid' => 'note_uuid']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'request',
            'candidate',
            'request',
            'fulltimer',
            'note',
            'createdBy',
            'updatedBy',
            'feedback',// latest feedback
            'feedbacks'// all feedbacks
        ];
    }

    /**
     * Need candidate or fulltimer
     */
    public function validateCandidate($attribute)
    {
        if (!$this->candidate_id && !$this->fulltimer_uuid) {
            $this->addError($attribute, Yii::t('app', 'Missing {value}', ['value' => $attribute]));
        }
    }

    /**
     * Validate duplicate request if one is already exist
     */
    public function validateDuplicateRequest($attribute)
    {
        if (
            ($this->candidate_id || $this->fulltimer_uuid) &&
            $this->request_uuid && $this->suggestion_status == Suggestion::TYPE_SUGGESTED
        ) {
            $query = Suggestion::find();
            $query->andWhere(['suggestion_status' => Suggestion::TYPE_SUGGESTED, 'request_uuid' => $this->request_uuid]);

            if ($this->candidate_id) {
                $query->andWhere(['candidate_id' => $this->candidate_id]);
            }
            if ($this->fulltimer_uuid) {
                $query->andWhere(['fulltimer_uuid' => $this->fulltimer_uuid]);
            }
            if ($query->exists()) {
                $this->addError('candidate_id', Yii::t('app', 'Suggestion already suggested'));
            }
        }
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'suggestion_uuid',
                ],
                'value' => function () {
                    if (!$this->suggestion_uuid)
                        $this->suggestion_uuid = 'suggestion_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->suggestion_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'suggestion_datetime',
                'updatedAtAttribute' => null,
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
            'suggestion_uuid' => Yii::t('app', 'Suggestion Uuid'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'fulltimer_uuid' => Yii::t('app', 'Fulltimer Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'note_uuid' => Yii::t('app', 'Note Uuid'),
            'suggestion_status' => Yii::t('app', 'Suggestion Status'),
            'suggestion_datetime' => Yii::t('app', 'Suggestion Datetime'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\common\models\Note")
    {
        return $this->hasOne($modelClass::className(), ['note_uuid' => 'note_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'created_by'])->via('note');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'updated_by'])->via('note');
    }

    /**
     * Show latest feedback in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedback($modelClass = "\common\models\Note")
    {
        return $this->hasOne($modelClass::className(), ['suggestion_uuid' => 'suggestion_uuid'])
            ->orderBy('note_created_datetime DESC');
    }

    /**
     * Show feedbacks in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbacks($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['suggestion_uuid' => 'suggestion_uuid'])
            ->orderBy('note_created_datetime ASC');
    }

    /**
     * send notification to company with suggested candidate profile pdf on their
     * request
     * @throws \Mpdf\MpdfException
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \yii\base\InvalidConfigException
     */
    public static function suggestionCandidateNotification()
    {
        Yii::$app->controller->layout = '@common/mail/layouts/pdf';

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $requests = Request::find()
            ->joinWith([
                'suggestions',
                'suggestions.note',
                //'suggestions.createdBy',
                'requestCreatedBy',
                //'requestUpdatedBy',
            ])
            ->andWhere("`note`.note_type='Suggested' and `suggestion`.`mail_to_company` = 0")
            ->andWhere("`request`.request_position_type=2")
            ->andWhere("`suggestion_datetime` <= NOW() - INTERVAL 20 MINUTE")
            ->all();

        // fetch all request which are suggested to part timer and not mailed

        foreach ($requests as $request) {

            $suggestionGroup = [];

            $staff = ($request->requestCreatedBy) ? $request->requestCreatedBy : $request->requestUpdatedBy;

            $message = Yii::$app->mailer->compose('company/suggestion-notification', [
                'model' => $request
            ]);

            // fetch all suggestion make for each not mailed request

            $suggestions = $request->getSuggestions()
                ->filterNotMailed()
                ->all();

            foreach ($suggestions as $suggestion)
            {
                if (!$suggestion->note) {
                    continue;
                }

                if (!isset($suggestionGroup[$suggestion->note->created_by])) {
                    $suggestionGroup[$suggestion->note->created_by] = [];
                }

                // grouping of suggestion which are suggested by staff
                $suggestionGroup[$suggestion->note->created_by][] = $suggestion;
            }

            foreach ($suggestionGroup as $suggestionByStaff) {

                // looping for each suggestion
                foreach ($suggestionByStaff as $eachSuggestion) {

                    $suggestedByStaff = $eachSuggestion->note->createdBy;

                    if (!$eachSuggestion->candidate) {
                        continue;
                        //throw new \yii\console\Exception('Resume not available to attach');
                    }

                    $content = Yii::$app->controller->render(
                        '@console/controllers/views/candidate-resume-pdf',
                        [
                            'candidate' => $eachSuggestion->candidate,
                            'withNumber' => true,
                            'staff' => $suggestedByStaff,
                        ]
                    );

                    $message->attachContent(
                        self::getPdfObj($eachSuggestion->note, $content),
                        [
                            'fileName' => $eachSuggestion->candidate_id . '.pdf',
                            'contentType' => 'application/pdf'
                        ]
                    );

                    //  update suggestion table to set mail to company
                    Suggestion::updateAllCounters(['mail_to_company' => 1], [
                        'suggestion_uuid' => $eachSuggestion->suggestion_uuid
                    ]);
                }

                // in case if contact doesn't have email address
                if ($request->contact->email) {
                    $setTo = [$request->contact->email => $request->contact->contact_name];
                } else {
                    $setTo = array_unique(self::getContactEmailByRequest($request));
                }

                $setCc = array_merge(
                    [Yii::$app->params['adminEmail'] => 'Khalid'],
                    [$suggestedByStaff->staff_email => $suggestedByStaff->staff_name]
                );

                $message->setFrom([$staff->staff_email => $staff->staff_name])
                    ->setTo($setTo)->setCc($setCc)
                    ->setBcc([$staff->staff_email => $staff->staff_name])
                    ->setSubject($request->suggestionEmailSubject)
                    ->send();

                Console::stdout("email sent from staff ($staff->staff_email) for request : `($request->request_position_title)` total candidates: " . count($suggestionByStaff) . " \n", Console::FG_RED, Console::BOLD);
            }
        }
    }

    /**
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function suggestionFulltimerNotification()
    {
        Yii::$app->controller->layout = '@common/mail/layouts/pdf';

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $requests = Request::find()
            ->joinWith([
                'suggestions',
                'suggestions.note',
                //'suggestions.createdBy',
                'requestCreatedBy',
                //'requestUpdatedBy',
            ])
            ->andWhere("`note`.note_type='Suggested' and `suggestion`.`mail_to_company` = 0")
            ->andWhere("`request`.request_position_type=1")
            ->andWhere("`suggestion_datetime` <= NOW() - INTERVAL 20 MINUTE")
            ->all();

        // fetch all request which are suggested to part timer and not mailed
        foreach ($requests as $request) {

            // fetch all suggestion make for each not mailed request

            $suggestionGroup = [];

            $suggestions = $request->getSuggestions()
                ->filterNotMailed()
                ->all();

            foreach ($suggestions as $suggestion) {

                if (!$suggestion->note) {
                    continue;
                }

                if (!isset($suggestionGroup[$suggestion->note->created_by])) {
                    $suggestionGroup[$suggestion->note->created_by] = [];
                }

                // grouping of suggestion which are suggested by staff
                $suggestionGroup[$suggestion->note->created_by][] = $suggestion;
            }

            $staff = ($request->requestCreatedBy) ? $request->requestCreatedBy : $request->requestUpdatedBy;

            foreach ($suggestionGroup as $suggestionByStaff) {

                $message = Yii::$app->mailer->compose('company/suggestion-fulltime', [
                    'model' => $request,
                    'requestSuggestion' => $suggestionByStaff,
                    'staff' => $staff,

                ]);

                // looping for each suggestion

                foreach ($suggestionByStaff as $eachSuggestion) {

                    $suggestedByStaff = $eachSuggestion->note->createdBy;

                    if (
                        $eachSuggestion->note &&
                        $eachSuggestion->note->fulltimer &&
                        $eachSuggestion->note->fulltimer->fulltimer_pdf_cv
                    ) {
                        $url = Yii::$app->resourceManager->getUrl("fulltimer-resume/" . $eachSuggestion->note->fulltimer->fulltimer_pdf_cv);

                        if ($url) {
                            $message->attachContent(file_get_contents($url), [
                                'fileName' => $eachSuggestion->note->fulltimer->fulltimer_pdf_cv,
                                'contentType' => Yii::$app->resourceManager->getType("fulltimer-resume/" . $eachSuggestion->note->fulltimer->fulltimer_pdf_cv)
                            ]);
                        } else {
                            throw new \yii\console\Exception('Resume not available to attach');
                        }
                    } else {
                        throw new \yii\console\Exception('Resume not available to attach');
                    }

                    //  update suggestion table to set mail to company
                    Suggestion::updateAllCounters(['mail_to_company' => 1], [
                        'suggestion_uuid' => $eachSuggestion->suggestion_uuid
                    ]);
                }

                // in case if contact doesn't have email address
                if ($request->contact->email) {
                    $setTo = [$request->contact->email => $request->contact->contact_name];
                } else {
                    $setTo = array_unique(self::getContactEmailByRequest($request));
                }

                $setCc = array_merge(
                    [Yii::$app->params['adminEmail'] => 'Khalid'],
                    [$suggestedByStaff->staff_email => $suggestedByStaff->staff_name]
                );

                $message->setFrom([$staff->staff_email => $staff->staff_name])
                    ->setTo($setTo)->setCc($setCc)
                    ->setBcc([$staff->staff_email => $staff->staff_name])
                    ->setSubject($request->suggestionEmailSubject)
                    ->send();

                Console::stdout("email sent from staff ($staff->staff_email) for request : `($request->request_position_title)` total fulltimer candidates: " . count($suggestionByStaff) . " \n", Console::FG_RED, Console::BOLD);
            }
        }
    }

    /**
     * get contact email by request uuid created by company
     * @param $companyRequest
     * @return array
     */
    private static function getContactEmailByRequest($companyRequest)
    {
        $emails = [];

        //emails of all contacts in invoice company
        $subQuery = CompanyContact::find()
            ->select('contact_uuid')
            ->andWhere([
                'company_id' => $companyRequest->company_id
            ]);

        $contacts = Contact::find()
            ->andWhere(['contact_receive_email' => 1])
            ->andWhere(['in', 'contact_uuid', $subQuery])
            ->andWhere(['<>', 'contact_email', null])
            ->all();

        $emails = array_merge($emails, ArrayHelper::getColumn($contacts, 'contact_email'));

        //company's contact email

//        if ($companyRequest->company->company_email)
//            $emails[] = $companyRequest->company->company_email;

        //if parent company, add company contact email if any + parent company's contact persons' email

        if ($companyRequest->company->parent_company_id) {

//            if ($companyRequest->company->parentCompany->company_email)
//                $emails[] = $companyRequest->company->parentCompany->company_email;

            //add parent company contact

            $subQuery = CompanyContact::find()
                ->select('contact_uuid')
                ->andWhere([
                    'company_id' => $companyRequest->company->parent_company_id
                ]);

            $contacts = Contact::find()
                ->andWhere(['contact_receive_email' => 1])
                ->andWhere(['in', 'contact_uuid', $subQuery])
                ->andWhere(['<>', 'contact_email', null])
                ->all();

            $emails = array_merge($emails, ArrayHelper::getColumn($contacts, 'contact_email'));
        }

        return $emails;
    }

    /**
     * get pdf object for mail attachment
     * @param $profile
     * @param $content
     * @return mixed
     * @throws \Mpdf\MpdfException
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \yii\base\InvalidConfigException
     */
    private static function getPdfObj($profile, $content)
    {
        $pdf = new Pdf([
            'options' => [
                'defaultheaderline' => 0,  //for header
                'defaulfooterline' => 0,  //for footer
                'title' => 'Candidate Resume #' . $profile->candidate->candidate_name
            ],
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            'marginTop' => 5,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => "
                    @font-face {
                          font-family: 'effra';
                          src: url('@staff/web/fonts/effra_std_bd-webfont.woff2') format('woff2'),
                               url('@staff/web/fonts/effra_std_bd-webfont.woff') format('woff'),
                               url('@staff/web/fonts/effra_std_bd-webfont.ttf') format('truetype');
                          font-weight: 700;
                          font-style: normal;
                        }

                        @font-face {
                          font-family: 'effra';
                          src: url('@staff/web/fonts/effra_std_rg-webfont.woff2') format('woff2'),
                               url('@staff/web/fonts/effra_std_rg-webfont.woff') format('woff'),
                               url('@staff/web/fonts/effra_std_rg-webfont.ttf') format('truetype');
                          font-weight: 400;
                          font-style: normal;
                        }

                        @font-face {
                          font-family: 'effra';
                          src: url('@staff/web/fonts/l') format('woff2'),
                               url('@staff/web/fonts/d.woff') format('woff'),
                               url('@staff/web/fonts/a') format('opentype');
                          font-weight: 500;
                          font-style: normal;
                        }
                        html, body, h1, p, div {
                            font-family: 'effra', sans-serif;
                        }",
        ]);
        return $pdf->output($content, $profile->candidate->candidate_id . '.pdf', 'S');
    }

    /**
     * @inheritdoc
     * @return query\SuggestionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\SuggestionQuery(get_called_class());
    }
}
