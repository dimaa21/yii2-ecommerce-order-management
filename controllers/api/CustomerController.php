<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\Rbac;
use app\exceptions\ValidationException;
use app\models\Customer;
use app\models\search\CustomerSearch;
use Yii;
use yii\web\NotFoundHttpException;

class CustomerController extends ApiController
{
    protected function permissions(): array
    {
        return [Rbac::CUSTOMER_MANAGE];
    }

    public function actionIndex(): array
    {
        $search = new CustomerSearch();
        $provider = $search->search(Yii::$app->request->queryParams, '');
        if ($search->hasErrors()) {
            throw new ValidationException($search->getErrors());
        }

        return $this->paginate($provider);
    }

    public function actionView(int $id): array
    {
        $model = Customer::find()->where(['id' => $id])->with('orders')->one();
        if (!$model instanceof Customer) {
            throw new NotFoundHttpException('Customer not found.');
        }

        return $model->toArray([], $this->expand());
    }
}
