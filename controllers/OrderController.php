<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\Rbac;
use app\exceptions\DomainException;
use app\exceptions\ValidationException;
use app\models\Customer;
use app\models\Order;
use app\models\OrderForm;
use app\models\Product;
use app\models\search\OrderSearch;
use app\services\OrderService;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class OrderController extends WebController
{
    protected function permissions(): array
    {
        return [Rbac::ORDER_MANAGE];
    }

    public function actionIndex(): string
    {
        $search = new OrderSearch();
        $dataProvider = $search->search($this->request->queryParams);

        return $this->render('index', [
            'search' => $search,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $model = $this->findOrder($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $model = new OrderForm();
        if ($model->load($this->request->post()) && $model->validate()) {
            try {
                $order = (new OrderService())->create((int) $model->customer_id, $model->lineItems());
                Yii::$app->session->setFlash('success', 'Order #' . $order->id . ' created.');

                return $this->redirect(['view', 'id' => $order->id]);
            } catch (ValidationException $e) {
                foreach ($e->getErrors() as $attribute => $messages) {
                    foreach ($messages as $message) {
                        $model->addError($attribute, $message);
                    }
                }
            } catch (DomainException $e) {
                $model->addError('items', $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'customers' => $this->customers(),
            'products' => $this->products(),
        ]);
    }

    public function actionUpdate(int $id): Response
    {
        if (!$this->request->isPost) {
            throw new MethodNotAllowedHttpException('Method not allowed.');
        }

        $this->findOrder($id);
        $status = $this->request->post('status');
        $paymentStatus = $this->request->post('payment_status');

        try {
            (new OrderService())->update(
                $id,
                is_string($status) ? $status : null,
                is_string($paymentStatus) ? $paymentStatus : null,
            );
            Yii::$app->session->setFlash('success', 'Order updated.');
        } catch (DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    private function findOrder(int $id): Order
    {
        $model = Order::find()->where(['id' => $id])->with(['customer', 'items.product'])->one();
        if (!$model instanceof Order) {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        return $model;
    }

    /**
     * @return array<int, string>
     */
    private function customers(): array
    {
        $customers = Customer::find()->orderBy(['name' => SORT_ASC])->all();

        return ArrayHelper::map(
            $customers,
            'id',
            static fn (Customer $customer): string => $customer->name . ' (' . $customer->email . ')',
        );
    }

    /**
     * @return Product[]
     */
    private function products(): array
    {
        return Product::find()->active()->with('category')->orderBy(['name' => SORT_ASC])->all();
    }
}
