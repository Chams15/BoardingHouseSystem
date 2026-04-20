<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class PayMongoService
{
    public function createCheckoutSession(array $payload): array
    {
        $response = $this->request()->post('/checkout_sessions', [
            'data' => [
                'attributes' => $payload,
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->extractErrorMessage($response));
        }

        return $response->json();
    }

    public function verifyWebhookSignature(string $payload, ?string $signatureHeader): bool
    {
        $secret = (string) config('services.paymongo.webhook_secret');

        if ($secret === '' || $signatureHeader === null || trim($signatureHeader) === '') {
            return false;
        }

        $timestamp = null;
        $candidateSignatures = [];

        foreach (explode(',', $signatureHeader) as $chunk) {
            [$key, $value] = array_pad(explode('=', trim($chunk), 2), 2, null);
            if ($key !== null && $value !== null) {
                $normalizedKey = strtolower(trim($key));
                $normalizedValue = trim($value, " \t\n\r\0\x0B\"'");

                if ($normalizedKey === 't') {
                    $timestamp = $normalizedValue;
                    continue;
                }

                if (in_array($normalizedKey, ['te', 'v1', 'sig'], true) && $normalizedValue !== '') {
                    $candidateSignatures[] = $normalizedValue;
                }
            }
        }

        if ($timestamp !== null && ! empty($candidateSignatures)) {
            $tolerance = (int) config('services.paymongo.webhook_tolerance_seconds', 600);

            if ($tolerance > 0 && ctype_digit((string) $timestamp)) {
                $age = abs(time() - (int) $timestamp);

                if ($age > $tolerance) {
                    return false;
                }
            }

            $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

            foreach ($candidateSignatures as $candidate) {
                if (hash_equals($expected, $candidate)) {
                    return true;
                }
            }
        }

        $direct = hash_hmac('sha256', $payload, $secret);

        return hash_equals($direct, trim($signatureHeader));
    }

    public function retrieveCheckoutSession(string $checkoutSessionId): array
    {
        $response = $this->request()->get('/checkout_sessions/'.$checkoutSessionId);

        if (! $response->successful()) {
            throw new \RuntimeException($this->extractErrorMessage($response));
        }

        return $response->json();
    }

    public function extractCheckoutDetails(array $checkoutResponse): array
    {
        $data = Arr::get($checkoutResponse, 'data', []);
        $attributes = Arr::get($data, 'attributes', []);
        $paymentIntent = Arr::get($attributes, 'payment_intent', []);

        return [
            'checkout_session_id' => Arr::get($data, 'id'),
            'checkout_url' => Arr::get($attributes, 'checkout_url')
                ?: Arr::get($attributes, 'checkout_url.url')
                ?: Arr::get($attributes, 'url'),
            'payment_intent_id' => Arr::get($paymentIntent, 'id')
                ?: Arr::get($attributes, 'payment_intent_id')
                ?: Arr::get($attributes, 'payment_intent.id')
                ?: Arr::get($attributes, 'payment_intent.attributes.id')
                ?: Arr::get($attributes, 'payment_intent.attributes.payment_intent_id'),
            'reference_no' => Arr::get($attributes, 'reference_number')
                ?: Arr::get($attributes, 'metadata.reference_no')
                ?: Arr::get($attributes, 'metadata.reference_number'),
            'expires_at' => Arr::get($attributes, 'expires_at')
                ?: Arr::get($attributes, 'data.attributes.expires_at'),
        ];
    }

    public function extractCheckoutStatus(array $checkoutResponse): ?string
    {
        return Arr::get($checkoutResponse, 'data.attributes.status')
            ?? Arr::get($checkoutResponse, 'data.attributes.payment_intent.attributes.status')
            ?? Arr::get($checkoutResponse, 'data.attributes.payment_intent.status')
            ?? Arr::get($checkoutResponse, 'data.attributes.data.attributes.status');
    }

    private function request()
    {
        $baseUrl = rtrim((string) config('services.paymongo.base_url', 'https://api.paymongo.com/v1'), '/');
        $secretKey = (string) config('services.paymongo.secret_key');

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->withBasicAuth($secretKey, '')
            ->timeout(20)
            ->retry(2, 250);
    }

    private function extractErrorMessage(Response $response): string
    {
        $json = $response->json();
        $detail = Arr::get($json, 'errors.0.detail')
            ?? Arr::get($json, 'errors.0.code')
            ?? Arr::get($json, 'message')
            ?? 'PayMongo request failed.';

        return 'PayMongo error: '.$detail;
    }
}
