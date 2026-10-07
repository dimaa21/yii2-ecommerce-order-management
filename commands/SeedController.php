<?php

declare(strict_types=1);

namespace app\commands;

use app\components\Rbac;
use app\models\Category;
use app\models\Customer;
use app\models\Order;
use app\models\OrderItem;
use app\models\Product;
use app\models\User;
use app\services\OrderService;
use app\exceptions\ValidationException;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Loads a fixed demo catalog so the admin and the API have something to show.
 */
class SeedController extends Controller
{
    public int $force = 0;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['force']);
    }

    public function actionDemo(): int
    {
        if (Yii::$app->authManager->getRole(Rbac::ROLE_ADMIN) === null) {
            $this->stderr("RBAC roles are missing. Run: php yii migrate --interactive=0\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$this->force && User::find()->exists()) {
            $this->stdout("Demo data already exists. Run php yii seed/demo --force=1 to replace it.\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($this->force) {
                $this->purge();
            }

            $admin = $this->user('admin', 'admin@example.com', 'admin12345', Rbac::ROLE_ADMIN);
            $manager = $this->user('manager', 'manager@example.com', 'manager12345', Rbac::ROLE_MANAGER);

            $categories = $this->categories();
            $products = $this->products($categories);
            $customers = $this->customers();
            $this->orders($products, $customers);

            $transaction->commit();
        } catch (Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            $details = $e instanceof ValidationException ? ' ' . json_encode($e->getErrors()) : '';
            $this->stderr($e->getMessage() . $details . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Demo data is ready.\n\n", Console::FG_GREEN);
        $this->stdout("Admin    admin / admin12345\n");
        $this->stdout("Manager  manager / manager12345\n\n");
        $this->stdout("Admin token    {$admin->access_token}\n");
        $this->stdout("Manager token  {$manager->access_token}\n");

        return ExitCode::OK;
    }

    private function purge(): void
    {
        OrderItem::deleteAll();
        Order::deleteAll();
        Product::deleteAll();
        Category::deleteAll();
        Customer::deleteAll();
        Yii::$app->db->createCommand()->delete('{{%auth_assignment}}', '1=1')->execute();
        User::deleteAll();
    }

    private function user(string $username, string $email, string $password, string $roleName): User
    {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->generateAccessToken();
        if (!$user->save()) {
            throw new \RuntimeException('Failed to save user ' . $username . ': ' . json_encode($user->getErrors()));
        }

        $role = Yii::$app->authManager->getRole($roleName);
        Yii::$app->authManager->assign($role, $user->id);

        return $user;
    }

    /**
     * @return array<string, Category>
     */
    private function categories(): array
    {
        $rows = [
            'phones' => ['Phones', 'Smartphones.'],
            'laptops' => ['Laptops', 'Portable computers.'],
            'audio' => ['Audio', 'Headphones and speakers.'],
            'accessories' => ['Accessories', 'Cables, cases and chargers.'],
            'tablets' => ['Tablets', 'Tablets and stylus pens.'],
        ];

        $categories = [];
        foreach ($rows as $slug => [$name, $description]) {
            $category = new Category();
            $category->name = $name;
            $category->slug = $slug;
            $category->description = $description;
            $category->status = Category::STATUS_ACTIVE;
            if (!$category->save()) {
                throw new \RuntimeException('Failed to save category ' . $name);
            }
            $categories[$slug] = $category;
        }

        return $categories;
    }

    /**
     * @param array<string, Category> $categories
     * @return array<string, Product>
     */
    private function products(array $categories): array
    {
        $rows = [
            ['phones', 'Nova X1', 'SKU-PH-001', '699.00', 25, Product::STATUS_ACTIVE],
            ['phones', 'Nova X1 Pro', 'SKU-PH-002', '899.00', 15, Product::STATUS_ACTIVE],
            ['phones', 'Pixel Mini', 'SKU-PH-003', '449.00', 30, Product::STATUS_ACTIVE],
            ['phones', 'Atlas Fold', 'SKU-PH-004', '1299.00', 8, Product::STATUS_INACTIVE],
            ['laptops', 'AeroBook 14', 'SKU-LP-001', '1199.00', 12, Product::STATUS_ACTIVE],
            ['laptops', 'AeroBook 16', 'SKU-LP-002', '1599.00', 10, Product::STATUS_ACTIVE],
            ['laptops', 'WorkMate 13', 'SKU-LP-003', '899.00', 18, Product::STATUS_ACTIVE],
            ['laptops', 'Studio Pro 15', 'SKU-LP-004', '2199.00', 6, Product::STATUS_ACTIVE],
            ['audio', 'Pulse Buds', 'SKU-AU-001', '79.00', 40, Product::STATUS_ACTIVE],
            ['audio', 'Pulse Buds Pro', 'SKU-AU-002', '149.00', 22, Product::STATUS_ACTIVE],
            ['audio', 'Home Speaker', 'SKU-AU-003', '199.00', 16, Product::STATUS_ACTIVE],
            ['audio', 'Studio Headphones', 'SKU-AU-004', '249.00', 11, Product::STATUS_ACTIVE],
            ['accessories', 'USB-C Cable', 'SKU-AC-001', '12.00', 6, Product::STATUS_ACTIVE],
            ['accessories', 'Phone Case', 'SKU-AC-002', '19.00', 5, Product::STATUS_ACTIVE],
            ['accessories', 'Screen Protector', 'SKU-AC-003', '9.50', 5, Product::STATUS_ACTIVE],
            ['accessories', '65W Charger', 'SKU-AC-004', '39.00', 20, Product::STATUS_ACTIVE],
            ['tablets', 'Slate 10', 'SKU-TB-001', '399.00', 14, Product::STATUS_ACTIVE],
            ['tablets', 'Slate 11 Pro', 'SKU-TB-002', '649.00', 9, Product::STATUS_ACTIVE],
            ['tablets', 'Slate Mini', 'SKU-TB-003', '329.00', 17, Product::STATUS_ACTIVE],
            ['tablets', 'Pen Stylus', 'SKU-TB-004', '89.00', 4, Product::STATUS_ACTIVE],
        ];

        $products = [];
        foreach ($rows as [$category, $name, $sku, $price, $stock, $status]) {
            $product = new Product();
            $product->category_id = $categories[$category]->id;
            $product->name = $name;
            $product->sku = $sku;
            $product->price = $price;
            $product->stock = $stock;
            $product->status = $status;
            $product->description = $name . ' in the ' . $categories[$category]->name . ' catalog.';
            if (!$product->save()) {
                throw new \RuntimeException('Failed to save product ' . $sku . ': ' . json_encode($product->getErrors()));
            }
            $products[$sku] = $product;
        }

        return $products;
    }

    /**
     * @return array<string, Customer>
     */
    private function customers(): array
    {
        $rows = [
            ['John Smith', 'john.smith@example.com', '+1 202 555 0101'],
            ['Mary Johnson', 'mary.johnson@example.com', '+1 202 555 0102'],
            ['Alex Brown', 'alex.brown@example.com', '+1 202 555 0103'],
            ['Sofia Davis', 'sofia.davis@example.com', '+1 202 555 0104'],
            ['Liam Wilson', 'liam.wilson@example.com', '+1 202 555 0105'],
            ['Emma Taylor', 'emma.taylor@example.com', null],
            ['Noah Anderson', 'noah.anderson@example.com', '+1 202 555 0107'],
            ['Olivia Thomas', 'olivia.thomas@example.com', '+1 202 555 0108'],
            ['Ethan Moore', 'ethan.moore@example.com', '+1 202 555 0109'],
            ['Ava Martin', 'ava.martin@example.com', '+1 202 555 0110'],
        ];

        $customers = [];
        foreach ($rows as [$name, $email, $phone]) {
            $customer = new Customer();
            $customer->name = $name;
            $customer->email = $email;
            $customer->phone = $phone;
            if (!$customer->save()) {
                throw new \RuntimeException('Failed to save customer ' . $email);
            }
            $customers[$email] = $customer;
        }

        return $customers;
    }

    /**
     * @param array<string, Product> $products
     * @param array<string, Customer> $customers
     */
    private function orders(array $products, array $customers): void
    {
        $today = time();
        $month = strtotime(date('Y-m-01 09:30:00'));
        $older = strtotime('-45 days 11:00:00');
        $service = new OrderService();

        $plans = [
            ['john.smith@example.com', ['SKU-PH-001' => 1, 'SKU-AU-001' => 1], Order::STATUS_PROCESSING, Order::PAYMENT_PAID, $today],
            ['john.smith@example.com', ['SKU-AC-001' => 2], Order::STATUS_NEW, Order::PAYMENT_PENDING, $today],
            ['mary.johnson@example.com', ['SKU-AC-002' => 1], Order::STATUS_COMPLETED, Order::PAYMENT_PAID, $today],
            ['mary.johnson@example.com', ['SKU-LP-001' => 1], Order::STATUS_PROCESSING, Order::PAYMENT_PENDING, $month],
            ['alex.brown@example.com', ['SKU-AU-004' => 1, 'SKU-AC-004' => 1], Order::STATUS_COMPLETED, Order::PAYMENT_PAID, $month],
            ['alex.brown@example.com', ['SKU-TB-001' => 1], Order::STATUS_NEW, Order::PAYMENT_FAILED, $month],
            ['sofia.davis@example.com', ['SKU-PH-003' => 1], Order::STATUS_CANCELLED, Order::PAYMENT_PENDING, $month],
            ['sofia.davis@example.com', ['SKU-AU-003' => 1], Order::STATUS_COMPLETED, Order::PAYMENT_PAID, $older],
            ['liam.wilson@example.com', ['SKU-LP-002' => 1], Order::STATUS_PROCESSING, Order::PAYMENT_PAID, $older],
            ['emma.taylor@example.com', ['SKU-AU-002' => 1], Order::STATUS_NEW, Order::PAYMENT_PENDING, $today],
            ['noah.anderson@example.com', ['SKU-LP-004' => 2], Order::STATUS_COMPLETED, Order::PAYMENT_REFUNDED, $month],
            ['olivia.thomas@example.com', ['SKU-TB-002' => 1], Order::STATUS_NEW, Order::PAYMENT_PENDING, $month],
            ['ethan.moore@example.com', ['SKU-TB-003' => 1], Order::STATUS_CANCELLED, Order::PAYMENT_FAILED, $older],
            ['ava.martin@example.com', ['SKU-PH-002' => 1], Order::STATUS_COMPLETED, Order::PAYMENT_PAID, $older],
            ['john.smith@example.com', ['SKU-AU-001' => 2, 'SKU-AC-004' => 1], Order::STATUS_PROCESSING, Order::PAYMENT_PAID, $today],
        ];

        $created = [];
        foreach ($plans as [$email, $quantities, $status, $payment, $timestamp]) {
            $items = [];
            foreach ($quantities as $sku => $quantity) {
                $items[] = [
                    'product_id' => (int) $products[$sku]->id,
                    'quantity' => $quantity,
                ];
            }
            $order = $service->create((int) $customers[$email]->id, $items);
            $created[] = [$order->id, $status, $payment, $timestamp];
        }

        foreach ($created as [$id, $status, $payment, $timestamp]) {
            $statusChange = $status === Order::STATUS_NEW ? null : $status;
            $paymentChange = $payment === Order::PAYMENT_PENDING ? null : $payment;
            if ($statusChange !== null || $paymentChange !== null) {
                $service->update((int) $id, $statusChange, $paymentChange);
            }
            Order::updateAll([
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], ['id' => $id]);
        }
    }
}
