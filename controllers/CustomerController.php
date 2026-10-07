<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\Rbac;
use app\models\Customer;
use app\models\search\CustomerSearch;
use Yii;
use yii\db\IntegrityException;
use yii\web\Response;

class CustomerController extends WebController
{
    protected function permissions(): array
    {
        return [Rbac::CUSTOMER_MANAGE];
    }

    public function actionIndex(): string
    {
        $search = new CustomerSearch();
        $dataProvider = $search->search($this->request->queryParams);

        return $this->render('index', [
            'search' => $search,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $model = $this->findCustomer($id);

        return $this->render('view', [
            'model' => $model,
            'orders' => $model->getOrders()->orderBy(['id' => SORT_DESC])->all(),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Customer();
        if ($model->load($this->request->post()) && $this->save($model)) {
            Yii::$app->session->setFlash('success', 'Customer created.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findCustomer($id);
        if ($model->load($this->request->post()) && $this->save($model)) {
            Yii::$app->session->setFlash('success', 'Customer updated.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findCustomer($id);
        try {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Customer deleted.');
        } catch (IntegrityException) {
            Yii::$app->session->setFlash('error', 'This customer has orders and cannot be deleted.');
        }

        return $this->redirect(['index']);
    }

    private function findCustomer(int $id): Customer
    {
        /** @var Customer $model */
        $model = $this->findModel(Customer::class, $id);

        return $model;
    }

    private function save(Customer $model): bool
    {
        try {
            return $model->save();
        } catch (IntegrityException) {
            $model->addError('email', 'This email is already registered.');
            return false;
        }
    }
}
