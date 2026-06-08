import http from "k6/http";
import { check } from "k6";
import { SharedArray } from "k6/data";
import { Counter } from "k6/metrics";

const tokensData = new SharedArray("tokens", function () {
    const jsonData = open("../../storage/app/tokens.json");
    return JSON.parse(jsonData);
});

const success201 = new Counter("safe_201");
const rejected409 = new Counter("safe_409");
const otherError = new Counter("safe_other");

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
        `${BASE_URL}/orders/pessimistic/test/no-limit/safe`,
        JSON.stringify({
            items: [{ product_id: 96, quantity: 1 }],
        }),
        { headers: authHeaders },
    );

    if (res.status === 201) {
        success201.add(1);
    } else if (res.status === 409) {
        rejected409.add(1);
    } else {
        otherError.add(1);
    }
    check(res, {
        "Safe order status": (r) => r.status === 201 || r.status === 409,
    });
}

export function handleSummary(data) {
    const total = data.metrics.http_reqs.values.count;
    const s201 = data.metrics.safe_201 ? data.metrics.safe_201.values.count : 0;
    const s409 = data.metrics.safe_409 ? data.metrics.safe_409.values.count : 0;
    const other = data.metrics.safe_other
        ? data.metrics.safe_other.values.count
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
    console.log(row("SAFE RACE CONDITION TEST", ""));
    console.log(border);
    console.log(row("Metric", "Value"));
    console.log(border);
    console.log(row("Total requests", total));
    console.log(
        row(
            "Successful (201)",
            s201 + " (" + ((s201 / total) * 100).toFixed(1) + "%)",
        ),
    );
    console.log(
        row(
            "Rejected (409)",
            s409 + " (" + ((s409 / total) * 100).toFixed(1) + "%)",
        ),
    );
    console.log(
        row(
            "Other errors",
            other + " (" + ((other / total) * 100).toFixed(1) + "%)",
        ),
    );
    console.log(row("Response time (avg)", avg.toFixed(2) + " ms"));
    console.log(row("Response time (p95)", p95.toFixed(2) + " ms"));
    console.log(row("Response time (p99)", p99.toFixed(2) + " ms"));
    console.log(row("Throughput", throughput + " req/s"));
    console.log(border);
    console.log(row("Locking", "Pessimistic (row-level)"));
    console.log(row("Initial stock", "20"));
    console.log(row("Expected", "Only 20 succeed (201)"));
    console.log(
        row(
            "Conclusion",
            s201 === 20 && s409 === total - 20
                ? "LOCKING WORKS"
                : "Check manually",
        ),
    );
    console.log(border + "\n");

    return {};
}
