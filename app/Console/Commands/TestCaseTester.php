<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestCaseTester extends Command
{
    protected $signature = 'test:case';
    protected $description = 'Run a single performance test scenario (like k6) interactively';

    protected $token;
    protected $user;
    protected $products = [];

    public function handle()
    {
        $this->info('Welcome to Case-by-Case Performance Tester');
        $this->setupUserAndToken();

        $scenarios = [
            '1' => ['name' => 'Race Condition - Unsafe',          'method' => 'raceUnsafe'],
            '2' => ['name' => 'Race Condition - Safe',            'method' => 'raceSafe'],
            '3' => ['name' => 'Rate Limiting (with-limit)',       'method' => 'rateLimit'],
            '4' => ['name' => 'Async Queue - Unsafe',             'method' => 'queueUnsafe'],
            '5' => ['name' => 'Async Queue - Safe',               'method' => 'queueSafe'],
            '6' => ['name' => 'Queue with Rate Limiting',         'method' => 'queueLimit'],
        ];

        $count = 50;

        while (true) {
            $this->line('');
            $this->info('Available Scenarios:');
            foreach ($scenarios as $key => $s) {
                $this->line("  [{$key}] {$s['name']}");
            }
            $this->line('  [q] Quit');
            $choice = $this->ask('Choose a scenario number (or q to quit)');

            if ($choice === 'q' || $choice === 'quit') {
                $this->info('Exiting.');
                break;
            }

            if (!isset($scenarios[$choice])) {
                $this->error('Invalid choice, please try again.');
                continue;
            }

            $this->cleanupOrdersOnly();

            $method = $scenarios[$choice]['method'];
            $this->callScenario($method, $count);

            $this->info("\nScenario finished. You may choose another one or quit.\n");
        }
    }

    private function setupUserAndToken()
    {
        $this->user = User::firstOrCreate(
            ['email' => 'tester@case.test'],
            [
                'name' => 'Case Tester',
                'password' => bcrypt('123456')
            ]
        );
        $this->user->assignRole('user');
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function cleanupOrdersOnly()
    {
        OrderItem::query()->delete();
        Order::query()->delete();
    }

    private function createProduct(string $suffix, int $stock): Product
    {
        return Product::create([
            'name' => "Case Product {$suffix}",
            'price' => 100,
            'stock' => $stock,
            'category' => 'test'
        ]);
    }

    private function sendRequest(string $url, array $data): array
    {
        $request = request()->create($url, 'POST', $data);
        $request->headers->set('Authorization', 'Bearer ' . $this->token);
        $start = microtime(true);
        $response = app()->handle($request);
        return [
            'status' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            'body' => $response->getContent(),
        ];
    }

    private function runRequests(string $url, array $baseData, int $count): array
    {
        $times = [];
        $success = 0;
        $fails = 0;
        $blocked = 0;
        for ($i = 0; $i < $count; $i++) {
            $res = $this->sendRequest($url, $baseData);
            $times[] = $res['duration_ms'];
            if ($res['status'] == 201 || $res['status'] == 202) $success++;
            elseif ($res['status'] == 429) $blocked++;
            else $fails++;
        }
        return compact('times', 'success', 'fails', 'blocked');
    }

    private function printSummary(string $title, array $results, string $successCode = '201')
    {
        $times = $results['times'];
        if (empty($times)) {
            $this->table(['Metric', 'Value'], [["No data", 0]]);
            return;
        }
        $this->table(
            ['Metric', 'Value'],
            [
                ["{$title} - Successful ({$successCode})", $results['success']],
                ["{$title} - Fails", $results['fails']],
                ["{$title} - Blocked (429)", $results['blocked']],
                ["{$title} - Min (ms)", min($times)],
                ["{$title} - Avg (ms)", round(array_sum($times) / count($times), 2)],
                ["{$title} - Max (ms)", max($times)],
            ]
        );
    }

    private function callScenario(string $method, int $count)
    {
        $this->info("Running: {$method}");
        switch ($method) {
            case 'raceUnsafe':
                $product = $this->createProduct('Race Unsafe', 200);
                $data = [
                    'user_id' => $this->user->id,
                    'items' => [['product_id' => $product->id, 'quantity' => 1]]
                ];
                $results = $this->runRequests('/api/orders/test/no-limit/unsafe', $data, $count);
                $this->printSummary('Race Unsafe', $results, '201');
                break;

            case 'raceSafe':
                $product = $this->createProduct('Race Safe', 200);
                $data = [
                    'user_id' => $this->user->id,
                    'items' => [['product_id' => $product->id, 'quantity' => 1]]
                ];
                $results = $this->runRequests('/api/orders/test/no-limit/safe', $data, $count);
                $this->printSummary('Race Safe', $results, '201');
                break;

            case 'rateLimit':
                $product = $this->createProduct('Rate Limit', 200);
                $data = [
                    'user_id' => $this->user->id,
                    'items' => [['product_id' => $product->id, 'quantity' => 1]]
                ];
                $results = $this->runRequests('/api/orders/test/with-limit/unsafe', $data, $count);
                $this->printSummary('Rate Limit', $results, '201');
                break;

            case 'queueUnsafe':
                $product = $this->createProduct('Queue Unsafe', 200);
                $data = [
                    'user_id' => $this->user->id,
                    'items' => [['product_id' => $product->id, 'quantity' => 1]]
                ];
                $results = $this->runRequests('/api/orders/queue/no-limit/unsafe', $data, $count);
                $this->printSummary('Queue Unsafe', $results, '202');
                break;

            case 'queueSafe':
                $product = $this->createProduct('Queue Safe', 200);
                $data = [
                    'user_id' => $this->user->id,
                    'items' => [['product_id' => $product->id, 'quantity' => 1]]
                ];
                $results = $this->runRequests('/api/orders/queue/no-limit/safe', $data, $count);
                $this->printSummary('Queue Safe', $results, '202');
                break;

            case 'queueLimit':
                $product = $this->createProduct('Queue Limit', 200);
                $data = [
                    'user_id' => $this->user->id,
                    'items' => [['product_id' => $product->id, 'quantity' => 1]]
                ];
                $results = $this->runRequests('/api/orders/queue/with-limit/unsafe', $data, $count);
                $this->printSummary('Queue with Limit', $results, '202');
                break;
        }
    }
}
