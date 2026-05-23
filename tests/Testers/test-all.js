import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = 'http://localhost:8000';
const LOGIN_URL = `${BASE_URL}/api/login`;

const TEST_USER = {
  email: 'tester@example.com',
  password: 'password123'
};

let authToken = '';

export function setup() {
  const loginRes = http.post(LOGIN_URL, JSON.stringify(TEST_USER), {
    headers: { 'Content-Type': 'application/json' },
  });
  check(loginRes, { 'Login successful': (r) => r.status === 200 });
  authToken = loginRes.json('data.token');
  return { token: authToken };
}

export default function (data) {
  const token = data.token;
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
  };

  // ========== 1. RACE CONDITION (Direct No Limit) ==========
  console.log('\n=== 1. RACE CONDITION TEST ===');
  // Unsafe: يجب أن يسمح بتجاوز الستوك (إذا تم إرسال طلبات متوازية)
  const unsafeRes = http.post(
    `${BASE_URL}/api/orders/test/no-limit/unsafe`,
    JSON.stringify({ items: [{ product_id: 1, quantity: 1 }] }),
    { headers }
  );
  check(unsafeRes, { 'Unsafe (no limit) returns 201': (r) => r.status === 201 });
  
  const safeRes = http.post(
    `${BASE_URL}/api/orders/test/no-limit/safe`,
    JSON.stringify({ items: [{ product_id: 1, quantity: 1 }] }),
    { headers }
  );
  check(safeRes, { 'Safe (no limit) returns 201': (r) => r.status === 201 });

  // ========== 2. RATE LIMITING (Direct With Limit) ==========
  console.log('\n=== 2. RATE LIMITING TEST ===');
  let success = 0, blocked = 0;
  for (let i = 0; i < 15; i++) {
    const res = http.post(
      `${BASE_URL}/api/orders/test/with-limit/unsafe`,
      JSON.stringify({ items: [{ product_id: 1, quantity: 1 }] }),
      { headers }
    );
    if (res.status === 201) success++;
    if (res.status === 429) blocked++;
    sleep(0.02);
  }
  console.log(`Rate limiting: ${success} success, ${blocked} blocked (429)`);

  // ========== 3. ASYNC QUEUE (No Limit) ==========
  console.log('\n=== 3. ASYNC QUEUE TEST ===');
  const queueUnsafe = http.post(
    `${BASE_URL}/api/orders/queue/no-limit/unsafe`,
    JSON.stringify({ items: [{ product_id: 1, quantity: 1 }] }),
    { headers }
  );
  check(queueUnsafe, { 'Queue unsafe returns 202': (r) => r.status === 202 });
  
  const queueSafe = http.post(
    `${BASE_URL}/api/orders/queue/no-limit/safe`,
    JSON.stringify({ items: [{ product_id: 1, quantity: 1 }] }),
    { headers }
  );
  check(queueSafe, { 'Queue safe returns 202': (r) => r.status === 202 });

  // ========== 4. QUEUE WITH RATE LIMITING ==========
  console.log('\n=== 4. QUEUE WITH RATE LIMITING ===');
  let qSuccess = 0, qBlocked = 0;
  for (let i = 0; i < 15; i++) {
    const res = http.post(
      `${BASE_URL}/api/orders/queue/with-limit/unsafe`,
      JSON.stringify({ items: [{ product_id: 1, quantity: 1 }] }),
      { headers }
    );
    if (res.status === 202) qSuccess++;
    if (res.status === 429) qBlocked++;
    sleep(0.02);
  }
  console.log(`Queue with limit: ${qSuccess} accepted, ${qBlocked} rate-limited`);
}