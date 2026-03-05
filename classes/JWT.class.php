<?php
/**
 * SimpleJWT: A minimal, secure JWT library in pure PHP.
 *
 * Usage:
 *   $token = JWT::encode(['user_id'=>123], 'my_secret_key');
 *   $data  = JWT::decode($token, 'my_secret_key');
 */

class JWT
{
    /**
     * Encode a payload into a JWT string.
     *
     * @param array $payload   Data to include in the token.
     * @param string $secret   Secret key for HMAC signature.
     * @param int $expiry      Seconds until expiration (optional).
     *
     * @return string          The JWT.
     */
    public static function encode(array $payload, string $secret, int $expiry = 3600): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $expiry;

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        $base64Signature = self::base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    /**
     * Decode and validate a JWT, returning the payload.
     *
     * @param string $token    The JWT string.
     * @param string $secret   Secret key to verify signature.
     *
     * @return array           The decoded payload.
     *
     * @throws \Exception     On invalid signature or expired token.
     */
    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \UnexpectedValueException('Wrong number of segments');
        }
        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $header = json_decode(self::base64UrlDecode($base64Header), true);
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        $signatureProvided = self::base64UrlDecode($base64Signature);

        // Verify signature
        $expectedSig = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        if (!hash_equals($expectedSig, $signatureProvided)) {
            throw new \UnexpectedValueException('Invalid signature');
        }

        // Verify expiration
        if (isset($payload['exp']) && time() >= $payload['exp']) {
            throw new \UnexpectedValueException('Token expired');
        }

        return $payload;
    }

    /**
     * URL-safe Base64 encode.
     */
    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * URL-safe Base64 decode.
     */
    protected static function base64UrlDecode(string $data): string
    {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
