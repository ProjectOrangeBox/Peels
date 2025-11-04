<?php

use peels\console\BitWise;

final class BitwiseTest extends unitTestHelper
{
    protected array $flagValues = ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'debug'];

    public function testSingle(): void
    {
        $bitwise = new BitWise($this->flagValues);
        $bitwise->turnOn('notice');
        $this->assertEquals(false, $bitwise->critical);
        $this->assertEquals(false, $bitwise->emergency);
        $this->assertEquals(true, $bitwise->notice);
        $this->assertEquals(true, $bitwise->always);
    }

    public function testEverything(): void
    {
        $bitwise = new BitWise($this->flagValues);
        $bitwise->turnOn('everything');
        $this->assertEquals(true, $bitwise->critical);
        $this->assertEquals(true, $bitwise->emergency);
        $this->assertEquals(true, $bitwise->notice);
        $this->assertEquals(true, $bitwise->always);
    }

    public function testNoticeEmergency(): void
    {
        $bitwise = new BitWise($this->flagValues);
        $bitwise->turnOn('notice', 'emergency');
        $this->assertEquals(false, $bitwise->critical);
        $this->assertEquals(true, $bitwise->emergency);
        $this->assertEquals(true, $bitwise->notice);
        $this->assertEquals(true, $bitwise->always);
    }

    public function testTurnOffEmergency(): void
    {
        $bitwise = new BitWise($this->flagValues);
        $bitwise->turnOn('notice', 'emergency');
        $this->assertEquals(false, $bitwise->critical);
        $this->assertEquals(true, $bitwise->emergency);
        $this->assertEquals(true, $bitwise->notice);
        $this->assertEquals(true, $bitwise->always);

        $bitwise->turnOff('emergency');
        $this->assertEquals(false, $bitwise->critical);
        $this->assertEquals(true, $bitwise->emergency);
        $this->assertEquals(false, $bitwise->notice);
        $this->assertEquals(true, $bitwise->always);
    }

    public function testAddBit(): void
    {
        $bitwise = new BitWise($this->flagValues);
        $bitwise->addBit('Don');
        $bitwise->turnOn('emergency', 'notice');
        $this->assertEquals(false, $bitwise->critical);
        $this->assertEquals(true, $bitwise->emergency);
        $this->assertEquals(true, $bitwise->notice);
        $this->assertEquals(true, $bitwise->always);
        $this->assertEquals(false, $bitwise->don);

        $bitwise->turnOn('don');
        $this->assertEquals(true, $bitwise->don);

        $bitwise->reset()->turnOn('don', 'notice');
        $this->assertEquals(false, $bitwise->critical);
        $this->assertEquals(false, $bitwise->emergency);
        $this->assertEquals(true, $bitwise->notice);
        $this->assertEquals(true, $bitwise->always);
        $this->assertEquals(true, $bitwise->don);
    }
}
