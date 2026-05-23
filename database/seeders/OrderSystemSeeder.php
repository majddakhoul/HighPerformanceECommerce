<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrderSystemSeeder extends Seeder
{
    public function run(): void
    {
        // تعطيل قيود المفاتيح الخارجية وتفريغ الجداول
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        OrderItem::truncate();
        Invoice::truncate();
        Order::truncate();
        Product::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // -------------------- 1. إنشاء 10 مستخدمين --------------------
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $users[] = User::create([
                'name'     => "User $i",
                'email'    => "user{$i}@example.com",
                'password' => Hash::make('password'),
            ]);
        }

        // -------------------- 2. إنشاء 50 منتجًا واقعيًا --------------------
        $productData = [
            ['name' => 'Wireless Mouse',         'price' => 25.99,  'cost' => 12.50,  'stock' => 150],
            ['name' => 'Mechanical Keyboard',    'price' => 79.99,  'cost' => 45.00,  'stock' => 80],
            ['name' => 'USB-C Hub',              'price' => 34.50,  'cost' => 18.00,  'stock' => 200],
            ['name' => '27-inch Monitor',        'price' => 299.99, 'cost' => 180.00, 'stock' => 30],
            ['name' => 'Laptop Stand',           'price' => 49.99,  'cost' => 22.00,  'stock' => 120],
            ['name' => 'Webcam 1080p',           'price' => 59.00,  'cost' => 30.00,  'stock' => 60],
            ['name' => 'Noise-Canceling Headphones', 'price' => 149.99, 'cost' => 85.00, 'stock' => 45],
            ['name' => 'Portable Bluetooth Speaker', 'price' => 39.99,  'cost' => 20.00,  'stock' => 90],
            ['name' => 'External SSD 1TB',       'price' => 109.99, 'cost' => 70.00,  'stock' => 55],
            ['name' => 'Smartphone 128GB',       'price' => 699.99, 'cost' => 450.00, 'stock' => 25],
            ['name' => 'Tablet 10.9-inch',       'price' => 449.99, 'cost' => 280.00, 'stock' => 35],
            ['name' => 'Wireless Charger',       'price' => 19.99,  'cost' => 8.00,   'stock' => 300],
            ['name' => 'Desk Lamp LED',          'price' => 29.99,  'cost' => 14.00,  'stock' => 100],
            ['name' => 'Ergonomic Office Chair', 'price' => 249.99, 'cost' => 140.00, 'stock' => 20],
            ['name' => 'Standing Desk Converter', 'price' => 179.99, 'cost' => 110.00, 'stock' => 15],
            ['name' => 'HDMI Cable 2m',          'price' => 12.99,  'cost' => 4.50,   'stock' => 500],
            ['name' => 'Laptop Sleeve 15.6"',    'price' => 24.99,  'cost' => 10.00,  'stock' => 80],
            ['name' => 'Power Bank 20000mAh',    'price' => 35.99,  'cost' => 18.00,  'stock' => 130],
            ['name' => 'Wireless Earbuds',       'price' => 89.99,  'cost' => 45.00,  'stock' => 70],
            ['name' => 'Graphic Tablet',         'price' => 59.99,  'cost' => 30.00,  'stock' => 40],
            ['name' => 'WiFi Router AX3000',     'price' => 129.99, 'cost' => 75.00,  'stock' => 50],
            ['name' => 'Smartwatch',             'price' => 199.99, 'cost' => 110.00, 'stock' => 30],
            ['name' => 'Running Shoes',          'price' => 89.99,  'cost' => 45.00,  'stock' => 60],
            ['name' => 'Backpack 30L',           'price' => 49.99,  'cost' => 22.00,  'stock' => 90],
            ['name' => 'Stainless Steel Water Bottle', 'price' => 19.99, 'cost' => 7.00, 'stock' => 200],
            ['name' => 'Yoga Mat',               'price' => 29.99,  'cost' => 12.00,  'stock' => 110],
            ['name' => 'Resistance Bands Set',   'price' => 15.99,  'cost' => 6.00,   'stock' => 180],
            ['name' => 'Dumbbell Set 20kg',      'price' => 69.99,  'cost' => 35.00,  'stock' => 40],
            ['name' => 'Treadmill',              'price' => 899.99, 'cost' => 550.00, 'stock' => 5],
            ['name' => 'Electric Toothbrush',    'price' => 39.99,  'cost' => 18.00,  'stock' => 75],
            ['name' => 'Hair Dryer 2000W',       'price' => 34.99,  'cost' => 15.00,  'stock' => 60],
            ['name' => 'Bluetooth Tracker',      'price' => 24.99,  'cost' => 10.00,  'stock' => 170],
            ['name' => 'Car Phone Mount',        'price' => 14.99,  'cost' => 6.00,   'stock' => 250],
            ['name' => 'Portable Air Pump',      'price' => 29.99,  'cost' => 14.00,  'stock' => 30],
            ['name' => 'LED Strip Lights 5m',    'price' => 18.99,  'cost' => 8.00,   'stock' => 140],
            ['name' => 'Smart Plug',             'price' => 12.99,  'cost' => 5.00,   'stock' => 300],
            ['name' => 'Instant Pot 6Qt',        'price' => 79.99,  'cost' => 45.00,  'stock' => 40],
            ['name' => 'Coffee Maker',           'price' => 59.99,  'cost' => 28.00,  'stock' => 50],
            ['name' => 'Air Fryer 5.5L',         'price' => 89.99,  'cost' => 50.00,  'stock' => 30],
            ['name' => 'Scented Candle Set',     'price' => 22.99,  'cost' => 9.00,   'stock' => 120],
            ['name' => 'Facial Cleanser',        'price' => 14.99,  'cost' => 5.00,   'stock' => 200],
            ['name' => 'Sunscreen SPF50',        'price' => 17.99,  'cost' => 7.00,   'stock' => 180],
            ['name' => 'Men\'s Casual Shirt',     'price' => 34.99,  'cost' => 16.00,  'stock' => 70],
            ['name' => 'Women\'s Crossbody Bag',  'price' => 42.99,  'cost' => 20.00,  'stock' => 55],
            ['name' => 'Sunglasses UV400',       'price' => 27.99,  'cost' => 11.00,  'stock' => 90],
            ['name' => 'Leather Wallet',         'price' => 32.99,  'cost' => 14.00,  'stock' => 100],
            ['name' => 'Desk Organizer',         'price' => 21.99,  'cost' => 9.00,   'stock' => 150],
            ['name' => 'Whiteboard 60x90cm',     'price' => 44.99,  'cost' => 20.00,  'stock' => 25],
            ['name' => 'Alarm Clock Digital',    'price' => 16.99,  'cost' => 6.00,   'stock' => 130],
            ['name' => 'Travel Adapter Universal', 'price' => 27.99,  'cost' => 12.00,  'stock' => 85],
        ];

        $products = [];
        foreach ($productData as $data) {
            $products[] = Product::create($data);
        }

        // -------------------- دوال مساعدة --------------------
        $getRandomUser = fn() => $users[array_rand($users)];
        $getRandomProducts = function ($maxItems = 4) use ($products) {
            $selected = [];
            $count = rand(1, $maxItems);
            $keys = array_rand($products, $count);
            $keys = is_array($keys) ? $keys : [$keys];
            foreach ($keys as $k) {
                $selected[] = $products[$k];
            }
            return $selected;
        };

        // -------------------- 3. إنشاء طلبات الأمس (40 طلب) --------------------
        $yesterday = now()->subDay();
        $orderCountYesterday = 40;
        for ($i = 0; $i < $orderCountYesterday; $i++) {
            $user = $getRandomUser();
            $selectedProducts = $getRandomProducts(4);

            $order = Order::create([
                'user_id'      => $user->id,
                'total_price'  => 0,
                'status'       => 'pending',
                'delivered_at' => null,
                'created_at'   => $yesterday->copy()->addMinutes(rand(0, 1440)),
                'updated_at'   => $yesterday->copy()->addMinutes(rand(0, 1440)),
            ]);

            $totalPrice = 0;
            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 5);
                $price = $product->price;
                $totalPrice += $price * $quantity;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'price'      => $price,
                ]);
            }

            $randStatus = rand(1, 10);
            $status = match (true) {
                $randStatus <= 3 => 'cancelled',
                $randStatus <= 6 => 'confirmed',
                $randStatus <= 8 => 'preparing',
                default         => 'delivered',
            };

            $order->total_price = $totalPrice;
            $order->status = $status;

            if ($status === 'delivered') {
                $order->delivered_at = $yesterday->copy()->addHours(rand(10, 22));
            }

            $order->save();

            if ($status !== 'cancelled') {
                Invoice::create([
                    'order_id' => $order->id,
                    'user_id'  => $user->id,
                    'total'    => $totalPrice,
                    'status'   => $status === 'delivered' ? 'paid' : 'unpaid',
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
        }

        // -------------------- 4. إنشاء طلبات اليوم (30 طلب) --------------------
        $today = now();
        $orderCountToday = 30;
        for ($i = 0; $i < $orderCountToday; $i++) {
            $user = $getRandomUser();
            $selectedProducts = $getRandomProducts(4);

            $order = Order::create([
                'user_id'      => $user->id,
                'total_price'  => 0,
                'status'       => 'pending',
                'delivered_at' => null,
                'created_at'   => $today->copy()->addMinutes(rand(0, 720)),  // صباح اليوم
                'updated_at'   => $today->copy()->addMinutes(rand(0, 720)),
            ]);

            $totalPrice = 0;
            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 4);
                $price = $product->price;
                $totalPrice += $price * $quantity;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'price'      => $price,
                ]);
            }

            $randStatus = rand(1, 10);
            $status = match (true) {
                $randStatus <= 2 => 'cancelled',
                $randStatus <= 5 => 'confirmed',
                $randStatus <= 8 => 'preparing',
                default         => 'delivered',
            };

            $order->total_price = $totalPrice;
            $order->status = $status;
            if ($status === 'delivered') {
                $order->delivered_at = $today->copy()->addHours(rand(8, 14));
            }
            $order->save();

            if ($status !== 'cancelled') {
                Invoice::create([
                    'order_id' => $order->id,
                    'user_id'  => $user->id,
                    'total'    => $totalPrice,
                    'status'   => $status === 'delivered' ? 'paid' : 'unpaid',
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
        }
    }
}
