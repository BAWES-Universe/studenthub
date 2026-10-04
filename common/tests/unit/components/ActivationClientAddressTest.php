<?php

namespace common\tests\unit\components;

use common\components\ActivationClientAddress;
use PHPUnit\Framework\TestCase;

class ActivationClientAddressTest extends TestCase
{
    public function testDirectClientIgnoresForwardedHeader()
    {
        $address = ActivationClientAddress::resolve(
            '203.0.113.10',
            '198.51.100.4, 203.0.113.99'
        );

        $this->assertSame('203.0.113.10', $address);
    }

    public function testTrustedProxyUsesTheRightmostClientAddress()
    {
        $address = ActivationClientAddress::resolve(
            '10.0.0.8',
            '198.51.100.4, 203.0.113.50'
        );

        $this->assertSame('203.0.113.50', $address);
    }

    public function testChangingTheSpoofedForwardedPrefixDoesNotChangeTheClient()
    {
        $first = ActivationClientAddress::resolve('172.16.0.4', '1.1.1.1, 203.0.113.50');
        $second = ActivationClientAddress::resolve('172.16.0.4', '8.8.8.8, 203.0.113.50');

        $this->assertSame('203.0.113.50', $first);
        $this->assertSame($first, $second);
    }

    public function testTrustedProxyWithoutAClientAddressFailsClosed()
    {
        $this->assertNull(ActivationClientAddress::resolve('127.0.0.1', ''));
        $this->assertNull(ActivationClientAddress::resolve('10.1.2.3', '10.9.9.9'));
    }
}
