import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const RUTA_SITIOS = `${BASE_URL}/sitios/disponibilidad`;

export const options = {
  stages: [
    { duration: '10s', target: 10 },
    { duration: '20s', target: 30 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<800'],
    checks: ['rate>0.95'],
  },
};

export default function () {
  const respuesta = http.get(RUTA_SITIOS);

  check(respuesta, {
    'GET /sitios/disponibilidad responde 200': (r) => r.status === 200,
  });

  sleep(1);
}
