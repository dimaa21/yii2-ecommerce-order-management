<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\Rbac;
use app\models\Category;
use Yii;
use yii\db\IntegrityException;
use yii\web\Response;

class CategoryController extends WebController
{
    protected function permissions(): array
    {
        return [Rbac::CATEGORY_MANAGE];
    }

    public function actionIndex(): string
    {
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => Category::find()->with('products')->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => (int) Yii::$app->params['pageSize']],
            'sort' => [
                'defaultOrder' => ['name' => SORT_ASC],
                'attributes' => ['name', 'status', 'created_at'],
            ],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model' => $this->findCategory($id),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Category();
        $model->status = Category::STATUS_ACTIVE;
        if ($model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Category created.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findCategory($id);
        if ($model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Category updated.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findCategory($id);
        try {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Category deleted.');
        } catch (IntegrityException) {
            Yii::$app->session->setFlash('error', 'This category still has products. Move or delete them first.');
        }

        return $this->redirect(['index']);
    }

    private function findCategory(int $id): Category
    {
        /** @var Category $model */
        $model = $this->findModel(Category::class, $id);

        return $model;
    }
}
