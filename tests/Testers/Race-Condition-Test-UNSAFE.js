import http from "k6/http";
import { check } from "k6";
import { SharedArray } from "k6/data";
import { Counter } from "k6/metrics";

const tokensData = new SharedArray("tokens", function () {
    const jsonData = open("../../storage/app/tokens.json");
    return JSON.parse(jsonData);
});

const successCount = new Counter("unsafe_success");
const failCount = new Counter("unsafe_fail");

export let options = {
    vus: 50,
    duration: "20s",
};

const BASE_URL = "http://localhost:8080/api";

export default function () {
    const userToken =
        tokensData[Math.floor(Math.random() * tokensData.length)].token;
    const authHeaders = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${userToken}`,
    };

    const res = http.post(
        `${BASE_URL}/orders/pessimistic/test/no-limit/unsafe`,
        JSON.stringify({
            items: [{ product_id: 95, quantity: 1 }],
        }),
        { headers: authHeaders },
    );

    const ok = check(res, { "status is 201": (r) => r.status === 201 });
    if (ok) {
        successCount.add(1);
    } else {
        failCount.add(1);
    }
}

export function handleSummary(data) {
    const total = data.metrics.http_reqs.values.count;
    const succ = data.metrics.unsafe_success
        ? data.metrics.unsafe_success.values.count
        : 0;
    const fail = data.metrics.unsafe_fail
        ? data.metrics.unsafe_fail.values.count
        : 0;
    const p95 = data.metrics.http_req_duration.values["p(95)"];
    const avg = data.metrics.http_req_duration.values.avg;
    const p99 = data.metrics.http_req_duration.values["p(99)"] || 0;
    const throughput = data.metrics.http_reqs.values.rate.toFixed(2);

    const col1Width = 28;
    const col2Width = 22;
    const border =
        "+" + "-".repeat(col1Width) + "+" + "-".repeat(col2Width) + "+";
    const row = (label, value) =>
        "| " +
        label.padEnd(col1Width - 1) +
        "| " +
        String(value).padEnd(col2Width - 1) +
        "|";

    console.log("\n" + border);
    console.log(row("UNSAFE RACE CONDITION TEST", ""));
    console.log(border);
    console.log(row("Metric", "Value"));
    console.log(border);
    console.log(row("Total requests", total));
    console.log(
        row(
            "Successful (201)",
            succ + " (" + ((succ / total) * 100).toFixed(1) + "%)",
        ),
    );
    console.log(
        row(
            "Failed (other)",
            fail + " (" + ((fail / total) * 100).toFixed(1) + "%)",
        ),
    );
    console.log(row("Response time (avg)", avg.toFixed(2) + " ms"));
    console.log(row("Response time (p95)", p95.toFixed(2) + " ms"));
    console.log(row("Response time (p99)", p99.toFixed(2) + " ms"));
    console.log(row("Throughput", throughput + " req/s"));
    console.log(border);
    console.log(row("Locking", "NONE (Unsafe)"));
    console.log(row("Initial stock", "20"));
    console.log(row("Expected", "Stock goes negative"));
    console.log(
        row(
            "Conclusion",
            succ > 20 ? "RACE CONDITION PROVEN" : "Check manually",
        ),
    );
    console.log(border + "\n");

    return {};
}
