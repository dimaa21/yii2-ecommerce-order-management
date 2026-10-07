<?php

declare(strict_types=1);

/** @var string $title */
/** @var string|null $subtitle */
/** @var string|null $actions */

use yii\helpers\Html;

$subtitle = $subtitle ?? null;
$actions = $actions ?? '';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= Html::encode($title) ?></h1>
        <?php if ($subtitle): ?>
            <p class="text-secondary mb-0"><?= Html::encode($subtitle) ?></p>
        <?php endif ?>
    </div>
    <div class="d-flex gap-2"><?= $actions ?></div>
</div>
