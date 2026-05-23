import http from 'k6/http';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.EMAIL || 'tester@example.com';
const PASSWORD = __ENV.PASSWORD || 'password123';

export const options = {
  scenarios: {
    race_load: {
      executor: 'ramping-arrival-rate',
      startRate: 0,
      timeUnit: '1s',
      preAllocatedVUs: 300,
      maxVUs: 500,
      stages: [
        { target: 400, duration: '1s' }, // burst قوي جداً
      ],
    },
  },
};

// 🔐 Login لكل VU (أضمن حل)
function getToken() {
  let res = http.post(`${BASE_URL}/api/login`, JSON.stringify({
    email: EMAIL,
    password: PASSWORD
  }), {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
  });

  if (res.status !== 200) {
    console.log(`Login failed: ${res.status} - ${res.body}`);
    return null;
  }

  return res.json('data.token');
}

// 🚀 test function
export default function () {

  const token = getToken();

  if (!token) return;

  let res = http.post(`${BASE_URL}/api/orders/test/no-limit/unsafe`, JSON.stringify({
    product_id: 2,
    quantity: 1
  }), {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  });

  // Debug عند الحاجة
  if (res.status !== 201 && res.status !== 200) {
    console.log(`Order failed: ${res.status} - ${res.body}`);
  }
}