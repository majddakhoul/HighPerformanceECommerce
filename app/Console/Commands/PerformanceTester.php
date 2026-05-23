<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Tymon\JWTAuth\Facades\JWTAuth;

class PerformanceTester extends Command
{
    protected $signature = 'test:performance';
    protected $description = 'Real performance test (no HTTP, fully internal Laravel testing)';

    protected $token;
    protected $user;
    protected $product;

    public function handle()
    {
        $this->info(' Starting REAL Performance Tests...');

        $this->cleanup();
        $this->setup();

        $this->raceConditionTest();
        $this->rateLimitTest();
        $this->queueTest();

        $this->info("\n DONE - Real results generated successfully.");
    }

    // ================= CLEANUP =================

    private function cleanup()
    {
        $this->info(" Cleaning database safely...");

        OrderItem::query()->delete();
        Order::query()->delete();
        Product::where('name', 'Test Product')->delete();
        User::where('email', 'tester@test.com')->delete();
    }

    // ================= SETUP =================

    private function setup()
    {
        $this->info("\n Setting up test data...");

        $this->user = User::create([
            'name' => 'Tester',
            'email' => 'tester@test.com',
            'password' => bcrypt('123456')
        ]);

        $this->user->assignRole('user');

        $this->product = Product::create([
            'name' => 'Test Product',
            'price' => 100,
            'stock' => 5,
            'category' => 'test'
        ]);

        $this->token = JWTAuth::fromUser($this->user);

        $this->info(" Setup completed.");
    }

    // ================= INTERNAL REQUEST =================

    private function sendRequest($url, $data)
    {
        $request = request()->create($url, 'POST', $data);

        $request->headers->set('Authorization', 'Bearer ' . $this->token);

        $response = app()->handle($request);

        return $response->getStatusCode();
    }

    // ================= RACE CONDITION =================

    private function raceConditionTest()
    {
        $this->info("\n === RACE CONDITION TEST ===");

        $this->product->update(['stock' => 40]);

        $unsafe = [];

        for ($i = 0; $i < 20; $i++) {
            $unsafe[] = $this->sendRequest('/api/orders/test/no-limit/unsafe', [
                'user_id' => $this->user->id,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1]
                ]
            ]);
        }

        $unsafeSuccess = collect($unsafe)->filter(fn($s) => $s == 201)->count();

        $this->product->refresh();

        $this->product->update(['stock' => 40]);

        $safe = [];

        for ($i = 0; $i < 20; $i++) {
            $safe[] = $this->sendRequest('/api/orders/test/no-limit/safe', [
                'user_id' => $this->user->id,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1]
                ]
            ]);
        }

        $safeSuccess = collect($safe)->filter(fn($s) => $s == 201)->count();

        $this->product->refresh();

        $this->table(
            ['Scenario', 'Success Orders', 'Final Stock'],
            [
                ['UNSAFE', $unsafeSuccess, $this->product->stock],
                ['SAFE', $safeSuccess, $this->product->stock],
            ]
        );
    }

    // ================= RATE LIMIT =================

    private function rateLimitTest()
    {
        $this->info("\n🚦 === RATE LIMIT TEST ===");

        $results = [];

        for ($i = 0; $i < 15; $i++) {
            $results[] = $this->sendRequest('/api/orders/test/with-limit/unsafe', [
                'user_id' => $this->user->id,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1]
                ]
            ]);
        }

        $success = collect($results)->filter(fn($s) => $s == 201)->count();
        $blocked = collect($results)->filter(fn($s) => $s == 429)->count();

        $this->table(
            ['Success (201)', 'Blocked (429)'],
            [[$success, $blocked]]
        );
    }

    // ================= QUEUE TEST =================

    private function queueTest()
    {
        $this->info("\n === QUEUE PERFORMANCE TEST ===");

        $start = microtime(true);

        $sync = $this->sendRequest('/api/orders/test/no-limit/safe', [
            'user_id' => $this->user->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1]
            ]
        ]);

        $syncTime = round((microtime(true) - $start) * 1000, 2);

        $start = microtime(true);

        $async = $this->sendRequest('/api/orders/queue/no-limit/safe', [
            'user_id' => $this->user->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1]
            ]
        ]);

        $asyncTime = round((microtime(true) - $start) * 1000, 2);

        $this->table(
            ['Type', 'Time (ms)', 'Status'],
            [
                ['SYNC', $syncTime, $sync],
                ['QUEUE', $asyncTime, $async],
            ]
        );

        if ($asyncTime < $syncTime) {
            $this->info(" Queue is faster by " . round($syncTime - $asyncTime, 2) . " ms");
        }
    }
}
