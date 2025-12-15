<?php

namespace ClientflowSdk;

class ClientflowApi
{
    protected string $baseUrl;
    protected string $token;

    public function __construct(string $baseUrl, string $token)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    public function getClients(int $page = 1): array
    {
        return $this->request('GET', '/api/clients?page='.$page);
    }

    public function createInvoice(array $payload): array
    {
        return $this->request('POST', '/api/invoices', $payload);
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $url = $this->baseUrl.$path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer '.$this->token,
                'Content-Type: application/json',
            ],
        ]);

        if (in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException('HTTP request failed: '.curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 400) {
            throw new \RuntimeException("API error ({$status}): ".$response);
        }

        return json_decode($response, true) ?? [];
    }
}
