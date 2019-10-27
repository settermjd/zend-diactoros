<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Response;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\DownloadResponse;

class DownloadResponseTest extends TestCase
{
    /**
     * @dataProvider overrideDownloadHeadersDataProvider
     * @param array $headers
     * @param bool$doesOverride
     */
    public function testOverridesDownloadHeadersDetectsAttemptToOverrideHeaders($headers, $doesOverride)
    {
        $response = new DownloadResponse();
        $this->assertEquals($doesOverride, $response->overridesDownloadHeaders($headers));
    }

    public function overrideDownloadHeadersDataProvider()
    {
        return [
            [
                [
                    'cache-control' => ['must-revalidate'],
                    'content-description' => ['File Transfer'],
                    'content-transfer-encoding' => ['Binary'],
                    'content-type' => ['text/csv; charset=utf-8'],
                    'expires' => ['0'],
                    'pragma' => ['Public'],
                ],
                true
            ],
            [
                [],
                false
            ],
            [
                [
                    'accept' => 'text/html',
                    'authorization' => 'Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==',
                    'cookie' => '$Version=1; Skin=new;',
                    'host' => 'en.wikipedia.org:8080',
                    'origin' => 'https://www.example.org',
                ],
                false
            ]
        ];
    }
}