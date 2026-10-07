<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\Rbac;
use app\exceptions\DomainException;
use app\exceptions\ValidationException;
use app\models\User;
use app\models\UserForm;
use app\services\UserService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Response;

class UserController extends WebController
{
    protected function permissions(): array
    {
        return [Rbac::USER_MANAGE];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->orderBy(['id' => SORT_ASC]),
            'pagination' => ['pageSize' => (int) Yii::$app->params['pageSize']],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate(): Response|string
    {
        $model = new UserForm(['scenario' => UserForm::SCENARIO_CREATE]);
        if ($model->load($this->request->post())) {
            try {
                (new UserService())->create($model);
                Yii::$app->session->setFlash('success', 'User created.');

                return $this->redirect(['index']);
            } catch (ValidationException $e) {
                $this->applyErrors($model, $e);
            } catch (DomainException $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $user = $this->findUser($id);
        $model = new UserForm(['scenario' => UserForm::SCENARIO_UPDATE]);
        if ($model->load($this->request->post())) {
            try {
                (new UserService())->update($user, $model);
                Yii::$app->session->setFlash('success', 'User updated.');

                return $this->redirect(['index']);
            } catch (ValidationException $e) {
                $this->applyErrors($model, $e);
            } catch (DomainException $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        } else {
            $model->username = $user->username;
            $model->email = $user->email;
            $model->status = (int) $user->status;
            $model->role = $user->getRoleName() ?? Rbac::ROLE_MANAGER;
            $model->userId = (int) $user->id;
        }

        return $this->render('update', [
            'model' => $model,
            'user' => $user,
        ]);
    }

    private function findUser(int $id): User
    {
        /** @var User $user */
        $user = $this->findModel(User::class, $id);

        return $user;
    }

    private function applyErrors(UserForm $model, ValidationException $exception): void
    {
        foreach ($exception->getErrors() as $attribute => $messages) {
            foreach ($messages as $message) {
                $model->addError($attribute, $message);
            }
        }
    }
}
