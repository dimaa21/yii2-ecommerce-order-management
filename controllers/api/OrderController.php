<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\Rbac;
use app\exceptions\ValidationException;
use app\models\Order;
use app\models\OrderForm;
use app\models\search\OrderSearch;
use app\services\OrderService;
use Yii;
use yii\web\NotFoundHttpException;

class OrderController extends ApiController
{
    protected function permissions(): array
    {
        return [Rbac::ORDER_MANAGE];
    }

    public function actionIndex(): array
    {
        $search = new OrderSearch();
        $provider = $search->search(Yii::$app->request->queryParams, '');
        if ($search->hasErrors()) {
            throw new ValidationException($search->getErrors());
        }

        return $this->paginate($provider);
    }

    public function actionView(int $id): array
    {
        return $this->find($id)->toArray([], $this->expand() ?: ['customer', 'items']);
    }

    public function actionCreate(): array
    {
        $form = new OrderForm();
        $form->load(Yii::$app->request->bodyParams, '');
        if (!$form->validate()) {
            throw new ValidationException($form->getErrors());
        }

        $order = (new OrderService())->create((int) $form->customer_id, $form->lineItems());
        Yii::$app->response->statusCode = 201;

        return $order->toArray([], ['customer', 'items']);
    }

    public function actionUpdate(int $id): array
    {
        $this->find($id);
        $body = Yii::$app->request->bodyParams;
        $order = (new OrderService())->update(
            $id,
            isset($body['status']) ? (string) $body['status'] : null,
            isset($body['payment_status']) ? (string) $body['payment_status'] : null,
        );

        return $order->toArray([], ['customer', 'items']);
    }

    private function find(int $id): Order
    {
        $model = Order::find()->where(['id' => $id])->with(['customer', 'items.product'])->one();
        if (!$model instanceof Order) {
            throw new NotFoundHttpException('Order not found.');
        }

        return $model;
    }
}
