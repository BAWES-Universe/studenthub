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
            'fulltimer' => FulltimerFixture::className(),
            'area' => AreaFixture::className(),
            'country' => CountryFixture::className(),
            'university' => UniversityFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Check fulltimer tags loaded',
                Fulltimer::find()->one()
            )->notNull();
        });

        $this->specify('Admin model fields validation', function () {

            $model = new Fulltimer();
            
            expect('should not accept empty nationality_id', $model->validate(['nationality_id']))->false();
            //expect('should not accept empty university_id', $model->validate(['university_id']))->false();
            expect('should not accept empty country_id', $model->validate(['country_id']))->false();
            expect('should not accept empty fulltimer_area_uuid', $model->validate(['fulltimer_area_uuid']))->false();
            expect('should not accept empty fulltimer_latitude', $model->validate(['fulltimer_latitude']))->false();
            expect('should not accept empty fulltimer_longitude', $model->validate(['fulltimer_longitude']))->false();
            expect('should not accept empty fulltimer_name', $model->validate(['fulltimer_name']))->false();
            expect('should not accept empty fulltimer_phone', $model->validate(['fulltimer_phone']))->false();
            expect('should not accept empty fulltimer_email', $model->validate(['fulltimer_email']))->false();
            //expect('should not accept empty fulltimer_pdf_cv', $admin->validate(['fulltimer_pdf_cv']))->false();
            expect('should not accept empty fulltimer_current_salary', $model->validate(['fulltimer_current_salary']))->false();
            expect('should not accept empty fulltimer_expected_salary', $model->validate(['fulltimer_expected_salary']))->false();

            $model->fulltimer_area_uuid = 1121212;
            expect('should not accept invalid fulltimer_area_uuid', $model->validate(['fulltimer_area_uuid']))->false();

            $model->fulltimer_gender = 1121212;
            expect('should not accept invalid fulltimer_gender', $model->validate(['fulltimer_gender']))->false();

            $model->fulltimer_gender = 1;
            expect('should not accept invalid fulltimer_gender', $model->validate(['fulltimer_gender']))->true();

            //fulltimer_employed
        });
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        $this->specify('Create New Fulltimer', function () {

            $response = Yii::$app->temporaryBucketResourceManager->save(
                null,
                'sample.pdf',
                [],
                codecept_data_dir() . 'files/sample.pdf',
                'application/pdf'
            );

            $area = Area::find()->one();

            $model = new Fulltimer();
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

            expect('Created successfully', $model->save())->true();
        });

        $this->specify('Update fulltimer', function() {
            $area = Area::find()->one();

            $model = Fulltimer::find()
                ->joinWith(['university'])
                ->one();

            $model->fulltimer_area_uuid = $area->area_uuid;
            $model->fulltimer_name = 'Matro';
            $model->university_id = 1;
            $model->validate();

            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['fulltimer_name' => 'Matro']))->notNull();
        });
    }
}
