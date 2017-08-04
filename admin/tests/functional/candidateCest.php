<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\models\Candidate;
use common\models\AdminToken;
use admin\fixtures\Company as CompanyFixture;
use admin\fixtures\Store as StoreFixture;
use admin\fixtures\Candidate as CandidateFixture;
use admin\fixtures\Transfer as TransferFixture;
use admin\fixtures\TransferCandidate as TransferCandidateFixture;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\fixtures\Country as CountryFixture;
use Codeception\Util\HttpCode;

class candidateCest
{
    public $token;
    
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'admin' => [
                'class' => AdminFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/admin.php'                
            ],
            'adminToken' => [
                'class' => AdminTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/adminToken.php'
            ],
            'country' => [
                'class' => CountryFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/country.php'                
            ],
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'                
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/store.php'                
            ],
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'                
            ],
            'transfer' => [
                'class' => TransferFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transfer.php'
            ],
            'transferCandidate' => [
                'class' => TransferCandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transferCandidate.php'
            ]
        ]);
        
        $this->token = AdminToken::find()
                ->one()
                ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {        
        $candidate_id = Candidate::find()
            ->one()
            ->candidate_id;
        
        //---------- list candidates to review 
        
        $I->wantTo('Validate admin > candidates api response for review listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('candidates/search?by=review&review=0&expand=store,university,country,company,bank');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------- approve candidate
        
        $I->wantTo('Validate admin > candidates api to approve candidate');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('candidates/approve/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------- list candidates by country
        
        $I->wantTo('Validate admin > candidates api to list candidates by country');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('candidates/search?by=country_id&country_id=168');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------- list candidates by store
        
        $I->wantTo('Validate admin > candidates api to list candidates by store');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('candidates/search?by=store_id&store_id=5');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------- list candidates by university
        
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('candidates/search?by=university_id&university_id=1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------- Get total no of candidate to review 
        
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('candidates/total-to-review');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------- Get candidate salary transfer
        
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('candidates/transfers/' . $candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------- Get candidate's work history 
        
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('candidates/work-history/' . $candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
