<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\helpers\Console;


/**
 * This is the model class for table "major".
 *
 * @property string $major_uuid
 * @property string $major_name_en
 * @property string $major_name_ar
 * @property integer $data_source
 * @property string $major_created_at
 * @property string $major_updated_at
 *
 * @property CandidateEducationMajor[] $candidateEducationMajors
 */
class Major extends \yii\db\ActiveRecord
{
    //Values available for `data_source`
    //This tells us where this model source data is coming from
    const FROM_ADMIN = 0;
    const FROM_CANDIDATE = 1;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'major';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['major_name_en', 'major_name_ar'], 'required'],
            [['data_source'], 'integer'],            
            [['major_created_at', 'major_updated_at'], 'safe'],
            [['major_name_en', 'major_name_ar'], 'string', 'max' => 255],
            [['major_name_en'], 'unique'],
            //Rule for major data source
            ['data_source', 'in', 'range' => [self::FROM_ADMIN, self::FROM_CANDIDATE]],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'major_uuid',
                ],
                'value' => function() {
                    if(!$this->major_uuid)
                        $this->major_uuid = 'major_'. Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    
                    return $this->major_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'major_created_at',
                'updatedAtAttribute' => 'major_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'major_uuid' => Yii::t('app','Major UUID'),
            'major_name_en' => Yii::t('app','Major Name En'),
            'major_name_ar' => Yii::t('app','Major Name Ar'),
            'data_source'   => Yii::t('app','Major Data Source'),            
            'major_created_at' => Yii::t('app','Major Created At'),
            'major_updated_at' => Yii::t('app','Major Updated At'),
        ];
    }
    
    /**
     * Trigger after model save 
     * @param type $insert
     * @param type $changedAttributes
     *
    public function afterSave($insert, $changedAttributes) {
        $this->updateAlgoliaIndex($insert);
    }*/

    /**
     * Update/Insert data on algolia index
     * @param bool $insert
     */
    public function updateAlgoliaIndex($insert = false)
    {
            $data = $this->prepareAlgoliaData($insert);
            
            if (!$data) {
                return true;
            }

            if ($insert) { // new major posted
                Yii::$app->algolia->add(Yii::$app->params['algolia_major_index'], $data);
            } else { // major data updated
                Yii::$app->algolia->partialUpdate(Yii::$app->params['algolia_major_index'], $data);
            }
    }

    /**
     * Return array of Major detail to update in algolia index
     * @return array
     */
    public function prepareAlgoliaData()
    {
        if($this->data_source == self::FROM_CANDIDATE) {
            return null;
        }
        
        return [
            'objectID' => $this->major_uuid,
            'major_uuid' => $this->major_uuid,
            'major_name_en' => $this->major_name_en,
            'major_name_ar' => $this->major_name_ar,
            'major_created_at' => $this->major_created_at,
            'major_updated_at' => $this->major_updated_at,
            'data_source' => (int) ($this->data_source? mb_convert_encoding($this->data_source, "UTF-8"): $this->data_source)
        ];
    }

    /**
     * Synch with algolia 
     * @return type
     */
    public static function synchWithAlgolia()
    {
        //delete all objects
        
        Yii::$app->algolia->clearObjects(Yii::$app->params['algolia_major_index']);
        
        //call api in batch 
        
        $query = self::find();
        
        $total = $query->count();
        
        Console::startProgress(0, $total); 
        
        $n = 0; 
        
        foreach ($query->batch(100) as $majors) {
            
            $data = [];

            foreach ($majors as $major)
            {
                 $raw = $major->prepareAlgoliaData();
                
                 if ($raw) {
                    $data[] = $raw;
                 }
            }

            if($data)
                Yii::$app->algolia->updates(Yii::$app->params['algolia_major_index'], $data);
            
            $n += sizeof($data);
            
            Console::updateProgress($n, $total);
        }
        
        return $total;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateEducations($modelClass = '\common\models\CandidateEducation')
    {
        return $this->hasMany($modelClass::className(), ['major_uuid' => 'major_uuid']);
    }
    
    /**
     * Find by UUID 
     * @param number $id
     * @return Major|array|null
     */
    public static function findByUUID($id)
    {
        return self::find()
            ->where(['major_uuid' =>  $id])
            ->one();
    }
    
    /**
     * @return query\MajorQuery
     *
    public static function find()
    {
        return new \common\models\query\MajorQuery(get_called_class());
    }*/
}
