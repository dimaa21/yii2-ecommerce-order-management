<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\OrderForm $model */
/** @var array<int, string> $customers */
/** @var app\models\Product[] $products */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'New order';

$productOptions = [];
foreach ($products as $product) {
    $productOptions[$product->id] = sprintf(
        '%s · %s · %s · stock %d',
        $product->name,
        $product->sku,
        Yii::$app->formatter->asCurrency($product->price),
        (int) $product->stock,
    );
}

if ($model->items === []) {
    $model->items = [['product_id' => '', 'quantity' => 1]];
}

$select = static function (string $index, mixed $selected) use ($productOptions): string {
    return Html::dropDownList(
        'OrderForm[items][' . $index . '][product_id]',
        $selected,
        $productOptions,
        ['class' => 'form-select', 'prompt' => 'Select a product'],
    );
};
?>
<?= $this->render('//_page', [
    'title' => 'New order',
    'subtitle' => 'Stock is reserved only if the whole order is saved.',
]) ?>

<div class="oms-card p-4">
    <?php $form = ActiveForm::begin() ?>
        <?= $form->field($model, 'customer_id')->dropDownList($customers, ['prompt' => 'Select a customer']) ?>

        <?php if ($model->hasErrors('items')): ?>
            <div class="alert alert-danger">
                <?= Html::errorSummary($model, ['header' => '']) ?>
            </div>
        <?php endif ?>

        <div class="table-responsive mb-3">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Product</th>
                    <th style="width: 140px">Quantity</th>
                    <th style="width: 80px"></th>
                </tr>
                </thead>
                <tbody id="order-lines">
                <?php foreach ($model->items as $index => $item): ?>
                    <tr>
                        <td><?= $select((string) $index, $item['product_id'] ?? '') ?></td>
                        <td>
                            <input class="form-control" type="number" min="1" step="1" name="OrderForm[items][<?= (int) $index ?>][quantity]" value="<?= Html::encode((string) ($item['quantity'] ?? 1)) ?>">
                        </td>
                        <td>
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-remove-line>Remove</button>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <template id="order-line-template">
            <tr>
                <td><?= $select('__INDEX__', '') ?></td>
                <td><input class="form-control" type="number" min="1" step="1" name="OrderForm[items][__INDEX__][quantity]" value="1"></td>
                <td><button class="btn btn-outline-secondary btn-sm" type="button" data-remove-line>Remove</button></td>
            </tr>
        </template>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" type="button" id="add-order-line">Add product</button>
            <?= Html::submitButton('Create order', ['class' => 'btn btn-dark']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>
    <?php ActiveForm::end() ?>
</div>
