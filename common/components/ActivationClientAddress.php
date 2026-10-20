<?php

namespace common\components;

/**
 * Client address for activation presigning only.
 *
 * Railway's edge connects to container nginx from the platform network, and
 * nginx passes that peer through as REMOTE_ADDR. The forwarded header is
 * honored only when that immediate peer is a private or loopback proxy.
 * The client is the rightmost address the proxy added, not the first
 * X-Forwarded-For value, which the caller can spoof. A direct public peer
 * ignores the header. This does not change the shared BlockedIp lookup.
 */
class ActivationClientAddress
{
    /**
     * @param mixed $remoteAddr
     * @param mixed $forwardedFor
     * @return string|null
     */
    public static function resolve($remoteAddr, $forwardedFor)
    {
        $remote = self::normalizeIp($remoteAddr);
        if ($remote === null) {
            return null;
        }

        if (!self::isTrustedProxy($remote)) {
            return $remote;
        }

        return self::clientFromForwarded($forwardedFor);
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function normalizeIp($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if ($value[0] === '[' && strpos($value, ']') !== false) {
            $value = substr($value, 1, strpos($value, ']') - 1);
        }

        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $value, $matches)) {
            $value = $matches[1];
        }

        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $value;
    }

    /**
     * @param string $ip
     * @return bool
     */
    private static function isTrustedProxy($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $packed = unpack('N', inet_pton($ip))[1];
            $ranges = [
                ['127.0.0.0', 8],
                ['10.0.0.0', 8],
                ['172.16.0.0', 12],
                ['192.168.0.0', 16],
            ];
            foreach ($ranges as $range) {
                $bits = $range[1];
                $mask = (0xFFFFFFFF << (32 - $bits)) & 0xFFFFFFFF;
                $network = unpack('N', inet_pton($range[0]))[1];
                if (($packed & $mask) === ($network & $mask)) {
                    return true;
                }
            }

            return false;
        }

        $packed = inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        if ($packed === inet_pton('::1')) {
            return true;
        }

        // IPv6 unique local addresses, fc00::/7.
        return (ord($packed[0]) & 0xfe) === 0xfc;
    }

    /**
     * @param mixed $forwardedFor
     * @return string|null
     */
    private static function clientFromForwarded($forwardedFor)
    {
        if (!is_string($forwardedFor) || trim($forwardedFor) === '') {
            return null;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $forwardedFor)), static function ($part) {
            return $part !== '';
        }));

        for ($index = count($parts) - 1; $index >= 0; $index--) {
            $ip = self::normalizeIp($parts[$index]);
            if ($ip === null) {
                return null;
            }
            if (!self::isTrustedProxy($ip)) {
                return $ip;
            }
        }

        return null;
    }
}
