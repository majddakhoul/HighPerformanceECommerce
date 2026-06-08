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

class RealisticDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        OrderItem::truncate();
        Invoice::truncate();
        Order::truncate();
        Product::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = [];
        for ($i = 1; $i <= 100; $i++) {
            $users[] = User::create([
                'name'     => "User $i",
                'email'    => "user{$i}@example.com",
                'password' => Hash::make('password'),
            ]);
            $users[$i - 1]->assignRole('user');
        }

        $productsData = [
            ['name' => 'Wireless Mouse', 'price' => 25.99, 'cost' => 12.50, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Mechanical Keyboard', 'price' => 79.99, 'cost' => 45.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'USB-C Hub', 'price' => 34.50, 'cost' => 18.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => '27-inch Monitor', 'price' => 299.99, 'cost' => 180.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Laptop Stand', 'price' => 49.99, 'cost' => 22.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Webcam 1080p', 'price' => 59.00, 'cost' => 30.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Noise-Canceling Headphones', 'price' => 149.99, 'cost' => 85.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Portable Bluetooth Speaker', 'price' => 39.99, 'cost' => 20.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'External SSD 1TB', 'price' => 109.99, 'cost' => 70.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Smartphone 128GB', 'price' => 699.99, 'cost' => 450.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Tablet 10.9-inch', 'price' => 449.99, 'cost' => 280.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Wireless Charger', 'price' => 19.99, 'cost' => 8.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Desk Lamp LED', 'price' => 29.99, 'cost' => 14.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Ergonomic Office Chair', 'price' => 249.99, 'cost' => 140.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Standing Desk Converter', 'price' => 179.99, 'cost' => 110.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'HDMI Cable 2m', 'price' => 12.99, 'cost' => 4.50, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Laptop Sleeve 15.6"', 'price' => 24.99, 'cost' => 10.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Power Bank 20000mAh', 'price' => 35.99, 'cost' => 18.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Wireless Earbuds', 'price' => 89.99, 'cost' => 45.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Graphic Tablet', 'price' => 59.99, 'cost' => 30.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'WiFi Router AX3000', 'price' => 129.99, 'cost' => 75.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Smartwatch', 'price' => 199.99, 'cost' => 110.00, 'stock' => 10000, 'category' => 'Wearables'],
            ['name' => 'Running Shoes', 'price' => 89.99, 'cost' => 45.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Backpack 30L', 'price' => 49.99, 'cost' => 22.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Stainless Steel Water Bottle', 'price' => 19.99, 'cost' => 7.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Yoga Mat', 'price' => 29.99, 'cost' => 12.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Resistance Bands Set', 'price' => 15.99, 'cost' => 6.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Dumbbell Set 20kg', 'price' => 69.99, 'cost' => 35.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Treadmill', 'price' => 899.99, 'cost' => 550.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Electric Toothbrush', 'price' => 39.99, 'cost' => 18.00, 'stock' => 10000, 'category' => 'Health & Beauty'],
            ['name' => 'Hair Dryer 2000W', 'price' => 34.99, 'cost' => 15.00, 'stock' => 10000, 'category' => 'Health & Beauty'],
            ['name' => 'Bluetooth Tracker', 'price' => 24.99, 'cost' => 10.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Car Phone Mount', 'price' => 14.99, 'cost' => 6.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Portable Air Pump', 'price' => 29.99, 'cost' => 14.00, 'stock' => 10000, 'category' => 'Automotive'],
            ['name' => 'LED Strip Lights 5m', 'price' => 18.99, 'cost' => 8.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Smart Plug', 'price' => 12.99, 'cost' => 5.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Instant Pot 6Qt', 'price' => 79.99, 'cost' => 45.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Coffee Maker', 'price' => 59.99, 'cost' => 28.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Air Fryer 5.5L', 'price' => 89.99, 'cost' => 50.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Scented Candle Set', 'price' => 22.99, 'cost' => 9.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Facial Cleanser', 'price' => 14.99, 'cost' => 5.00, 'stock' => 10000, 'category' => 'Health & Beauty'],
            ['name' => 'Sunscreen SPF50', 'price' => 17.99, 'cost' => 7.00, 'stock' => 10000, 'category' => 'Health & Beauty'],
            ['name' => 'Men\'s Casual Shirt', 'price' => 34.99, 'cost' => 16.00, 'stock' => 10000, 'category' => 'Clothing'],
            ['name' => 'Women\'s Crossbody Bag', 'price' => 42.99, 'cost' => 20.00, 'stock' => 10000, 'category' => 'Clothing'],
            ['name' => 'Sunglasses UV400', 'price' => 27.99, 'cost' => 11.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Leather Wallet', 'price' => 32.99, 'cost' => 14.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Desk Organizer', 'price' => 21.99, 'cost' => 9.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Whiteboard 60x90cm', 'price' => 44.99, 'cost' => 20.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Alarm Clock Digital', 'price' => 16.99, 'cost' => 6.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Travel Adapter Universal', 'price' => 27.99, 'cost' => 12.00, 'stock' => 10000, 'category' => 'Accessories'],
            ['name' => 'Gaming Mouse Pad', 'price' => 14.99, 'cost' => 6.00, 'stock' => 10000, 'category' => 'Gaming'],
            ['name' => 'Gaming Headset', 'price' => 69.99, 'cost' => 35.00, 'stock' => 10000, 'category' => 'Gaming'],
            ['name' => 'Gaming Chair', 'price' => 299.99, 'cost' => 180.00, 'stock' => 10000, 'category' => 'Gaming'],
            ['name' => 'RGB Mousepad', 'price' => 24.99, 'cost' => 12.00, 'stock' => 10000, 'category' => 'Gaming'],
            ['name' => 'Mechanical Switch Tester', 'price' => 19.99, 'cost' => 8.00, 'stock' => 10000, 'category' => 'Gaming'],
            ['name' => 'VR Headset', 'price' => 399.99, 'cost' => 250.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Drone 4K', 'price' => 499.99, 'cost' => 320.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Digital Camera', 'price' => 549.99, 'cost' => 350.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Bookshelf Speakers', 'price' => 129.99, 'cost' => 70.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Soundbar', 'price' => 179.99, 'cost' => 100.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Robot Vacuum', 'price' => 349.99, 'cost' => 200.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Air Purifier', 'price' => 199.99, 'cost' => 120.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Humidifier', 'price' => 49.99, 'cost' => 25.00, 'stock' => 10000, 'category' => 'Home & Office'],
            ['name' => 'Electric Kettle', 'price' => 29.99, 'cost' => 14.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Blender', 'price' => 39.99, 'cost' => 20.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Toaster', 'price' => 24.99, 'cost' => 12.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Microwave Oven', 'price' => 99.99, 'cost' => 60.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Rice Cooker', 'price' => 44.99, 'cost' => 22.00, 'stock' => 10000, 'category' => 'Kitchen'],
            ['name' => 'Yoga Block', 'price' => 9.99, 'cost' => 4.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Jump Rope', 'price' => 12.99, 'cost' => 5.00, 'stock' => 10000, 'category' => 'Sports'],
            ['name' => 'Tent 2-Person', 'price' => 89.99, 'cost' => 45.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Sleeping Bag', 'price' => 49.99, 'cost' => 25.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Camping Stove', 'price' => 34.99, 'cost' => 18.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Hiking Backpack 50L', 'price' => 79.99, 'cost' => 40.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Fishing Rod', 'price' => 59.99, 'cost' => 30.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Cooler Box 24L', 'price' => 39.99, 'cost' => 20.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'BBQ Grill', 'price' => 149.99, 'cost' => 80.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Beach Umbrella', 'price' => 24.99, 'cost' => 10.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Picnic Basket', 'price' => 34.99, 'cost' => 18.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Echo Dot', 'price' => 39.99, 'cost' => 20.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Smart Light Bulb', 'price' => 14.99, 'cost' => 7.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Wi-Fi Range Extender', 'price' => 49.99, 'cost' => 25.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'USB Microphone', 'price' => 59.99, 'cost' => 30.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Ring Light', 'price' => 29.99, 'cost' => 14.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Drawing Tablet', 'price' => 89.99, 'cost' => 45.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => '3D Printer', 'price' => 299.99, 'cost' => 180.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Robot Kit', 'price' => 79.99, 'cost' => 40.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Drone Racing Kit', 'price' => 199.99, 'cost' => 120.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'VR Gloves', 'price' => 149.99, 'cost' => 80.00, 'stock' => 10000, 'category' => 'Electronics'],
            ['name' => 'Electric Scooter', 'price' => 399.99, 'cost' => 250.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Hoverboard', 'price' => 249.99, 'cost' => 150.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Electric Bike', 'price' => 899.99, 'cost' => 550.00, 'stock' => 10000, 'category' => 'Outdoor'],
            ['name' => 'Fitness Tracker', 'price' => 49.99, 'cost' => 25.00, 'stock' => 10000, 'category' => 'Wearables'],
            ['name' => 'Smart Ring', 'price' => 99.99, 'cost' => 55.00, 'stock' => 10000, 'category' => 'Wearables'],
            ['name' => 'Race Condition Test - UNSAFE', 'price' => 10.00, 'cost' => 5.00, 'stock' => 20, 'category' => 'Test'],
            ['name' => 'Race Condition Test - SAFE',   'price' => 10.00, 'cost' => 5.00, 'stock' => 20, 'category' => 'Test'],
        ];

        $products = [];
        foreach ($productsData as $data) {
            $products[] = Product::create($data);
        }

        $userCount = count($users);
        $productCount = count($products);

        $days = 90;
        $startDate = now()->subDays($days)->startOfDay();

        for ($d = 0; $d < $days; $d++) {
            $date = $startDate->copy()->addDays($d);
            $ordersToday = rand(15, 40);

            for ($i = 0; $i < $ordersToday; $i++) {
                $user = $users[array_rand($users)];
                $itemCount = rand(1, 5);
                $selectedProducts = [];
                $usedKeys = [];
                for ($j = 0; $j < $itemCount; $j++) {
                    do {
                        $k = array_rand($products);
                    } while (in_array($k, $usedKeys));
                    $usedKeys[] = $k;
                    $selectedProducts[] = $products[$k];
                }

                $totalPrice = 0;
                $orderItems = [];
                foreach ($selectedProducts as $prod) {
                    $qty = rand(1, 3);
                    $totalPrice += $prod->price * $qty;
                    $orderItems[] = [
                        'product_id' => $prod->id,
                        'quantity'   => $qty,
                        'price'      => $prod->price,
                    ];
                }

                $statusRand = rand(1, 100);
                $status = 'delivered';
                if ($statusRand <= 5) {
                    $status = 'cancelled';
                } elseif ($statusRand <= 20) {
                    $status = 'confirmed';
                } elseif ($statusRand <= 30) {
                    $status = 'preparing';
                }

                $order = Order::create([
                    'user_id'      => $user->id,
                    'total_price'  => $totalPrice,
                    'status'       => $status,
                    'delivered_at' => $status === 'delivered' ? $date->copy()->addHours(rand(10, 20)) : null,
                    'sales_processed_at' => null,
                    'created_at'   => $date->copy()->addMinutes(rand(0, 1440)),
                    'updated_at'   => $date->copy()->addMinutes(rand(0, 1440)),
                ]);

                foreach ($orderItems as $oi) {
                    $oi['order_id'] = $order->id;
                    OrderItem::create($oi);
                }

                if ($status !== 'cancelled') {
                    Invoice::create([
                        'order_id'   => $order->id,
                        'user_id'    => $user->id,
                        'total'      => $totalPrice,
                        'status'     => $status === 'delivered' ? 'paid' : 'unpaid',
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                    ]);
                }
            }
        }

        Order::where('status', 'delivered')->update(['sales_processed_at' => null]);
    }
}