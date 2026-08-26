<?php

declare(strict_types=1);

/*
 * This file is part of the package k3n/tonictypes.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * Many thanks to Auth: B. Zagar / Maint: J. Pietschmann for sharing this extension – TYPO3 inspiring people to share!
 * Contact: support@tonictypes.com
 *
 */

namespace K3n\Tonictypes\Tests\Unit\Evaluation;

use K3n\Tonictypes\Evaluation\PhoneNumberEvaluation;
use PHPUnit\Framework\TestCase;

final class PhoneNumberEvaluationTest extends TestCase
{
    private PhoneNumberEvaluation $subject;

    protected function setUp(): void
    {
        $this->subject = new PhoneNumberEvaluation();
    }

    public function testValidPhoneIsNormalized(): void
    {
        $set = true;
        $result = $this->subject->evaluateFieldValue('+49  123  456789', '', $set);

        self::assertTrue($set);
        self::assertSame('+49 123 456789', $result);
    }

    public function testInvalidPhoneIsRejected(): void
    {
        $set = true;
        $result = $this->subject->evaluateFieldValue('abc', '', $set);

        self::assertFalse($set);
        self::assertSame('', $result);
    }

    public function testEmptyValueIsAllowed(): void
    {
        $set = true;
        $result = $this->subject->evaluateFieldValue('   ', '', $set);

        self::assertTrue($set);
        self::assertSame('', $result);
    }
}
