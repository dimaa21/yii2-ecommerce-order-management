<?php

declare(strict_types=1);

namespace app\models\traits;

use yii\helpers\Inflector;

/**
 * Builds a unique slug from the name when the form leaves slug empty.
 *
 * @property int|null $id
 * @property string|null $name
 * @property string|null $slug
 * @property bool $isNewRecord
 */
trait Sluggable
{
    protected function normalizeSlug(): void
    {
        $slug = trim((string) $this->slug);
        if ($slug === '') {
            $slug = Inflector::slug((string) $this->name);
        } else {
            $slug = Inflector::slug($slug);
        }

        if ($slug === '') {
            $slug = 'item';
        }

        $base = $slug;
        $suffix = 2;
        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        $this->slug = $slug;
    }

    private function slugExists(string $slug): bool
    {
        $query = static::find()->where(['slug' => $slug]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        return $query->exists();
    }
}
