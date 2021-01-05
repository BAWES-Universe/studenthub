<?php

namespace common\models\query;

use Yii;
use yii\db\ActiveQuery;


/**
 * This is the ActiveQuery class for [[Note]].
 *
 */
class NoteQuery extends ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        return parent::all ($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        return parent::one ($db);
    }

    public function filterContact($contact_uuid) {
        return $this->andWhere (['contact_uuid' => $contact_uuid]);
    }

    public function filterCompany($company_id) {
        return $this->andWhere (['company_id' => $company_id]);
    }
    
    public function filterFulltimer($fulltimer_uuid) {
        return $this->andWhere (['fulltimer_uuid' => $fulltimer_uuid]);
    }

    public function filterCandidate($candidate_id) {
        return $this->andWhere (['candidate_id' => $candidate_id]);
    }

    public function filterRequest($request_uuid) {
        return $this->andWhere (['request_uuid' => $request_uuid]);
    }

    public function filterStaff($request_uuid) {
        return $this->andWhere (['created_by' => $request_uuid]);
    }
}
