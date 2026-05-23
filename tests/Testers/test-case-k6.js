import http from 'k6/http';
import { sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js';

// تعريف المتريكات لكل سيناريو
const raceUnsafeTrend = new Trend('race_unsafe_ms');
const raceSafeTrend = new Trend('race_safe_ms');
const rateLimitTrend = new Trend('rate_limit_ms');
const queueUnsafeTrend = new Trend('queue_unsafe_ms');
const queueSafeTrend = new Trend('queue_safe_ms');
const queueLimitTrend = new Trend('queue_limit_ms');

const successCount = new Counter('success_total');
const failCount = new Counter('fail_total');
const blockedCount = new Counter('blocked_429_total');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const ADMIN_EMAIL = __ENV.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = __ENV.ADMIN_PASSWORD || 'password';

// ---------- دوال مساعدة ----------
function logRequest(scenario, status, dur) {
    if (status === 201 || status === 202) {
        console.log(`[SUCCESS] ${scenario}: ${status} | ${dur.toFixed(2)}ms`);
    } else if (status === 429) {
        console.log(`[BLOCKED] ${scenario}: 429 | ${dur.toFixed(2)}ms`);
    } else {
        console.log(`[FAIL]    ${scenario}: ${status} | ${dur.toFixed(2)}ms`);
    }
}

// ---------- الإعداد (setup) ----------
export function setup() {
    // تسجيل دخول المستخدم العادي
    const userLoginRes = http.post(`${BASE_URL}/api/login`,
        JSON.stringify({ email: 'tester@example.com', password: 'password123' }),
        { headers: { 'Content-Type': 'application/json' } }
    );
    if (userLoginRes.status !== 200) {
        console.error(`User login failed: ${userLoginRes.status} ${userLoginRes.body}`);
        throw new Error('User login failed');
    }
    const userToken = userLoginRes.json('data.token');
    console.log('User logged in successfully');

    // تسجيل دخول الأدمن
    const adminLoginRes = http.post(`${BASE_URL}/api/login`,
        JSON.stringify({ email: ADMIN_EMAIL, password: ADMIN_PASSWORD }),
        { headers: { 'Content-Type': 'application/json' } }
    );
    if (adminLoginRes.status !== 200) {
        console.error(`Admin login failed: ${adminLoginRes.status} ${adminLoginRes.body}`);
        throw new Error('Admin login failed');
    }
    const adminToken = adminLoginRes.json('data.token');
    console.log('Admin logged in successfully');

    // إنشاء المنتجات عبر الأدمن
    const adminHeaders = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${adminToken}`,
    };

    const scenarios = [
        { key: 'race_unsafe', name: 'Race Unsafe' },
        { key: 'race_safe',   name: 'Race Safe' },
        { key: 'rate_limit',  name: 'Rate Limit' },
        { key: 'queue_unsafe', name: 'Queue Unsafe' },
        { key: 'queue_safe',  name: 'Queue Safe' },
        { key: 'queue_limit', name: 'Queue Limit' },
    ];

    const productIds = {};
    for (const s of scenarios) {
        // مخزون 50 فقط للـ Race حتى يظهر السباق بوضوح؛ للمعدلات الأخرى 200 تكفي
        const stock = (s.key === 'race_unsafe' || s.key === 'race_safe') ? 200 : 200;
        const payload = JSON.stringify({
            name: `Case Product ${s.name}`,
            price: 100,
            stock: stock,
            category: 'test',
        });
        const res = http.post(`${BASE_URL}/api/products`, payload, { headers: adminHeaders });
        if (res.status === 201) {
            const product = res.json('data');
            productIds[s.key] = product.id;
            console.log(`Product "${s.name}" created (stock: ${stock}), id=${product.id}`);
        } else {
            console.error(`Create product ${s.name} failed: ${res.status} ${res.body}`);
            throw new Error('Product creation failed');
        }
    }

    return { token: userToken, productIds };
}

// ---------- دالة تنفيذ طلب واحد (تُستخدم في جميع الحالات) ----------
function makeRequest(url, token, productId, trend, scenarioName) {
    const payload = JSON.stringify({
        items: [{ product_id: productId, quantity: 1 }],
    });
    const res = http.post(url, payload, {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
        },
    });

    const dur = res.timings.duration;
    trend.add(dur);
    logRequest(scenarioName, res.status, dur);

    if (res.status === 201 || res.status === 202) {
        successCount.add(1);
    } else if (res.status === 429) {
        blockedCount.add(1);
    } else {
        failCount.add(1);
    }
}

// ---------- دوال خاصة بالسيناريوهات المتوازية ----------
export function raceUnsafe(data) {
    makeRequest(
        `${BASE_URL}/api/orders/test/no-limit/unsafe`,
        data.token,
        data.productIds.race_unsafe,
        raceUnsafeTrend,
        'RACE-UNSAFE'
    );
}

export function raceSafe(data) {
    makeRequest(
        `${BASE_URL}/api/orders/test/no-limit/safe`,
        data.token,
        data.productIds.race_safe,
        raceSafeTrend,
        'RACE-SAFE'
    );
}

// ---------- الدالة الرئيسية (لباقي السيناريوهات التسلسلية) ----------
export default function (data) {
    const token = data.token;
    const ids = data.productIds;

    const sequentialScenarios = [
        {
            name: 'Rate Limit',
            url: `${BASE_URL}/api/orders/test/with-limit/unsafe`,
            productId: ids.rate_limit,
            trend: rateLimitTrend,
            iterations: 50,
        },
        {
            name: 'Queue Unsafe',
            url: `${BASE_URL}/api/orders/queue/no-limit/unsafe`,
            productId: ids.queue_unsafe,
            trend: queueUnsafeTrend,
            iterations: 50,
        },
        {
            name: 'Queue Safe',
            url: `${BASE_URL}/api/orders/queue/no-limit/safe`,
            productId: ids.queue_safe,
            trend: queueSafeTrend,
            iterations: 50,
        },
        {
            name: 'Queue with Limit',
            url: `${BASE_URL}/api/orders/queue/with-limit/unsafe`,
            productId: ids.queue_limit,
            trend: queueLimitTrend,
            iterations: 50,
        },
    ];

    for (const scenario of sequentialScenarios) {
        console.log(`\n--- Starting: ${scenario.name} ---`);
        for (let i = 0; i < scenario.iterations; i++) {
            makeRequest(scenario.url, token, scenario.productId, scenario.trend, scenario.name);
            sleep(0.01);
        }
        console.log(`--- Finished: ${scenario.name} ---`);
    }
}

// ---------- إعدادات التشغيل (سيناريوهين متوازيين + سيناريو تسلسلي واحد) ----------
export const options = {
    scenarios: {
        race_unsafe_burst: {
            executor: 'shared-iterations',
            vus: 50,
            iterations: 50,
            maxDuration: '30s',
            exec: 'raceUnsafe',
        },
        race_safe_burst: {
            executor: 'shared-iterations',
            vus: 50,
            iterations: 50,
            maxDuration: '30s',
            exec: 'raceSafe',
            startTime: '35s',         // يبدأ بعد انتهاء الـ Unsafe تقريباً
        },
        sequential_tests: {
            executor: 'per-vu-iterations',
            vus: 1,
            iterations: 1,
            exec: 'default',         // الدالة الافتراضية لتنفيذ البقية
            startTime: '75s',       // بعد انتهاء السيناريوهين المتوازيين
        },
    },
};

// ---------- ملخص مخصص (جداول) ----------
function computeStats(trend) {
    const values = Object.values(trend.data);
    if (!values.length) return { min: 0, avg: 0, max: 0, count: 0 };
    const sum = values.reduce((a, b) => a + b, 0);
    return {
        min: Math.min(...values),
        avg: sum / values.length,
        max: Math.max(...values),
        count: values.length,
    };
}

export function handleSummary(data) {
    const trends = {
        'Race Unsafe (parallel 50)': data.metrics.race_unsafe_ms,
        'Race Safe (parallel 50)': data.metrics.race_safe_ms,
        'Rate Limit': data.metrics.rate_limit_ms,
        'Queue Unsafe': data.metrics.queue_unsafe_ms,
        'Queue Safe': data.metrics.queue_safe_ms,
        'Queue with Limit': data.metrics.queue_limit_ms,
    };

    console.log('\n========== K6 Performance Test Summary ==========');
    for (const [name, trend] of Object.entries(trends)) {
        const stats = computeStats(trend);
        console.log(`\n${name}`);
        console.log(`  Successful (201/202) : ${stats.count}`);
        console.log(`  Min (ms)             : ${stats.min.toFixed(2)}`);
        console.log(`  Avg (ms)             : ${stats.avg.toFixed(2)}`);
        console.log(`  Max (ms)             : ${stats.max.toFixed(2)}`);
    }

    console.log('\n👉 IMPORTANT: Check product stock in database:');
    console.log('   Race Unsafe stock should be NEGATIVE (overselling)');
    console.log('   Race Safe stock should be EXACTLY 0 (no overselling)');

    return {
        stdout: textSummary(data, { indent: ' ', enableColors: true }),
        'results.json': JSON.stringify(data, null, 2),
    };
}