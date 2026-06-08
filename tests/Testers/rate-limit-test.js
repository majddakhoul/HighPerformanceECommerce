import http from "k6/http";
import { SharedArray } from "k6/data";
import { Counter } from "k6/metrics";

const tokensData = new SharedArray("tokens", function () {
    const jsonData = open("../../storage/app/tokens.json");
    return JSON.parse(jsonData);
});

const noLimitOk = new Counter("no_limit_ok");
const noLimitBlocked = new Counter("no_limit_blocked");
const limitOk = new Counter("limit_ok");
const limitBlocked = new Counter("limit_blocked");

export let options = {
    vus: 50,
    duration: "30s",
};

const BASE_URL = "http://localhost:8080/api";

export default function () {
    const token = tokensData[0].token;
    const authHeaders = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
    };
    const payload = JSON.stringify({
        items: [{ product_id: 1, quantity: 1 }],
    });

    const resNoLimit = http.post(
        `${BASE_URL}/orders/pessimistic/test/no-limit/safe`,
        payload,
        { headers: authHeaders },
    );
    if (resNoLimit.status === 201 || resNoLimit.status === 409) {
        noLimitOk.add(1);
    } else {
        noLimitBlocked.add(1);
    }

    const resLimit = http.post(
        `${BASE_URL}/orders/pessimistic/test/with-limit/safe`,
        payload,
        { headers: authHeaders },
    );
    if (resLimit.status === 201 || resLimit.status === 409) {
        limitOk.add(1);
    } else {
        limitBlocked.add(1);
    }
}

export function handleSummary(data) {
    const noLimitTotal =
        (data.metrics.no_limit_ok ? data.metrics.no_limit_ok.values.count : 0) +
        (data.metrics.no_limit_blocked
            ? data.metrics.no_limit_blocked.values.count
            : 0);
    const limitTotal =
        (data.metrics.limit_ok ? data.metrics.limit_ok.values.count : 0) +
        (data.metrics.limit_blocked
            ? data.metrics.limit_blocked.values.count
            : 0);

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
    console.log(row("RESOURCE MANAGEMENT (RATE LIMITING) TEST", ""));
    console.log(border);
    console.log(row("Metric", "No Limit"));
    console.log(border);
    console.log(row("Total requests", noLimitTotal));
    console.log(
        row(
            "Accepted (2xx)",
            data.metrics.no_limit_ok
                ? data.metrics.no_limit_ok.values.count
                : 0,
        ),
    );
    console.log(
        row(
            "Blocked (429/other)",
            data.metrics.no_limit_blocked
                ? data.metrics.no_limit_blocked.values.count
                : 0,
        ),
    );
    console.log(border);
    console.log(row("Metric", "With Limit (10/min)"));
    console.log(border);
    console.log(row("Total requests", limitTotal));
    console.log(
        row(
            "Accepted (2xx)",
            data.metrics.limit_ok ? data.metrics.limit_ok.values.count : 0,
        ),
    );
    console.log(
        row(
            "Blocked (429/other)",
            data.metrics.limit_blocked
                ? data.metrics.limit_blocked.values.count
                : 0,
        ),
    );
    console.log(border + "\n");

    return {};
}
