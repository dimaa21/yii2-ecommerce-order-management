<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\Rbac;
use app\models\Category;
use app\models\Product;
use app\models\search\ProductSearch;
use Yii;
use yii\db\IntegrityException;
use yii\web\Response;

class ProductController extends WebController
{
    protected function permissions(): array
    {
        return [Rbac::PRODUCT_MANAGE];
    }

    public function actionIndex(): string
    {
        $search = new ProductSearch();
        $dataProvider = $search->search($this->request->queryParams);

        return $this->render('index', [
            'search' => $search,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model' => $this->findProduct($id),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Product();
        $model->status = Product::STATUS_ACTIVE;
        $model->stock = 0;

        if ($model->load($this->request->post()) && $this->save($model)) {
            Yii::$app->session->setFlash('success', 'Product created.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'categories' => $this->categories($model),
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findProduct($id);
        if ($model->load($this->request->post()) && $this->save($model)) {
            Yii::$app->session->setFlash('success', 'Product updated.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'categories' => $this->categories($model),
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findProduct($id);
        try {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Product deleted.');
        } catch (IntegrityException) {
            Yii::$app->session->setFlash('error', 'This product is used by orders. Deactivate it instead of deleting it.');
        }

        return $this->redirect(['index']);
    }

    private function findProduct(int $id): Product
    {
        /** @var Product $model */
        $model = $this->findModel(Product::class, $id);

        return $model;
    }

    /**
     * @return array<int, string>
     */
    private function categories(Product $model): array
    {
        $categories = Category::find()
            ->where(['or', ['status' => Category::STATUS_ACTIVE], ['id' => $model->category_id]])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return \yii\helpers\ArrayHelper::map($categories, 'id', 'name');
    }

    private function save(Product $model): bool
    {
        try {
            return $model->save();
        } catch (IntegrityException) {
            $model->addError('sku', 'This SKU is already in use.');
            return false;
        }
    }
}
