<?php

declare(strict_types=1);

namespace app\models\search;

use app\models\Customer;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class CustomerSearch extends Model
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $search = '';

    public function rules(): array
    {
        return [
            [['name', 'email', 'phone', 'search'], 'trim'],
            [['name', 'email', 'phone', 'search'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'search' => 'Search',
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function search(array $params, ?string $formName = null): ActiveDataProvider
    {
        $query = Customer::find()->alias('customer')->with('orders');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => (int) (\Yii::$app->params['pageSize'] ?? 20),
                'pageSizeLimit' => [1, 100],
            ],
            'sort' => [
                'enableMultiSort' => true,
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes' => ['id', 'name', 'email', 'phone', 'created_at'],
            ],
        ]);

        $this->load($params, $formName);
        if (!$this->validate()) {
            $query->emulateExecution = true;
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'customer.name', $this->blankToNull($this->name)]);
        $query->andFilterWhere(['like', 'customer.email', $this->blankToNull($this->email)]);
        $query->andFilterWhere(['like', 'customer.phone', $this->blankToNull($this->phone)]);

        $search = trim($this->search);
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'customer.name', $search],
                ['like', 'customer.email', $search],
                ['like', 'customer.phone', $search],
            ]);
        }

        return $dataProvider;
    }

    private function blankToNull(mixed $value): mixed
    {
        return $value === null || $value === '' ? null : $value;
    }
}
