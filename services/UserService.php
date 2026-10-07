<?php

declare(strict_types=1);

namespace app\services;

use app\components\Rbac;
use app\exceptions\DomainException;
use app\exceptions\ValidationException;
use app\models\User;
use app\models\UserForm;
use Throwable;
use Yii;

/**
 * Creating a staff account is two writes: the user row and the RBAC assignment.
 * They share one transaction so a failed role assignment does not leave a login without a role.
 */
final class UserService
{
    public function create(UserForm $form): User
    {
        if (!$form->validate()) {
            throw new ValidationException($form->getErrors());
        }

        $user = new User();
        $user->username = $form->username;
        $user->email = $form->email;
        $user->status = (int) $form->status;
        $user->setPassword($form->password);
        $user->generateAuthKey();
        $user->generateAccessToken();

        return $this->persist($user, $form->role);
    }

    public function update(User $user, UserForm $form): User
    {
        $form->userId = (int) $user->id;
        if (!$form->validate()) {
            throw new ValidationException($form->getErrors());
        }

        $this->guardLastAdmin($user, $form);

        $user->username = $form->username;
        $user->email = $form->email;
        $user->status = (int) $form->status;
        if ($form->password !== '') {
            $user->setPassword($form->password);
            $user->generateAuthKey();
        }

        return $this->persist($user, $form->role, true);
    }

    private function persist(User $user, string $roleName, bool $replaceRole = false): User
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($roleName);
        if ($role === null) {
            throw new DomainException('Unknown role.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$user->save()) {
                throw new ValidationException($user->getErrors());
            }

            if ($replaceRole) {
                $auth->revokeAll($user->id);
            }
            $auth->assign($role, $user->id);
            $transaction->commit();

            return $user;
        } catch (Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
    }

    private function guardLastAdmin(User $user, UserForm $form): void
    {
        $actorId = (int) Yii::$app->user->id;
        if ($actorId === (int) $user->id && $form->role !== Rbac::ROLE_ADMIN) {
            throw new DomainException('You cannot remove your own admin role.');
        }
        if ($actorId === (int) $user->id && (int) $form->status !== User::STATUS_ACTIVE) {
            throw new DomainException('You cannot deactivate your own account.');
        }

        if ($user->getRoleName() === Rbac::ROLE_ADMIN && $form->role !== Rbac::ROLE_ADMIN) {
            $admins = Yii::$app->authManager->getUserIdsByRole(Rbac::ROLE_ADMIN);
            if (count($admins) <= 1) {
                throw new DomainException('At least one administrator must remain.');
            }
        }
    }
}
