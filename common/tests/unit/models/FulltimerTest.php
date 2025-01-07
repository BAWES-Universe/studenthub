<?php
namespace common\tests\models;

use Yii;
use common\models\Area;
use common\models\Fulltimer;
use common\fixtures\FulltimerFixture;
use common\fixtures\AreaFixture;
use common\fixtures\CountryFixture;
use common\fixtures\UniversityFixture;
use Codeception\Specify;


class FulltimerTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures(){
        return [
            'fulltimer' => FulltimerFixture::class,
            'area' => AreaFixture::class,
            'country' => CountryFixture::class,
            'university' => UniversityFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Fulltimer::find()->one());
        //});

        //$this->specify('Fulltimer model fields validation', function () {

            $model = new Fulltimer();
            
            $this->assertFalse($model->validate(['nationality_id']), "Nationality ID is not valid   ");
           // $this->assertFalse($model->validate(['university_id']), "University ID is not valid");
            $this->assertFalse($model->validate(['country_id']), "Country ID is not valid");
            $this->assertFalse($model->validate(['fulltimer_area_uuid']), "Fulltimer Area UUID is not valid");
            $this->assertFalse($model->validate(['fulltimer_latitude']), "Fulltimer Latitude is not valid");
            $this->assertFalse($model->validate(['fulltimer_longitude']), "Fulltimer Longitude is not valid");
            $this->assertFalse($model->validate(['fulltimer_name']), "Fulltimer Name is not valid");
            $this->assertFalse($model->validate(['fulltimer_phone']), "Fulltimer Phone is not valid");
            $this->assertFalse($model->validate(['fulltimer_email']), "Fulltimer Email is not valid");
            /*$this->assertFalse($model->validate(['fulltimer_pdf_cv']), "Fulltimer PDF CV is not valid");
            $this->assertFalse($model->validate(['fulltimer_current_salary']), "Fulltimer Current Salary is not valid");
            $this->assertFalse($model->validate(['fulltimer_expected_salary']), "Fulltimer Expected Salary is not valid");
            */

            $model->fulltimer_area_uuid = 1121212;
            $this->assertFalse($model->validate(['fulltimer_area_uuid']));

            $model->fulltimer_gender = 1121212;
            $this->assertFalse($model->validate(['fulltimer_gender']));

            $model->fulltimer_gender = 1;
            $this->assertTrue($model->validate(['fulltimer_gender']));

            //fulltimer_employed
        //});
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        //$this->specify('Create New Fulltimer', function () {

            $response = Yii::$app->temporaryBucketResourceManager->save(
                null,
                'sample.pdf',
                [],
                codecept_data_dir() . 'files/sample.pdf',
                'application/pdf'
            );

            $area = Area::find()->one();

            $model = new Fulltimer();
            $model->currency_code = "KWD";
            $model->nationality_id = 1;
            $model->country_id = 1;
            $model->fulltimer_area_uuid = $area->area_uuid;
            $model->fulltimer_latitude = 1;
            $model->fulltimer_longitude = 1;
            $model->fulltimer_name = 'Test';
            $model->fulltimer_phone = '874957235';
            $model->fulltimer_email = 'test@localhost.com';
            //$model->fulltimer_pdf_cv = basename($response['ObjectURL']);
            $model->fulltimer_expected_salary = '10';
            $model->fulltimer_current_salary = '11';

            $this->assertTrue($model->save());
        //});

        //$this->specify('Update fulltimer', function() {
            $area = Area::find()->one();

            $model = Fulltimer::find()
                ->joinWith(['university'])
                ->one();

            $model->fulltimer_area_uuid = $area->area_uuid;
            $model->fulltimer_name = 'Matro';
            $model->university_id = 1;
            $model->validate();

            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['fulltimer_name' => 'Matro']));
        //});
    }
}
