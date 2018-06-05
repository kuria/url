<?php declare(strict_types=1);

namespace Kuria\Url;

use Kuria\Url\Exception\InvalidUrlException;

final class Url
{
    /** @var static|null */
    private static $current;
    /** @var string */
    private static $defaultCurrentHost = 'localhost';

    /** @var string|null */
    private $scheme;
    /** @var string|null */
    private $host;
    /** @var int|null */
    private $port;
    /** @var string|null */
    private $user;
    /** @var string|null */
    private $password;
    /** @var string */
    private $path;
    /** @var array */
    private $query;
    /** @var string|null */
    private $fragment;

    function __construct(
        ?string $scheme = null,
        ?string $host = null,
        ?int $port = null,
        ?string $user = null,
        ?string $password = null,
        string $path = '',
        array $query = [],
        ?string $fragment = null
    ) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
    }

    function __toString(): string
    {
        return $this->build();
    }

    /**
     * Parse an URL
     *
     * @throws InvalidUrlException if the URL is invalid
     * @return static
     */
    static function parse(string $url)
    {
        $components = parse_url($url);

        if ($components === false) {
            throw new InvalidUrlException(sprintf('The given URL "%s" is invalid', $url));
        }

        $query = [];

        if (isset($components['query'])) {
            parse_str($components['query'], $query);
        }

        return new static(
            $components['scheme'] ?? null,
            $components['host'] ?? null,
            $components['port'] ?? null,
            $components['user'] ?? null,
            $components['pass'] ?? null,
            $components['path'] ?? '',
            $query,
            $components['fragment'] ?? null
        );
    }

    /**
     * Get current URL
     *
     * If no URL has been specified using Url::setCurrent(), it will be determined
     * using $_SERVER properties. The result is cached internally.
     *
     * Returns a new instance each time.
     *
     * @see Url::setCurrent() to specify current URL
     * @see Url::clearCurrentUrlCache() to clear the internal cache
     * @return static
     */
    static function current()
    {
        if (self::$current === null) {
            self::$current = self::parse(sprintf(
                '%s://%s%s',
                !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http',
                $_SERVER['HTTP_HOST'] ?? self::$defaultCurrentHost,
                self::getCurrentRequestUri()
            ));
        }

        return clone self::$current;
    }

    /**
     * Specify current URL
     */
    static function setCurrent(self $url): void
    {
        self::$current = $url;
    }

    /**
     * Set a default host to be used when determining current URL
     */
    static function setDefaultCurrentHost(string $host): void
    {
        self::$defaultCurrentHost = $host;
        self::clearCurrentUrlCache();
    }

    /**
     * Clear the internal current URL cache
     *
     * @see Url::current()
     */
    static function clearCurrentUrlCache(): void
    {
        self::$current = null;
    }

    function getScheme(): ?string
    {
        return $this->scheme;
    }

    function setScheme(?string $scheme): void
    {
        $this->scheme = $scheme;
    }

    function hasScheme(): bool
    {
        return $this->scheme !== null;
    }

    function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Get host name, including the port, if defined
     *
     * E.g. example.com:8080
     */
    function getFullHost(): ?string
    {
        if ($this->host === null) {
            return null;
        }

        $fullHost = $this->host;

        if ($this->port !== null) {
            $fullHost .= ':' . $this->port;
        }

        return $fullHost;
    }

    function setHost(?string $host): void
    {
        $this->host = $host;
    }

    function hasHost(): bool
    {
        return $this->host !== null;
    }

    function getPort(): ?int
    {
        return $this->port;
    }

    function setPort(?int $port): void
    {
        $this->port = $port;
    }

    function hasPort(): bool
    {
        return $this->port !== null;
    }

    function getUser(): ?string
    {
        return $this->user;
    }

    function setUser(?string $user): void
    {
        $this->user = $user;
    }

    function hasUser(): bool
    {
        return $this->user !== null;
    }

    function getPassword(): ?string
    {
        return $this->password;
    }

    function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    function hasPassword(): bool
    {
        return $this->password !== null;
    }

    function getPath(): string
    {
        return $this->path;
    }

    function setPath(string $path): void
    {
        $this->path = $path;
    }

    function hasPath(): bool
    {
        return $this->path !== '';
    }

    function getQuery(): array
    {
        return $this->query;
    }

    function getQueryString(): string
    {
        return http_build_query($this->query, '', '&');
    }

    function setQuery(array $query): void
    {
        $this->query = $query;
    }

    function hasQuery(): bool
    {
        return !empty($this->query);
    }

    function getFragment(): ?string
    {
        return $this->fragment;
    }

    function setFragment(?string $fragment): void
    {
        $this->fragment = $fragment;
    }

    function hasFragment(): bool
    {
        return $this->fragment !== null;
    }

    /**
     * See whether a query parameter is defined
     *
     * @param string|int $parameter
     */
    function has($parameter): bool
    {
        return key_exists($parameter, $this->query);
    }

    /**
     * Attempt to retrieve a query parameter value
     *
     * Returns NULL if the query parameter is not defined.
     *
     * @param string|int $parameter
     * @return mixed
     */
    function get($parameter)
    {
        return $this->query[$parameter] ?? null;
    }

    /**
     * Set query parameter
     *
     * @param string|int $parameter
     * @param mixed $value
     */
    function set($parameter, $value): void
    {
        $this->query[$parameter] = $value;
    }

    /**
     * Add multiple query parameters
     *
     * Already defined parameters with the same key will be overriden.
     */
    function add(array $parameters): void
    {
        foreach ($parameters as $parameter => $value) {
            $this->query[$parameter] = $value;
        }
    }

    /**
     * Remove a query parameter
     *
     * @param string|int $parameter
     */
    function remove($parameter): void
    {
        unset($this->query[$parameter]);
    }

    /**
     * Remove all query parameters
     */
    function removeAll(): void
    {
        $this->query = [];
    }

    /**
     * Build an absolute or relative URL, depending on whether the host is defined
     */
    function build(): string
    {
        if ($this->host !== null) {
            return $this->buildAbsolute();
        } else {
            return $this->buildRelative();
        }
    }

    /**
     * Build an absolute URL
     *
     * If no host is specified, uses the current host.
     */
    function buildAbsolute(): string
    {
        $output = '';

        if ($this->host === null) {
            $current = self::current();
            $fullHost = $current->getFullHost();
            $scheme = $this->scheme ?? $current->getScheme();
        } else {
            $fullHost = $this->getFullHost();
            $scheme = $this->scheme;
        }

        // scheme
        if ($scheme !== null) {
            $output .= $scheme;
            $output .= '://';
        } else {
            // protocol-relative
            $output .= '//';
        }

        // auth
        if ($this->user !== null) {
            $output .= $this->user;
        }

        if ($this->password !== null) {
            $output .= ':';
            $output .= $this->password;
        }

        if ($this->user !== null || $this->password !== null) {
            $output .= '@';
        }

        // host, port
        $output .= $fullHost;

        // ensure a forward slash between host and a non-empty path
        if ($this->path !== '' && $this->path[0] !== '/') {
            $output .= '/';
        }

        // path, query, fragment
        $output .= $this->buildRelative();

        return $output;
    }

    /**
     * Build a relative URL
     */
    function buildRelative(): string
    {
        $output = '';

        // path
        $output .= $this->path;

        // query
        if ($this->query) {
            $output .= '?';
            $output .= $this->getQueryString();
        }

        // fragment
        if ($this->fragment !== null) {
            $output .= '#';
            $output .= $this->fragment;
        }

        return $output;
    }

    private static function getCurrentRequestUri(): string
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }

        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            return $_SERVER['HTTP_X_REWRITE_URL']; // ISAPI_Rewrite 3.x
        }

        if (isset($_SERVER['HTTP_REQUEST_URI'])) {
            return $_SERVER['HTTP_REQUEST_URI']; // ISAPI_Rewrite 2.x
        }

        $requestUri = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';

        if (!empty($_SERVER['QUERY_STRING'])) {
            $requestUri .= '?' . $_SERVER['QUERY_STRING'];
        }

        if ($requestUri !== '' && $requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }

        return $requestUri;
    }
}
