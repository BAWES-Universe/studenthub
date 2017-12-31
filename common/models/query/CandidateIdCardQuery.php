<?php
namespace common\models\query;

/**
 * This is the ActiveQuery class for [[CandidateIdCard]].
 *
 */
class CandidateIdCardQuery extends \yii\db\ActiveQuery
{
    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function idExpired()
    {
        return $this->andWhere('DATE(expiry_date) < DATE(NOW())');
    }
}