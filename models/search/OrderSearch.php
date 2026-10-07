<?php

declare(strict_types=1);

namespace app\models\search;

use app\models\Order;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class OrderSearch extends Model
{
    public string $customer = '';
    public string|int|null $customer_id = null;
    public string $status = '';
    public string $payment_status = '';
    public string $date_from = '';
    public string $date_to = '';

    public function rules(): array
    {
        return [
            [['customer', 'status', 'payment_status', 'date_from', 'date_to'], 'trim'],
            [['customer'], 'string', 'max' => 255],
            [['customer_id'], 'integer'],
            [['status'], 'in', 'range' => array_keys(Order::statusOptions())],
            [['payment_status'], 'in', 'range' => array_keys(Order::paymentStatusOptions())],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'customer' => 'Customer',
            'customer_id' => 'Customer',
            'status' => 'Status',
            'payment_status' => 'Payment',
            'date_from' => 'From',
            'date_to' => 'To',
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function search(array $params, ?string $formName = null): ActiveDataProvider
    {
        $query = Order::find()
            ->alias('o')
            ->joinWith(['customer c'])
            ->with(['items']);

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
                    'id' => [
                        'asc' => ['o.id' => SORT_ASC],
                        'desc' => ['o.id' => SORT_DESC],
                    ],
                    'status' => [
                        'asc' => ['o.status' => SORT_ASC],
                        'desc' => ['o.status' => SORT_DESC],
                    ],
                    'payment_status' => [
                        'asc' => ['o.payment_status' => SORT_ASC],
                        'desc' => ['o.payment_status' => SORT_DESC],
                    ],
                    'total_amount' => [
                        'asc' => ['o.total_amount' => SORT_ASC],
                        'desc' => ['o.total_amount' => SORT_DESC],
                    ],
                    'created_at' => [
                        'asc' => ['o.created_at' => SORT_ASC],
                        'desc' => ['o.created_at' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params, $formName);
        if (!$this->validate()) {
            $query->emulateExecution = true;
            return $dataProvider;
        }

        $query->andFilterWhere(['o.customer_id' => $this->blankToNull($this->customer_id)]);
        $query->andFilterWhere(['o.status' => $this->blankToNull($this->status)]);
        $query->andFilterWhere(['o.payment_status' => $this->blankToNull($this->payment_status)]);

        $customer = trim($this->customer);
        if ($customer !== '') {
            $query->andWhere([
                'or',
                ['like', 'c.name', $customer],
                ['like', 'c.email', $customer],
            ]);
        }

        if ($this->date_from !== '') {
            $from = strtotime($this->date_from . ' 00:00:00');
            if ($from !== false) {
                $query->andWhere(['>=', 'o.created_at', $from]);
            }
        }
        if ($this->date_to !== '') {
            $to = strtotime($this->date_to . ' 23:59:59');
            if ($to !== false) {
                $query->andWhere(['<=', 'o.created_at', $to]);
            }
        }

        return $dataProvider;
    }

    private function blankToNull(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
