<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\TransferFile;
use common\models\AdminToken;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\TransferFileFixture;
use Codeception\Util\HttpCode;


class TransferFileCest
{
    public $token, $file_uuid = 1;

    public function _fixtures()
    {
        return [
            'admin' => AdminFixture::className(),
            'adminToken' => AdminTokenFixture::className(),
            'transferFiles' => TransferFileFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate transfer file api response for listing');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/transfer-files');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * View transfer file detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $model = TransferFile::find()->one();
        
        $I->wantTo('Validate transfer file api to view transfer file detail');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/transfer-files/' . $model->transfer_file_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
