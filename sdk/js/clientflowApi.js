export class ClientflowApi {
  constructor(baseUrl, token) {
    this.baseUrl = baseUrl.replace(/\/$/, '');
    this.token = token;
  }

  async getClients(page = 1) {
    return this.request('GET', `/api/clients?page=${page}`);
  }

  async createInvoice(payload) {
    return this.request('POST', '/api/invoices', payload);
  }

  async request(method, path, body = undefined) {
    const res = await fetch(this.baseUrl + path, {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${this.token}`,
      },
      body: body ? JSON.stringify(body) : undefined,
    });

    if (!res.ok) {
      const text = await res.text();
      throw new Error(`API error ${res.status}: ${text}`);
    }

    return res.json();
  }
}

// Usage:
// const api = new ClientflowApi('http://127.0.0.1:8000', 'your_token');
// api.getClients().then(console.log);
