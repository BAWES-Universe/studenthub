<?php

namespace console\controllers;

use Yii;

/**
 * https://xeroapi.github.io/xero-php-oauth2/docs/v2/accounting/index.html#api-Accounting-getBankTransactionsHistory
 */
class XeroController extends \yii\console\Controller
{
    public function actionTest() {
        $result = Yii::$app->xero->getToken();

        print_r($result);
    }

    /**
     * scopes
     * -----------------
     * accounting.transactions	Grant read-write access to bank transactions, credit notes, invoices, repeating invoices
     * accounting.transactions.read	Grant read-only access to invoices
     * @return void
     */
    public function actionSyncTransactions() {

        try {
            $result = Yii::$app->xero->syncTransactions();


        } catch (Exception $e) {
            echo 'Exception when calling AccountingApi->getBankTransactionsHistory: ', $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * sync transaction after given id
     * @param $bankTransactionID
     * @return void
     */
    public function actionSyncAfter($bankTransactionID) {

        try {
            $result = Yii::$app->xero->getBankTransactionsHistory($bankTransactionID);
        } catch (Exception $e) {
            echo 'Exception when calling AccountingApi->getBankTransactionsHistory: ', $e->getMessage(), PHP_EOL;
        }
    }
}