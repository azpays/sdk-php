<?php

declare(strict_types=1);

namespace AzPays\Tests;

use AzPays\Config;
use AzPays\Exceptions\BadRequestException;
use AzPays\Exceptions\ForbiddenException;
use AzPays\Exceptions\NotFoundException;
use AzPays\Exceptions\RateLimitedException;
use AzPays\Exceptions\UnauthorizedException;
use AzPays\Http\Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    public function testStandardEnvelopeUnpacking(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'ok' => true,
                'msg' => 'success',
                'data' => [
                    'id' => 'pay_123',
                    'fiat_amount' => 50.0,
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new Config('az_live_testkey', null, 30, 1, false, null, $client);
        $transport = new Transport($config);

        $res = $transport->get('/v1/payments/pay_123');

        $this->assertIsArray($res);
        $this->assertSame('pay_123', $res['id']);
        $this->assertEquals(50.0, $res['fiat_amount']);
    }

    public function testPaginatedEnvelope(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'message' => 'list retrieved',
                'data' => [
                    ['id' => 'pay_1'],
                    ['id' => 'pay_2'],
                ],
                'pagination' => [
                    'total' => 2,
                    'per_page' => 15,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new Config('az_live_testkey', null, 30, 1, false, null, $client);
        $transport = new Transport($config);

        $res = $transport->getPaginated('/v1/payments');

        $this->assertIsArray($res);
        $this->assertCount(2, $res['data']);
        $this->assertSame(2, $res['pagination']['total']);
    }

    public function test404ThrowsNotFoundException(): void
    {
        $mock = new MockHandler([
            new Response(404, ['X-Request-Id' => 'req_abc'], json_encode([
                'ok' => false,
                'msg' => 'Payment not found',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new Config('az_live_testkey', null, 30, 0, false, null, $client);
        $transport = new Transport($config);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Payment not found');

        $transport->get('/v1/payments/nonexistent');
    }

    public function test401ThrowsUnauthorizedException(): void
    {
        $mock = new MockHandler([
            new Response(401, [], json_encode([
                'ok' => false,
                'msg' => 'Invalid API key',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new Config('az_live_testkey', null, 30, 0, false, null, $client);
        $transport = new Transport($config);

        $this->expectException(UnauthorizedException::class);
        $transport->get('/v1/merchants/me');
    }

    public function test403ThrowsForbiddenException(): void
    {
        $mock = new MockHandler([
            new Response(403, [], json_encode([
                'ok' => false,
                'msg' => 'Forbidden resource',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new Config('az_live_testkey', null, 30, 0, false, null, $client);
        $transport = new Transport($config);

        $this->expectException(ForbiddenException::class);
        $transport->get('/v1/merchants/forbidden');
    }

    public function test400ThrowsBadRequestException(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode([
                'ok' => false,
                'msg' => 'Missing required field: fiat_amount',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new Config('az_live_testkey', null, 30, 0, false, null, $client);
        $transport = new Transport($config);

        $this->expectException(BadRequestException::class);
        $transport->post('/v1/payments', []);
    }

    public function testRetryOn500ThenSuccess(): void
    {
        $mock = new MockHandler([
            new Response(500, [], json_encode(['ok' => false, 'msg' => 'Internal Server Error'])),
            new Response(200, [], json_encode(['ok' => true, 'data' => ['success' => true]])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // maxRetries = 2
        $config = new Config('az_live_testkey', null, 30, 2, false, null, $client);
        $transport = new Transport($config);

        $res = $transport->get('/v1/test');
        $this->assertSame(['success' => true], $res);
    }
}
