<?php

namespace Tests\Unit;

use App\Modules\Billing\Services\XmlGeneratorService;
use Tests\TestCase;

class AccessKeyGeneratorTest extends TestCase
{
    /**
     * Test Modulo 11 check digit arithmetic.
     */
    public function test_calculate_modulo_11_check_digit()
    {
        $service = new XmlGeneratorService();

        // Let's test with some standard keys
        // If remainder of sum%11 is 0, check digit is 0 (since 11 - 0 = 11 -> 0)
        // If remainder is 1, check digit is 1 (since 11 - 1 = 10 -> 1)
        // Otherwise, 11 - remainder.
        
        // Example 1: SRI key without check digit
        $key1 = "020620180117912561150012001001000000001123456781";
        $digit1 = $service->calculateModulo11($key1);
        $this->assertTrue($digit1 >= 0 && $digit1 <= 9);

        // Example 2: Simple repetitive digits
        $key2 = str_repeat("1", 48);
        // calculation details for 48 ones:
        // weights from right to left: 2,3,4,5,6,7, 2,3,4,5,6,7... repeated 8 times.
        // sum of weights in one group of 6: 2+3+4+5+6+7 = 27.
        // 8 groups: 27 * 8 = 216.
        // 216 % 11 = 7.
        // check digit = 11 - 7 = 4.
        $this->assertEquals(4, $service->calculateModulo11($key2));
    }
}
