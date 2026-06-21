<?php

namespace App\Support;

class OutboundUrl
{
    /**
     * Whether $url is safe for the server to call, as an SSRF guard: it must
     * use an http(s) scheme, have a resolvable host, and every address that
     * host resolves to must be a public (non-private, non-reserved) IP. This
     * blocks loopback, link-local (cloud metadata at 169.254.169.254), private
     * networks, and IPv6 unique/link-local ranges.
     *
     * Note: DNS is re-checked at call time, but a determined attacker can still
     * race DNS (TOCTOU). For higher assurance, resolve once and connect to that
     * pinned IP. This is a strong, standard mitigation for the threat here.
     */
    public static function isPublic(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = trim($parts['host'], '[]'); // strip IPv6 literal brackets

        $ips = self::resolve($host);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a host to every IP it points at. IP literals short-circuit (no
     * DNS lookup). Returns [] when nothing resolves.
     *
     * @return array<int, string>
     */
    private static function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = gethostbynamel($host);
        $ips = is_array($ips) ? $ips : [];

        $records = @dns_get_record($host, DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (! empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return $ips;
    }

    /**
     * Whether a single IP is a globally routable public address.
     */
    private static function isPublicIp(string $ip): bool
    {
        // PHP's filter handles private/reserved IPv4 and most IPv6 cases.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // Belt-and-suspenders for IPv6 ranges the flags don't reliably cover.
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 16) {
            $first = ord($packed[0]);
            $second = ord($packed[1]);

            if (($first & 0xFE) === 0xFC) {        // fc00::/7  unique-local
                return false;
            }
            if ($first === 0xFE && ($second & 0xC0) === 0x80) { // fe80::/10 link-local
                return false;
            }
        }

        return true;
    }
}
