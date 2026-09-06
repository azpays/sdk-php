<?php

declare(strict_types=1);

namespace AzPays\Tests;

use AzPays\Exceptions\ApiException;
use AzPays\Exceptions\BadRequestException;
use AzPays\Exceptions\ForbiddenException;
use AzPays\Exceptions\NotFoundException;
use AzPays\Exceptions\RateLimitedException;
use AzPays\Exceptions\SignatureVerificationException;
use AzPays\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testApiExceptionMethods(): void
    {
        $ex = new ApiException('Test error', 404, 'req_123', ['error' => 'not found']);

        $this->assertSame(404, $ex->getStatusCode());
        $this->assertSame('req_123', $ex->getRequestId());
        $this->assertSame(['error' => 'not found'], $ex->getResponseBody());
        $this->assertTrue($ex->isNotFound());
        $this->assertFalse($ex->isUnauthorized());
        $this->assertFalse($ex->isForbidden());
        $this->assertFalse($ex->isRateLimited());
        $this->assertFalse($ex->isBadRequest());
        $this->assertStringContainsString('req_123', $ex->getMessage());
    }

    public function testApiExceptionPredicates(): void
    {
        $badReq = new BadRequestException('Bad request', 400);
        $this->assertTrue($badReq->isBadRequest());

        $unauth = new UnauthorizedException('Unauthorized', 401);
        $this->assertTrue($unauth->isUnauthorized());

        $forbid = new ForbiddenException('Forbidden', 403);
        $this->assertTrue($forbid->isForbidden());

        $rate = new RateLimitedException('Rate limited', 429, 'req_rate', null, 30);
        $this->assertTrue($rate->isRateLimited());
        $this->assertSame(30, $rate->getRetryAfter());

        $sig = new SignatureVerificationException('Sig fail');
        $this->assertSame('Sig fail', $sig->getMessage());
    }
}
