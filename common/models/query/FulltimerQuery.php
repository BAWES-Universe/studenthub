<?php

namespace common\models\query;

use common\models\Fulltimer;
use yii\db\Expression;

/**
 * This is the ActiveQuery class for [[FulltimerSkill]].
 *
 */
class FulltimerQuery extends \yii\db\ActiveQuery
{
    /**
     * @inheritdoc
     * @return Fulltimer[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Fulltimer|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param $id
     * @return FulltimerQuery
     */
    public function filterById($id)
    {
        return $this->andWhere(['{{%fulltimer}}.fulltimer_uuid'=>$id]);
    }

    /**
     * @param $fulltimer_name
     * @return FulltimerQuery
     */
    public function filterName($fulltimer_name)
    {
        return $this->andWhere(['like', '{{%fulltimer}}.fulltimer_name', $fulltimer_name]);
    }

    /**
     * @param $fulltimer_email
     * @return FulltimerQuery
     */
    public function filterEmail($fulltimer_email)
    {
        return $this->andWhere(['like', '{{%fulltimer}}.fulltimer_email', $fulltimer_email]);
    }

    public function filterGender($gender)
    {
        return $this->andWhere(['{{%fulltimer}}.fulltimer_gender' => $gender]);
    }

    public function filterAge($values)
    {
        return $this->andWhere(new Expression("YEAR(CURDATE()) - YEAR(fulltimer.fulltimer_birth_date) BETWEEN ".$values[0].
                " AND ".$values[1]));
    }

    public function filterUniversity($university_id)
    {
        return $this->andWhere(['{{%fulltimer}}.university_id' => $university_id]);
    }

    public function filterNationality($country_id)
    {
        return $this->andWhere(['{{%fulltimer}}.nationality_id' => $country_id]);
    }

    public function filterCountry($country_id)
    {
        return $this->andWhere(['{{%fulltimer}}.country_id' => $country_id]);
    }

    public function filterPhone($fulltimer_phone)
    {
        return $this->andWhere(['like', '{{%fulltimer}}.fulltimer_phone', $fulltimer_phone]);
    }

    public function filterEmployed($status)
    {
        return $this->andWhere(['{{%fulltimer}}.fulltimer_employed'=>$status]);
    }

    public function filterDrivingLicense($status)
    {
        return $this->andWhere(['{{%fulltimer}}.fulltimer_driving_license'=>$status]);
    }

    public function filterArea($area_uuid)
    {
        return $this->andWhere(['{{%fulltimer}}.fulltimer_area_uuid'=>$area_uuid]);
    }
}
