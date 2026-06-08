import http from "k6/http";
import { check, sleep } from "k6";
import { Counter } from "k6/metrics";

const productsOk = new Counter("products_ok");
const addOk = new Counter("add_ok");
const checkoutOk = new Counter("checkout_ok");

export function setup() {
    const BASE_URL = "http://localhost:8005/api";
    const tokens = [];
    for (let i = 1; i <= 20; i++) {
        const payload = JSON.stringify({
            email: `user${i}@example.com`,
            password: "password",
        });
        const res = http.post(`${BASE_URL}/login`, payload, {
            headers: { "Content-Type": "application/json" },
        });
        if (res.status === 200) {
            tokens.push({
                email: `user${i}@example.com`,
                token: res.json().data.token,
            });
        }
    }
    return { tokens };
}

export let options = {
    stages: [
        { duration: "2m", target: 60 },
        { duration: "2m", target: 120 },
        { duration: "4m", target: 120 },
        { duration: "2m", target: 0 },
    ],
    thresholds: {
        http_req_duration: ["p(95)<3000"],
        "http_req_failed{status:5xx}": ["rate<0.03"],
    },
};

const BASE_URL = "http://localhost:8005/api";

export default function (data) {
    const user = data.tokens[Math.floor(Math.random() * data.tokens.length)];
    if (!user) return;

    const authHeaders = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${user.token}`,
    };

    let productsRes = http.get(`${BASE_URL}/products`, {
        headers: authHeaders,
    });
    if (check(productsRes, { "Products fetched": (r) => r.status === 200 })) {
        productsOk.add(1);
    }

    let randomProductId = Math.floor(Math.random() * 100) + 1;
    let addRes = http.post(
        `${BASE_URL}/cart/add`,
        JSON.stringify({
            product_id: randomProductId,
            quantity: 1,
        }),
        { headers: authHeaders },
    );
    if (check(addRes, { "Item added": (r) => r.status === 200 })) {
        addOk.add(1);
    }

    if (Math.random() < 0.4) {
        let checkoutRes = http.post(`${BASE_URL}/cart/checkout`, null, {
            headers: authHeaders,
        });
        if (
            check(checkoutRes, {
                "Checkout done": (r) => r.status === 201 || r.status === 202,
            })
        ) {
            checkoutOk.add(1);
        }
    }

    sleep(Math.random() * 2 + 1);
}

export function handleSummary(data) {
    const totalReqs = data.metrics.http_reqs
        ? data.metrics.http_reqs.values.count
        : 0;
    const reqRate = data.metrics.http_reqs
        ? data.metrics.http_reqs.values.rate.toFixed(2)
        : "0.00";
    const avgDuration = (
        data.metrics.http_req_duration
            ? data.metrics.http_req_duration.values.avg
            : 0
    ).toFixed(2);
    const p95Duration = (
        data.metrics.http_req_duration
            ? data.metrics.http_req_duration.values["p(95)"]
            : 0
    ).toFixed(2);
    const p99Duration = (
        data.metrics.http_req_duration
            ? data.metrics.http_req_duration.values["p(99)"] || 0
            : 0
    ).toFixed(2);
    const failed5xx = data.metrics["http_req_failed{status:5xx}"]
        ? data.metrics["http_req_failed{status:5xx}"].values.count
        : 0;
    const checksPassed = data.metrics.checks
        ? data.metrics.checks.values.passes
        : 0;
    const checksTotal = data.metrics.checks
        ? data.metrics.checks.values.checks
        : 0;
    const checksRate =
        checksTotal > 0
            ? ((checksPassed / checksTotal) * 100).toFixed(1)
            : "0.0";

    const prodOk = data.metrics.products_ok
        ? data.metrics.products_ok.values.count
        : 0;
    const prodRate =
        totalReqs > 0 ? ((prodOk / totalReqs) * 100).toFixed(1) : "0.0";
    const addOkCount = data.metrics.add_ok
        ? data.metrics.add_ok.values.count
        : 0;
    const addRate =
        totalReqs > 0 ? ((addOkCount / totalReqs) * 100).toFixed(1) : "0.0";
    const checkoutOkCount = data.metrics.checkout_ok
        ? data.metrics.checkout_ok.values.count
        : 0;
    const checkoutRate =
        totalReqs > 0
            ? ((checkoutOkCount / totalReqs) * 100).toFixed(1)
            : "0.0";

    console.log("\n+------------------------------------+------------------+");
    console.log("| Metric                             | Value            |");
    console.log("+------------------------------------+------------------+");
    console.log(
        `| Total requests                     | ${totalReqs.toString().padEnd(16)} |`,
    );
    console.log(
        `| Request rate (req/s)               | ${reqRate.toString().padEnd(16)} |`,
    );
    console.log("+------------------------------------+------------------+");
    console.log(
        `| Average response time (ms)         | ${avgDuration.toString().padEnd(16)} |`,
    );
    console.log(
        `| p95 response time (ms)             | ${p95Duration.toString().padEnd(16)} |`,
    );
    console.log(
        `| p99 response time (ms)             | ${p99Duration.toString().padEnd(16)} |`,
    );
    console.log("+------------------------------------+------------------+");
    console.log(
        `| HTTP 5xx errors                    | ${failed5xx.toString().padEnd(16)} |`,
    );
    console.log(
        `| Checks pass rate (%)               | ${checksRate.toString().padEnd(16)} |`,
    );
    console.log("+------------------------------------+------------------+");
    console.log(
        `| Products fetched success (%)       | ${prodRate.toString().padEnd(16)} |`,
    );
    console.log(
        `| Item added success (%)             | ${addRate.toString().padEnd(16)} |`,
    );
    console.log(
        `| Checkout success (%)               | ${checkoutRate.toString().padEnd(16)} |`,
    );
    console.log("+------------------------------------+------------------+\n");

    return {};
}
