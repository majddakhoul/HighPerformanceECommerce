import http from 'k6/http';
import { Trend, Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const ADMIN_EMAIL = __ENV.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = __ENV.ADMIN_PASSWORD || 'password';
const TEST_USER_EMAIL = 'tester@example.com';
const TEST_USER_PASSWORD = 'password123';

const safeDuration = new Trend('safe_duration');
const safeSuccess = new Counter('safe_success');

export function setup() {
    let res = http.post(`${BASE_URL}/api/login`, JSON.stringify({
        email: ADMIN_EMAIL, password: ADMIN_PASSWORD
    }), { headers: { 'Content-Type': 'application/json' } });
    const adminToken = res.json('data.token');

    res = http.post(`${BASE_URL}/api/products`, JSON.stringify({
        name: 'Race Safe Product',
        price: 100,
        stock: 200,           
        category: 'test',
    }), { headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${adminToken}` } });
    const productId = res.json('data.id');
    console.log(`Safe product created, id=${productId}, stock=10`);

    res = http.post(`${BASE_URL}/api/login`, JSON.stringify({
        email: TEST_USER_EMAIL, password: TEST_USER_PASSWORD
    }), { headers: { 'Content-Type': 'application/json' } });
    const userToken = res.json('data.token');

    return { productId, userToken };
}

export default function (data) {
    const payload = JSON.stringify({
        items: [{ product_id: data.productId, quantity: 1 }]
    });
    const res = http.post(`${BASE_URL}/api/orders/test/no-limit/safe`, payload, {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${data.userToken}`
        },
    });
    safeDuration.add(res.timings.duration);
    if (res.status === 201) safeSuccess.add(1);
}

export const options = {
    scenarios: {
        burst: {
            executor: 'per-vu-iterations',
            vus: 50,
            iterations: 1,
            maxDuration: '10s',
        },
    },
};

export function handleSummary(data) {
    console.log('\n--- SAFE Race Condition Test ---');
    console.log(`Successful orders (201): ${data.metrics.safe_success?.value || 0}`);
    console.log('Check product stock: it should be EXACTLY 0 (no overselling)');
    return { stdout: 'Safe test completed.' };
}