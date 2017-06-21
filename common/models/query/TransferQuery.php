<?php

namespace common\models\query;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[Country]].
 *
 */
class TransferQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%transfer}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%transfer}}.deleted' => 0]);
        return parent::one($db);
    }

    /**
     * @param $transfer_id
     * @return $this
     */
    public function filterParent($transfer_id)
    {
        return $this->andWhere(['parent_transfer_id' => $transfer_id]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterTransfer($id)
    {
        return $this->andWhere(['{{%transfer}}.transfer_id' => $id]);
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function filterCompanyId($company_id)
    {
        return $this->andWhere(['{{%company}}.company_id' => $company_id]);
    }

    /**
     * @param $company_name
     * @return $this
     */
    public function filterCompany($company_name)
    {
        return $this->andWhere(['like', '{{%company}}.company_name', $company_name]);
    }

    /**
     * @param $transfer_status
     * @return $this
     */
    public function filterStatus($transfer_status)
    {
        return $this->andWhere(['{{%transfer}}.transfer_status' => $transfer_status]);
    }

    /**
     * Transfer for login company / his child
     * @param $company
     * @return $this
     */
    public function filterCurrentCompany($company) 
    {
        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map(
            $companies, 
            'company_id', 
            'company_id'
        );

        $company_ids[] = $company->company_id;
        return $this->andWhere([
            'in',
            '{{%transfer}}.company_id',
            $company_ids
        ]);
    }

    /**
     * Filter parent transfer
     */
    public function parentTransfers()
    {
        return $this->andWhere('parent_transfer_id IS NULL');
    }

    /**
     * Field require on listing
     */
    public function selectedFields()
    {
        return $this->select([
            '{{%transfer}}.*',
            'SUM(transfer_cost) AS total_transfer_cost',
            '{{%company}}.company_name',
            '{{%company}}.company_email',
        ]);
    }

    public function selectedFieldsForCompany()
    {
        return $this->select([
            '{{%transfer}}.transfer_id,
             {{%transfer}}.company_id,
             {{%transfer}}.company_total,
             {{%transfer}}.parent_transfer_id,
             {{%transfer}}.transfer_created_at,
             {{%transfer}}.transfer_updated_at,
             {{%transfer}}.transfer_status',
            '{{%company}}.company_name',
            '{{%company}}.company_email',
        ]);
    }
    /**
     * @return $this
     */
    public function companyJoin()
    {
        return $this->leftJoin('{{%company}}', '{{%company}}.company_id = {{%transfer}}.company_id');
    }

    /**
     * @return $this
     */
    public function transferCandidateJoin()
    {
        return $this->joinWith('transferCandidate');
    }

    /**
     * @return $this
     */
    public function transferCandidateJoinForCompany()
    {

        return $this->joinWith( [
            'transferCandidate' => function (\yii\db\ActiveQuery $query) {
                $query->select('{{%transfer_candidate}}.bonus,{{%transfer_candidate}}.candidate_id,{{%transfer_candidate}}.hours,{{%transfer_candidate}}.paid,{{%transfer_candidate}}.tc_id')
                    ->addSelect('{{%transfer_candidate}}.transfer_id,{{%transfer_candidate}}.company_hourly_rate,{{%transfer_candidate}}.hours,{{%transfer_candidate}}.bonus')
                    ->addSelect('{{%candidate}}.candidate_name,{{%candidate}}.candidate_email,{{%candidate}}.store_id')
                    ->addSelect('{{%store}}.store_name')
                    ->innerJoin('candidate','{{%transfer_candidate}}.candidate_id={{%candidate}}.candidate_id')->andWhere(['{{%candidate}}.deleted'=>0])
                    ->innerJoin('store','{{%store}}.store_id={{%candidate}}.store_id')->andWhere(['{{%store}}.deleted'=>0]);
            }
        ]);
    }
}
	