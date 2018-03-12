<?php declare(strict_types=1);

namespace Kuria\Url;

use Kuria\Url\Exception\InvalidUrlException;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @dataProvider provideUrls
     */
    function testParseAndRebuild(string $urlToParse, array $expectedMethodResults)
    {
        $expectedMethodResults += [
            'build' => $urlToParse,
            '__toString' => $urlToParse,
        ];

        $url = Url::parse($urlToParse);

        $this->assertInstanceOf(Url::class, $url);
        $this->assertUrlMethodResults($url, $expectedMethodResults);
    }

    function provideUrls(): array
    {
        return [
            // urlToParse, expectedMethodResults
            'empty string' => [
                '',
                [
                    'getPath' => '',
                ],
            ],
            'relative path only' => [
                'foo',
                [
                    'getPath' => 'foo',
                ]
            ],
            'absolute path only' => [
                '/foo',
                [
                    'getPath' => '/foo',
                ]
            ],
            'fragment only' => [
                '#foo-bar',
                [
                    'getFragment' => 'foo-bar',
                ]
            ],
            'query only' => [
                '?foo=bar&lorem=ipsum',
                [
                    'getQuery' => ['foo' => 'bar', 'lorem' => 'ipsum'],
                ]
            ],
            'protocol-relative host' => [
                '//example.com',
                [
                    'getHost' => 'example.com',
                ]
            ],
            'protocol-relative host with port' => [
                '//example.com:80',
                [
                    'getHost' => 'example.com',
                    'getFullHost' => 'example.com:80',
                    'getPort' => 80,
                    'buildRelative' => '',
                ]
            ],
            'protocol-relative host with auth' => [
                '//user:pass@example.com',
                [
                    'getUser' => 'user',
                    'getPassword' => 'pass',
                    'getHost' => 'example.com',
                    'buildRelative' => '',
                ]
            ],
            'host with protocol' => [
                'http://example.com',
                [
                    'getScheme' => 'http',
                    'getHost' => 'example.com',
                    'buildRelative' => '',
                ]
            ],
            'host with protocol and port' => [
                'http://example.com:80',
                [
                    'getScheme' => 'http',
                    'getHost' => 'example.com',
                    'getFullHost' => 'example.com:80',
                    'getPort' => 80,
                    'buildRelative' => '',
                ]
            ],
            'host with protocol and auth' => [
                'http://user:pass@example.com',
                [
                    'getUser' => 'user',
                    'getPassword' => 'pass',
                    'getScheme' => 'http',
                    'getHost' => 'example.com',
                    'buildRelative' => '',
                ]
            ],
            'absolute url' => [
                'http://www.example.com/foo/bar.html',
                [
                    'getScheme' => 'http',
                    'getHost' => 'www.example.com',
                    'getPath' => '/foo/bar.html',
                    'buildRelative' => '/foo/bar.html',
                ]
            ],
            'url with all components' => [
                'https://user:pass@example.com:88/foo/bar.html?foo=bar&baz%5B0%5D=zero&baz%5B1%5D=one#test',
                [
                    'getScheme' => 'https',
                    'getUser' => 'user',
                    'getPassword' => 'pass',
                    'getHost' => 'example.com',
                    'getFullHost' => 'example.com:88',
                    'getPort' => 88,
                    'getPath' => '/foo/bar.html',
                    'getQuery' => ['foo' => 'bar', 'baz' => ['zero', 'one']],
                    'getFragment' => 'test',
                    'buildRelative' => '/foo/bar.html?foo=bar&baz%5B0%5D=zero&baz%5B1%5D=one#test',
                ]
            ],
        ];
    }

    function testExceptionOnInvalidUrl()
    {
        $this->expectException(InvalidUrlException::class);

        Url::parse('//example.com:xx');
    }

    function testQueryString()
    {
        $url = new Url();
        $url->setQuery(['foo' => 'bar', 'lorem' => 'ipsum']);

        $this->assertSame('foo=bar&lorem=ipsum', $url->getQueryString());
    }

    function testUrlManipulation()
    {
        $url = new Url();
        $step = 0;
        $expectedMethodResults = [];

        // assert initial state
        $this->assertUrlMethodResults($url, $expectedMethodResults);

        // iteratively modify the URL and assert the results
        do {
            switch (++$step) {
                case 1:
                    $url->setHost('localhost');

                    $expectedMethodResults['getHost'] = 'localhost';
                    $expectedMethodResults['build'] = '//localhost';
                    break;

                case 2:
                    $url->setPort(8080);

                    $expectedMethodResults['getPort'] = 8080;
                    $expectedMethodResults['getFullHost'] = 'localhost:8080';
                    $expectedMethodResults['build'] = '//localhost:8080';
                    break;

                case 3:
                    $url->setScheme('ftp');

                    $expectedMethodResults['getScheme'] = 'ftp';
                    $expectedMethodResults['build'] = 'ftp://localhost:8080';
                    break;

                case 4:
                    $url->setUser('john.smith');
                    $url->setPassword('123456');

                    $expectedMethodResults['getUser'] = 'john.smith';
                    $expectedMethodResults['getPassword'] = '123456';
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080';
                    break;

                case 5:
                    $url->setPath('foo/bar');

                    $expectedMethodResults['getPath'] = 'foo/bar';
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080/foo/bar';
                    $expectedMethodResults['buildRelative'] = 'foo/bar';
                    break;

                case 6:
                    $url->setQuery(['param' => 'value']);

                    $expectedMethodResults['getQuery'] = ['param' => 'value'];
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080/foo/bar?param=value';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=value';
                    break;

                case 7:
                    $url->set('lorem', ['ipsum', 'dolor']);

                    $expectedMethodResults['getQuery'] = ['param' => 'value', 'lorem' => ['ipsum', 'dolor']];
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080/foo/bar'
                        . '?param=value&lorem%5B0%5D=ipsum&lorem%5B1%5D=dolor';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=value&lorem%5B0%5D=ipsum&lorem%5B1%5D=dolor';
                    break;

                case 8:
                    $url->remove('lorem');
                    $url->add(['param' => 'new-value']);

                    $expectedMethodResults['getQuery'] = ['param' => 'new-value'];
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080/foo/bar?param=new-value';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=new-value';
                    break;

                case 9:
                    $url->setFragment('test-fragment');

                    $expectedMethodResults['getFragment'] = 'test-fragment';
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080/foo/bar?param=new-value#test-fragment';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=new-value#test-fragment';
                    break;

                case 10:
                    $url->removeAll();

                    $expectedMethodResults['getQuery'] = [];
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080/foo/bar#test-fragment';
                    $expectedMethodResults['buildRelative'] = 'foo/bar#test-fragment';
                    break;

                case 11:
                    $url->setFragment(null);

                    $expectedMethodResults['getFragment'] = null;
                    $expectedMethodResults['build'] = 'ftp://john.smith:123456@localhost:8080/foo/bar';
                    $expectedMethodResults['buildRelative'] = 'foo/bar';
                    break;

                case 12:
                    $url->setUser(null);
                    $url->setPassword(null);

                    $expectedMethodResults['getUser'] = null;
                    $expectedMethodResults['getPassword'] = null;
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar';
                    break;

                case 13:
                    $url->setScheme(null);

                    $expectedMethodResults['getScheme'] = null;
                    $expectedMethodResults['build'] = '//localhost:8080/foo/bar';
                    break;

                case 14:
                    $url->setPort(null);

                    $expectedMethodResults['getPort'] = null;
                    $expectedMethodResults['getFullHost'] = 'localhost';
                    $expectedMethodResults['build'] = '//localhost/foo/bar';
                    break;

                case 15:
                    $url->setPath('');

                    $expectedMethodResults['getPath'] = '';
                    $expectedMethodResults['build'] = '//localhost';
                    $expectedMethodResults['buildRelative'] = '';
                    break;

                case 16:
                    $url->setHost(null);

                    $expectedMethodResults['getHost'] = null;
                    $expectedMethodResults['getFullHost'] = null;
                    $expectedMethodResults['build'] = '';
                    break;

                default:
                    break 2;
            }

            $this->assertUrlMethodResults($url, $expectedMethodResults);
        } while (true);
    }

    function testBuildAbsoluteShouldUseCurrentHostIfNoneDefined()
    {
        Url::clearCurrentUrlCache();

        $this->setServerProperties(['HTTP_HOST' => 'current-host']);

        $url = new Url();
        $url->setPath('/bar');

        $this->assertSame('http://current-host/bar', $url->buildAbsolute());
    }

    function testBuildAbsoluteShouldUseCurrentHostAndPortIfNoneDefinedWithSpecifiedScheme()
    {
        Url::clearCurrentUrlCache();

        $this->setServerProperties(['HTTP_HOST' => 'example:8080']);

        $url = new Url();
        $url->setPath('/bar');
        $url->setScheme('ftp');

        $this->assertSame('ftp://example:8080/bar', $url->buildAbsolute());
    }

    function testQueryParameterRetrieval()
    {
        $url = new Url();
        $url->setQuery(['foo' => 'bar', 'lorem' => 'ipsum', 'null-param' => null]);

        $this->assertFalse($url->has('nonexistent'));
        $this->assertTrue($url->has('foo'));
        $this->assertTrue($url->has('lorem'));
        $this->assertTrue($url->has('null-param'));

        $this->assertSame('bar', $url->get('foo'));
        $this->assertSame('ipsum', $url->get('lorem'));
        $this->assertNull($url->get('nonexistent'));
        $this->assertNull($url->get('null-param'));
    }

    /**
     * @dataProvider provideServerProperties
     */
    function testDeterminingCurrentUrl(array $serverProperties, string $expectedUrl)
    {
        Url::clearCurrentUrlCache();

        $this->setServerProperties($serverProperties);

        $url = Url::current();

        $this->assertSame($expectedUrl, $url->build());
    }

    function provideServerProperties(): array
    {
        return [
            // serverProperties, expectedUrl
            'standard' => [
                [
                    'REQUEST_URI' => '/path?foo=1',
                    'HTTP_HOST' => 'example.com',
                ],
                'http://example.com/path?foo=1',
            ],
            'https on' => [
                [
                    'REQUEST_URI' => '/path?foo=2',
                    'HTTP_HOST' => 'example.com',
                    'HTTPS' => 'on',
                ],
                'https://example.com/path?foo=2',
            ],
            'https off' => [
                [
                    'REQUEST_URI' => '/path?foo=3',
                    'HTTP_HOST' => 'example.com',
                    'HTTPS' => 'off',
                ],
                'http://example.com/path?foo=3',
            ],
            'no host' => [
                [
                    'REQUEST_URI' => '/path?foo=4',
                ],
                'http://localhost/path?foo=4',
            ],
            'ISAPI_Rewrite 3.x' => [
                [
                    'HTTP_X_REWRITE_URL' => '/path?foo=5',
                    'HTTP_HOST' => 'example.com',
                ],
                'http://example.com/path?foo=5',
            ],
            'ISAPI_Rewrite 2.x' => [
                [
                    'HTTP_REQUEST_URI' => '/path?foo=6',
                    'HTTP_HOST' => 'example.com',
                ],
                'http://example.com/path?foo=6',
            ],
            'script name fallback' => [
                [
                    'SCRIPT_NAME' => 'script.php',
                    'HTTP_HOST' => 'example.com',
                ],
                'http://example.com/script.php',
            ],
            'script name fallback with query string and path' => [
                [
                    'SCRIPT_NAME' => '/path/script.php',
                    'HTTP_HOST' => 'example.com',
                    'QUERY_STRING' => 'foo=7',
                ],
                'http://example.com/path/script.php?foo=7',
            ],
            'php self fallback' => [
                [
                    'PHP_SELF' => 'script.php',
                    'HTTP_HOST' => 'example.com',
                ],
                'http://example.com/script.php',
            ],
            'php self fallback with query string and path' => [
                [
                    'PHP_SELF' => '/path/script.php',
                    'HTTP_HOST' => 'example.com',
                    'QUERY_STRING' => 'foo=8',
                ],
                'http://example.com/path/script.php?foo=8',
            ],
            'all env variables missing' => [
                [],
                'http://localhost',
            ],
        ];
    }

    function testSetDefaultCurrentHost()
    {
        Url::clearCurrentUrlCache();
        Url::setDefaultCurrentHost('custom-default-host');

        $_SERVER['REQUEST_URI'] = '/path';
        unset($_SERVER['HTTP_HOST']);

        $this->assertSame('http://custom-default-host/path', Url::current()->build());
    }

    function testSetDefaultCurrentHostShouldClearCurentUrlCache()
    {
        Url::setDefaultCurrentHost('foo');
        $this->assertSame('foo', Url::current()->getHost());

        Url::setDefaultCurrentHost('bar');
        $this->assertSame('bar', Url::current()->getHost());
    }

    private function assertUrlMethodResults(Url $url, array $expectedMethodResults)
    {
        $expectedMethodResults += [
            'getScheme' => null,
            'hasScheme' => isset($expectedMethodResults['getScheme']),
            'getHost' => null,
            'hasHost' => isset($expectedMethodResults['getHost']),
            'getFullHost' => $expectedMethodResults['getHost'] ?? null,
            'getPort' => null,
            'hasPort' => isset($expectedMethodResults['getPort']),
            'getUser' => null,
            'hasUser' => isset($expectedMethodResults['getUser']),
            'getPassword' => null,
            'hasPassword' => isset($expectedMethodResults['getPassword']),
            'getPath' => '',
            'hasPath' => ($expectedMethodResults['getPath'] ?? '') !== '',
            'getQuery' => [],
            'hasQuery' => !empty($expectedMethodResults['getQuery']),
            'getFragment' => null,
            'hasFragment' => isset($expectedMethodResults['getFragment']),
        ];

        foreach ($expectedMethodResults as $method => $expectedValue) {
            $this->assertSame(
                $expectedValue,
                $url->{$method}(),
                sprintf('Expected Url::%s() to yield the expected value', $method)
            );
        }
    }

    private function setServerProperties(array $serverProperties = [])
    {
        $_SERVER = $serverProperties
            + [
                'HTTPS' => null,
                'QUERY_STRING' => null,
                'HTTP_HOST' => null,
                'REQUEST_URI' => null,
                'HTTP_X_REWRITE_URL' => null,
                'HTTP_REQUEST_URI' => null,
                'SCRIPT_NAME' => null,
                'PHP_SELF' => null,
            ]
            + $_SERVER;
    }
}
