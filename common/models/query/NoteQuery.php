<?php

namespace common\models\query;

use common\models\Note;
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

    /**
     * filter by staff who created request
     * @param $staff_id
     * @return NoteQuery
     */
    public function filterCreatedBy($staff_id) {
        return $this->andWhere (['created_by' => $staff_id]);
    }

    /**
     * filter by staff who updated request
     * @param $staff_id
     * @return NoteQuery
     */
    public function filterUpdatedBy($staff_id) {
        return $this->andWhere (['updated_by' => $staff_id]);
    }

    /**
     * filter by contact
     * @param $contact_uuid
     * @return NoteQuery
     */
    public function filterContact($contact_uuid) {
        return $this->andWhere (['contact_uuid' => $contact_uuid]);
    }

    /**
     * filter by company
     * @param $company_id
     * @return NoteQuery
     */
    public function filterCompany($company_id) {
        return $this->andWhere (['company_id' => $company_id]);
    }

    /**
     * filter by fulltimer
     * @param $fulltimer_uuid
     * @return NoteQuery
     */
    public function filterFulltimer($fulltimer_uuid) {
        return $this->andWhere (['fulltimer_uuid' => $fulltimer_uuid]);
    }

    /**
     * filter by candidate
     * @param $candidate_id
     * @return NoteQuery
     */
    public function filterCandidate($candidate_id) {
        return $this->andWhere (['candidate_id' => $candidate_id]);
    }

    /**
     * filter by request
     * @param $request_uuid
     * @return NoteQuery
     */
    public function filterRequest($request_uuid) {
        return $this->andWhere (['request_uuid' => $request_uuid]);
    }

    /**
     * filter by staff
     * @param $staff_uuid
     * @return NoteQuery
     */
    public function filterStaff($staff_uuid) {
        return $this->andWhere (['created_by' => $staff_uuid]);
    }

    /**
     * filter non internal notes
     * @return NoteQuery
     */
    public function filterNonInternal() {
        return $this->andWhere (['!=', 'note_type', Note::TYPE_INTERNAL_NOTE]);
    }
}
