<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\Rbac;
use app\models\Category;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class CategoryController extends ApiController
{
    protected function permissions(): array
    {
        return [Rbac::CATEGORY_MANAGE, Rbac::PRODUCT_MANAGE];
    }

    public function actionIndex(): array
    {
        $provider = new ActiveDataProvider([
            'query' => Category::find()->orderBy(['name' => SORT_ASC]),
            'pagination' => [
                'pageSize' => (int) Yii::$app->params['pageSize'],
                'pageSizeLimit' => [1, 100],
            ],
        ]);

        return $this->paginate($provider);
    }

    public function actionView(int $id): array
    {
        $model = Category::find()->where(['id' => $id])->with('products')->one();
        if (!$model instanceof Category) {
            throw new NotFoundHttpException('Category not found.');
        }

        return $model->toArray([], $this->expand() ?: ['products']);
    }
}
