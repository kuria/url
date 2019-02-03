<?php declare(strict_types=1);

namespace Kuria\Url;

use Kuria\DevMeta\Test;
use Kuria\Url\Exception\IncompleteUrlException;
use Kuria\Url\Exception\InvalidUrlException;

class UrlTest extends Test
{
    /**
     * @dataProvider provideUrls
     */
    function testShouldParseAndRebuild(string $urlToParse, array $expectedMethodResults)
    {
        $expectedMethodResults += [
            'build' => $urlToParse,
            '__toString' => $urlToParse,
        ];

        $url = Url::parse($urlToParse);

        $this->assertInstanceOf(Url::class, $url);
        $this->assertUrlMethodResults($url, $expectedMethodResults);
    }

    function provideUrls()
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
                ],
            ],
            'absolute path only' => [
                '/foo',
                [
                    'getPath' => '/foo',
                ],
            ],
            'fragment only' => [
                '#foo-bar',
                [
                    'getFragment' => 'foo-bar',
                ],
            ],
            'query only' => [
                '?foo=bar&lorem=ipsum',
                [
                    'getQuery' => ['foo' => 'bar', 'lorem' => 'ipsum'],
                ],
            ],
            'protocol-relative host' => [
                '//example.com',
                [
                    'getHost' => 'example.com',
                ],
            ],
            'protocol-relative host with port' => [
                '//example.com:80',
                [
                    'getHost' => 'example.com',
                    'getFullHost' => 'example.com:80',
                    'getPort' => 80,
                    'buildRelative' => '',
                ],
            ],
            'host with protocol' => [
                'http://example.com',
                [
                    'getScheme' => 'http',
                    'getHost' => 'example.com',
                    'buildRelative' => '',
                ],
            ],
            'host with protocol and port' => [
                'http://example.com:80',
                [
                    'getScheme' => 'http',
                    'getHost' => 'example.com',
                    'getFullHost' => 'example.com:80',
                    'getPort' => 80,
                    'buildRelative' => '',
                ],
            ],
            'absolute url' => [
                'http://www.example.com/foo/bar.html',
                [
                    'getScheme' => 'http',
                    'getHost' => 'www.example.com',
                    'getPath' => '/foo/bar.html',
                    'buildRelative' => '/foo/bar.html',
                ],
            ],
            'url with all components' => [
                'https://example.com:88/foo/bar.html?foo=bar&baz%5B0%5D=zero&baz%5B1%5D=one#test',
                [
                    'getScheme' => 'https',
                    'getHost' => 'example.com',
                    'getFullHost' => 'example.com:88',
                    'getPort' => 88,
                    'getPath' => '/foo/bar.html',
                    'getQuery' => ['foo' => 'bar', 'baz' => ['zero', 'one']],
                    'getFragment' => 'test',
                    'buildRelative' => '/foo/bar.html?foo=bar&baz%5B0%5D=zero&baz%5B1%5D=one#test',
                ],
            ],
        ];
    }

    function testShouldThrowExceptionOnInvalidUrl()
    {
        $this->expectException(InvalidUrlException::class);

        Url::parse('//example.com:xx');
    }

    function testShouldGetQueryString()
    {
        $url = new Url();
        $url->setQuery(['foo' => 'bar', 'lorem' => 'ipsum']);

        $this->assertSame('foo=bar&lorem=ipsum', $url->getQueryString());
    }

    function testShouldManipulateUrl()
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
                    $url->setPath('foo/bar');

                    $expectedMethodResults['getPath'] = 'foo/bar';
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar';
                    $expectedMethodResults['buildRelative'] = 'foo/bar';
                    break;

                case 5:
                    $url->setPreferredFormat(Url::RELATIVE);

                    $expectedMethodResults['getPreferredFormat'] = Url::RELATIVE;
                    $expectedMethodResults['build'] = 'foo/bar';
                    break;

                case 6:
                    $url->setQuery(['param' => 'value']);
                    $url->setPreferredFormat(Url::ABSOLUTE);

                    $expectedMethodResults['getQuery'] = ['param' => 'value'];
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar?param=value';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=value';
                    unset($expectedMethodResults['getPreferredFormat']);
                    break;

                case 7:
                    $url->set('lorem', ['ipsum', 'dolor']);

                    $expectedMethodResults['getQuery'] = ['param' => 'value', 'lorem' => ['ipsum', 'dolor']];
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar'
                        . '?param=value&lorem%5B0%5D=ipsum&lorem%5B1%5D=dolor';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=value&lorem%5B0%5D=ipsum&lorem%5B1%5D=dolor';
                    break;

                case 8:
                    $url->remove('lorem');
                    $url->add(['param' => 'new-value']);

                    $expectedMethodResults['getQuery'] = ['param' => 'new-value'];
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar?param=new-value';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=new-value';
                    break;

                case 9:
                    $url->setFragment('test-fragment');

                    $expectedMethodResults['getFragment'] = 'test-fragment';
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar?param=new-value#test-fragment';
                    $expectedMethodResults['buildRelative'] = 'foo/bar?param=new-value#test-fragment';
                    break;

                case 10:
                    $url->removeAll();

                    $expectedMethodResults['getQuery'] = [];
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar#test-fragment';
                    $expectedMethodResults['buildRelative'] = 'foo/bar#test-fragment';
                    break;

                case 11:
                    $url->setFragment(null);

                    $expectedMethodResults['getFragment'] = null;
                    $expectedMethodResults['build'] = 'ftp://localhost:8080/foo/bar';
                    $expectedMethodResults['buildRelative'] = 'foo/bar';
                    break;

                case 12:
                    $url->setScheme(null);

                    $expectedMethodResults['getScheme'] = null;
                    $expectedMethodResults['build'] = '//localhost:8080/foo/bar';
                    break;

                case 13:
                    $url->setPort(null);

                    $expectedMethodResults['getPort'] = null;
                    $expectedMethodResults['getFullHost'] = 'localhost';
                    $expectedMethodResults['build'] = '//localhost/foo/bar';
                    break;

                case 14:
                    $url->setPath('');

                    $expectedMethodResults['getPath'] = '';
                    $expectedMethodResults['build'] = '//localhost';
                    $expectedMethodResults['buildRelative'] = '';
                    break;

                case 15:
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

    function testShouldThrowExceptionIfBuildingAbsoluteUrlWithoutHost()
    {
        $this->expectException(IncompleteUrlException::class);

        (new Url())->buildAbsolute();
    }

    function testShouldRetrieveQueryParameters()
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

    function testShouldSetDefaultPreferredFormatToAbsolute()
    {
        $this->assertSame(Url::ABSOLUTE, (new Url())->getPreferredFormat());
        $this->assertSame(Url::ABSOLUTE, Url::parse('foo')->getPreferredFormat());
    }

    function testShouldSetPreferredFormatViaConstructor()
    {
        $url = new Url(null, null, null, '', [], null, Url::RELATIVE);

        $this->assertSame(Url::RELATIVE, $url->getPreferredFormat());
    }

    function testShouldSetPreferredFormatViaParse()
    {
        $url = Url::parse('foo', Url::RELATIVE);

        $this->assertSame(Url::RELATIVE, $url->getPreferredFormat());
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
            'getPath' => '',
            'hasPath' => ($expectedMethodResults['getPath'] ?? '') !== '',
            'getQuery' => [],
            'hasQuery' => !empty($expectedMethodResults['getQuery']),
            'getFragment' => null,
            'hasFragment' => isset($expectedMethodResults['getFragment']),
            'getPreferredFormat' => Url::ABSOLUTE,
        ];

        foreach ($expectedMethodResults as $method => $expectedValue) {
            $this->assertSame(
                $expectedValue,
                $url->{$method}(),
                sprintf('Expected Url::%s() to yield the expected value', $method)
            );
        }
    }
}
