<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="oms-card p-4">
    <h1 class="h3"><?= Html::encode($name) ?></h1>
    <p class="text-secondary mb-0"><?= nl2br(Html::encode($message)) ?></p>
</div>
