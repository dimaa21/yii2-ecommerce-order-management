<?php

declare(strict_types=1);

namespace app\models\search;

use app\models\Product;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

class ProductSearch extends Model
{
    public string $search = '';
    public string $name = '';
    public string $sku = '';
    public string|int|null $category_id = null;
    public string $status = '';
    public string|float|null $min_price = null;
    public string|float|null $max_price = null;
    public string|int|null $min_stock = null;
    public string|int|null $max_stock = null;

    public function rules(): array
    {
        return [
            [['search', 'name', 'sku', 'status'], 'trim'],
            [['search', 'name', 'sku'], 'string', 'max' => 255],
            [['category_id', 'min_stock', 'max_stock'], 'integer'],
            [['min_price', 'max_price'], 'number'],
            [['status'], 'in', 'range' => array_keys(Product::statusOptions())],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'search' => 'Search',
            'category_id' => 'Category',
            'status' => 'Status',
            'min_price' => 'Min price',
            'max_price' => 'Max price',
            'min_stock' => 'Min stock',
            'max_stock' => 'Max stock',
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function search(array $params, ?string $formName = null): ActiveDataProvider
    {
        $query = Product::find()->alias('product')->with('category');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => (int) (\Yii::$app->params['pageSize'] ?? 20),
                'pageSizeLimit' => [1, 100],
            ],
            'sort' => [
                'enableMultiSort' => true,
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes' => [
                    'id',
                    'name',
                    'sku',
                    'price',
                    'stock',
                    'status',
                    'created_at',
                ],
            ],
        ]);

        $this->load($params, $formName);
        if (!$this->validate()) {
            $query->emulateExecution = true;
            return $dataProvider;
        }

        $this->apply($query);

        return $dataProvider;
    }

    private function apply(ActiveQuery $query): void
    {
        $query->andFilterWhere(['product.category_id' => $this->blankToNull($this->category_id)]);
        $query->andFilterWhere(['product.status' => $this->blankToNull($this->status)]);
        $query->andFilterWhere(['>=', 'product.price', $this->blankToNull($this->min_price)]);
        $query->andFilterWhere(['<=', 'product.price', $this->blankToNull($this->max_price)]);
        $query->andFilterWhere(['>=', 'product.stock', $this->blankToNull($this->min_stock)]);
        $query->andFilterWhere(['<=', 'product.stock', $this->blankToNull($this->max_stock)]);
        $query->andFilterWhere(['like', 'product.name', $this->blankToNull($this->name)]);
        $query->andFilterWhere(['like', 'product.sku', $this->blankToNull($this->sku)]);

        $search = trim($this->search);
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'product.name', $search],
                ['like', 'product.sku', $search],
                ['like', 'product.description', $search],
            ]);
        }
    }

    private function blankToNull(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
