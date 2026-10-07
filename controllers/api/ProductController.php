<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\Rbac;
use app\exceptions\ValidationException;
use app\models\Product;
use app\models\search\ProductSearch;
use Yii;
use yii\db\IntegrityException;
use yii\web\NotFoundHttpException;

class ProductController extends ApiController
{
    protected function permissions(): array
    {
        return [Rbac::PRODUCT_MANAGE];
    }

    public function actionIndex(): array
    {
        $search = new ProductSearch();
        $provider = $search->search(Yii::$app->request->queryParams, '');
        if ($search->hasErrors()) {
            throw new ValidationException($search->getErrors());
        }

        return $this->paginate($provider);
    }

    public function actionView(int $id): array
    {
        return $this->find($id)->toArray([], $this->expand());
    }

    public function actionCreate(): array
    {
        $model = new Product();
        $model->load(Yii::$app->request->bodyParams, '');
        try {
            if (!$model->save()) {
                throw new ValidationException($model->getErrors());
            }
        } catch (IntegrityException) {
            throw new ValidationException(['sku' => ['This SKU is already in use.']]);
        }

        Yii::$app->response->statusCode = 201;

        return $model->toArray([], $this->expand());
    }

    public function actionUpdate(int $id): array
    {
        $model = $this->find($id);
        $model->load(Yii::$app->request->bodyParams, '');
        try {
            if (!$model->save()) {
                throw new ValidationException($model->getErrors());
            }
        } catch (IntegrityException) {
            throw new ValidationException(['sku' => ['This SKU is already in use.']]);
        }

        return $model->toArray([], $this->expand());
    }

    public function actionDelete(int $id): array
    {
        $model = $this->find($id);
        try {
            if ($model->delete() === false) {
                throw new \RuntimeException('Failed to delete the product.');
            }
        } catch (IntegrityException) {
            throw new ValidationException([
                'id' => ['This product is used by orders and cannot be deleted.'],
            ]);
        }

        return ['message' => 'Product deleted.'];
    }

    private function find(int $id): Product
    {
        $model = Product::find()->where(['id' => $id])->with('category')->one();
        if (!$model instanceof Product) {
            throw new NotFoundHttpException('Product not found.');
        }

        return $model;
    }
}
