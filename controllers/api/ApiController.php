<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\Rbac;
use app\exceptions\DomainException;
use app\exceptions\EntityNotFoundException;
use app\exceptions\InsufficientStockException;
use app\exceptions\ValidationException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use app\models\User;
use Throwable;

class ApiController extends Controller
{
    /**
     * @return list<string>
     */
    protected function permissions(): array
    {
        return [];
    }

    public function behaviors(): array
    {
        return [
            'authenticator' => [
                'class' => CompositeAuth::class,
                'authMethods' => [
                    [
                        'class' => HttpBasicAuth::class,
                        'auth' => static function (string $username, string $password): ?User {
                            $user = User::findByUsername($username);
                            if ($user === null || $user->status !== User::STATUS_ACTIVE || !$user->validatePassword($password)) {
                                return null;
                            }

                            return $user;
                        },
                    ],
                    HttpBearerAuth::class,
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => $this->permissions(),
                    ],
                ],
                'denyCallback' => static function (): void {
                    if (Yii::$app->user->isGuest) {
                        throw new UnauthorizedHttpException('Authentication is required.');
                    }
                    throw new ForbiddenHttpException('You are not allowed to perform this action.');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'],
                    'view' => ['GET', 'HEAD'],
                    'create' => ['POST'],
                    'update' => ['PUT', 'PATCH'],
                    'delete' => ['DELETE'],
                ],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        Yii::$app->request->enableCsrfValidation = false;
        Yii::$app->response->format = Response::FORMAT_JSON;

        return parent::beforeAction($action);
    }

    public function runAction($id, $params = [])
    {
        try {
            $result = parent::runAction($id, $params);
            if ($result instanceof Response) {
                return $result;
            }

            return $this->asJson([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return $this->renderException($e);
        }
    }

    /**
     * @return list<string>
     */
    protected function expand(): array
    {
        $expand = Yii::$app->request->get('expand', '');
        if (!is_string($expand) || $expand === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $expand))));
    }

    /**
     * Page query param is 1-based. Yii's pager is 0-based internally.
     *
     * @return array{items: list<array<string, mixed>>, meta: array<string, int>}
     */
    protected function paginate(ActiveDataProvider $provider): array
    {
        $pagination = $provider->getPagination();
        if ($pagination === false) {
            throw new \RuntimeException('Pagination is required.');
        }

        $page = Yii::$app->request->get('page');
        if ($page !== null) {
            $pagination->setPage(max(0, (int) $page - 1));
        }

        $perPage = Yii::$app->request->get('per_page');
        if ($perPage !== null) {
            $pagination->setPageSize((int) $perPage);
        }

        $expand = $this->expand();
        $items = [];
        foreach ($provider->getModels() as $model) {
            $items[] = $model->toArray([], $expand);
        }

        return [
            'items' => $items,
            'meta' => [
                'total' => (int) $provider->getTotalCount(),
                'page' => $pagination->getPage() + 1,
                'per_page' => $pagination->getPageSize(),
                'page_count' => $pagination->getPageCount(),
            ],
        ];
    }

    protected function renderException(Throwable $e): Response
    {
        [$status, $message, $errors] = $this->normalize($e);
        if ($status >= 500) {
            Yii::error($e, __METHOD__);
        }

        Yii::$app->response->statusCode = $status;
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return $this->asJson($payload);
    }

    /**
     * @return array{0: int, 1: string, 2: array<string, list<string>>}
     */
    private function normalize(Throwable $e): array
    {
        if ($e instanceof ValidationException) {
            return [422, $e->getMessage(), $e->getErrors()];
        }
        if ($e instanceof EntityNotFoundException) {
            return [404, $e->getMessage(), []];
        }
        if ($e instanceof InsufficientStockException) {
            return [422, $e->getMessage(), []];
        }
        if ($e instanceof DomainException) {
            return [422, $e->getMessage(), []];
        }
        if ($e instanceof HttpException) {
            return [$e->statusCode, $e->getMessage(), []];
        }

        $message = YII_DEBUG ? $e->getMessage() : 'Internal server error.';

        return [500, $message, []];
    }
}
