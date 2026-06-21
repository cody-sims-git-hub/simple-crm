<?php

namespace Tests\Unit;

use App\Support\OutboundUrl;
use Tests\TestCase;

class OutboundUrlTest extends TestCase
{
    public function test_allows_public_ip_literals(): void
    {
        $this->assertTrue(OutboundUrl::isPublic('https://1.1.1.1/hook'));
        $this->assertTrue(OutboundUrl::isPublic('http://8.8.8.8/path'));
    }

    public function test_blocks_loopback(): void
    {
        $this->assertFalse(OutboundUrl::isPublic('http://127.0.0.1/x'));
        $this->assertFalse(OutboundUrl::isPublic('http://127.0.0.1:9200'));
        $this->assertFalse(OutboundUrl::isPublic('http://[::1]/x'));
    }

    public function test_blocks_private_ranges(): void
    {
        $this->assertFalse(OutboundUrl::isPublic('http://10.0.0.5/x'));
        $this->assertFalse(OutboundUrl::isPublic('http://172.16.4.4/x'));
        $this->assertFalse(OutboundUrl::isPublic('http://192.168.1.10/x'));
    }

    public function test_blocks_cloud_metadata_endpoint(): void
    {
        $this->assertFalse(OutboundUrl::isPublic('http://169.254.169.254/latest/meta-data/'));
    }

    public function test_blocks_ipv6_link_local_and_unique_local(): void
    {
        $this->assertFalse(OutboundUrl::isPublic('http://[fe80::1]/x'));
        $this->assertFalse(OutboundUrl::isPublic('http://[fc00::1]/x'));
    }

    public function test_blocks_non_http_schemes(): void
    {
        $this->assertFalse(OutboundUrl::isPublic('ftp://1.1.1.1/x'));
        $this->assertFalse(OutboundUrl::isPublic('file:///etc/passwd'));
        $this->assertFalse(OutboundUrl::isPublic('gopher://1.1.1.1/x'));
    }

    public function test_blocks_malformed_input(): void
    {
        $this->assertFalse(OutboundUrl::isPublic('not-a-url'));
        $this->assertFalse(OutboundUrl::isPublic(''));
        $this->assertFalse(OutboundUrl::isPublic('http://'));
    }
}
