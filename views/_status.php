<?php

declare(strict_types=1);

/** @var string $status */

use yii\helpers\Html;

$map = [
    'active' => 'success',
    'inactive' => 'secondary',
    'new' => 'primary',
    'processing' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger',
    'pending' => 'warning',
    'paid' => 'success',
    'failed' => 'danger',
    'refunded' => 'secondary',
];
$tone = $map[$status] ?? 'secondary';

echo Html::tag('span', Html::encode(ucfirst((string) $status)), [
    'class' => 'badge rounded-pill text-bg-' . $tone,
]);
