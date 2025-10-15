<?php

declare(strict_types=1);

use peels\validate\Validate;

final class anotherTest extends \unitTestHelper
{
    private $validate;

    protected function setUp(): void
    {
        $this->validate = Validate::getInstance([
            'throwExceptionOnFailure' => true,
        ]);
    }

    public function testOne(): void
    {
        $this->assertEquals(123, $this->validate->value('123', 'toInteger|castInteger|isGreaterThan[100]|isLessThan[999]|isInteger'));
    }

    public function testTwo(): void
    {
        $password = 'DefaultPassword#1';

        $hash = $this->validate->value($password, 'toPasswordHash');

        $this->assertEquals($password, $this->validate->value($password, 'passwordVerify[' . $hash . ']'));
    }
}
