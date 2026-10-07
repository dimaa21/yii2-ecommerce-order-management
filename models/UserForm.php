<?php

declare(strict_types=1);

namespace app\models;

use app\components\Rbac;
use yii\base\Model;

/**
 * Input model for staff accounts.
 * password_hash is never a form field, so it cannot be mass-assigned.
 */
class UserForm extends Model
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $role = Rbac::ROLE_MANAGER;
    public int $status = User::STATUS_ACTIVE;
    public ?int $userId = null;

    public function rules(): array
    {
        return [
            [['username', 'email', 'role', 'status'], 'required'],
            [['username', 'email', 'password'], 'trim'],
            [['username'], 'string', 'min' => 3, 'max' => 64],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['password'], 'required', 'on' => self::SCENARIO_CREATE],
            [['password'], 'string', 'min' => 8, 'skipOnEmpty' => true],
            [['role'], 'in', 'range' => [Rbac::ROLE_ADMIN, Rbac::ROLE_MANAGER]],
            [['status'], 'in', 'range' => [User::STATUS_INACTIVE, User::STATUS_ACTIVE]],
            [['status'], 'filter', 'filter' => 'intval'],
            [['username'], 'validateUsername'],
            [['email'], 'validateEmail'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'role' => 'Role',
            'status' => 'Status',
        ];
    }

    public function validateUsername(string $attribute): void
    {
        $query = User::find()->where(['username' => $this->username]);
        if ($this->userId !== null) {
            $query->andWhere(['<>', 'id', $this->userId]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'This username is already taken.');
        }
    }

    public function validateEmail(string $attribute): void
    {
        $query = User::find()->where(['email' => strtolower($this->email)]);
        if ($this->userId !== null) {
            $query->andWhere(['<>', 'id', $this->userId]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'This email is already taken.');
        }
    }
}
