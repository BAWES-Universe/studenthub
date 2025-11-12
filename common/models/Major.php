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
                'class' => AttributeBehavior::class,
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
                'class' => TimestampBehavior::class,
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
     * Update/Insert data on Meilisearch index
     * @param bool $insert
     */
    public function updateMeilisearchIndex($insert = false)
    {
        if (!isset(Yii::$app->meilisearch) || empty(Yii::$app->params['meilisearch_major_index'])) {
            return false;
        }
        
        $data = $this->prepareMeilisearchData($insert);
        
        if (!$data) {
            return true;
        }

        try {
            $indexName = Yii::$app->params['meilisearch_major_index'];
            
            if ($insert) { // new major posted
                Yii::$app->meilisearch->add($indexName, $data);
            } else { // major data updated
                Yii::$app->meilisearch->partialUpdate($indexName, $data);
            }
        } catch (\Exception $e) {
            Yii::error('Failed to update Meilisearch index for major ' . $this->major_uuid . ': ' . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * Prepare data for Meilisearch index
     * @param bool $insert
     * @return array|null
     */
    public function prepareMeilisearchData($insert = false)
    {
        // Reuse the existing data preparation method
        return $this->prepareAlgoliaData($insert);
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
     * Sync majors to Meilisearch
     * @return int Number of majors synchronized
     */
    public static function syncToMeilisearch()
    {
        if (!isset(Yii::$app->meilisearch) || empty(Yii::$app->params['meilisearch_major_index'])) {
            return 0;
        }
        
        //call api in batch 
        
        $query = self::find();
        
        $total = $query->count();
        
        Console::startProgress(0, $total); 
        
        $n = 0; 
        
        foreach ($query->batch(100) as $majors) {
            
            $data = [];

            foreach ($majors as $major)
            {
                 $raw = $major->prepareMeilisearchData();
                
                 if ($raw) {
                    $data[] = $raw;
                 }
            }

            if($data) {
                try {
                    Yii::$app->meilisearch->updates(Yii::$app->params['meilisearch_major_index'], $data);
                } catch (\Exception $e) {
                    Yii::error('Failed to sync batch to Meilisearch: ' . $e->getMessage());
                }
            }
            
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
