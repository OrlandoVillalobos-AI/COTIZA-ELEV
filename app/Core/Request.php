<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $params;
    private array $headers;
    private ?string $rawBody;

    public function __construct(
        string $method = 'GET',
        string $path = '/',
        array $params = [],
        array $headers = [],
        ?string $rawBody = null
    ) {
        $this->method = strtoupper($method);
        $this->path = '/' . trim(parse_url($path, PHP_URL_PATH) ?? '/', '/');
        if ($this->path !== '/' && str_ends_with($this->path, '/')) {
            $this->path = rtrim($this->path, '/');
        }
        $this->params = $params;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->rawBody = $rawBody;
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $headers = [];

        foreach ($_SERVER as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = (string)$val;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headers[str_replace('_', '-', strtolower($key))] = (string)$val;
            }
        }

        $rawBody = file_get_contents('php://input');
        $params = array_merge($_GET, $_POST);

        if (!empty($rawBody)) {
            $json = json_decode($rawBody, true);
            if (is_array($json)) {
                $params = array_merge($params, $json);
            }
        }

        return new self($method, $uri, $params, $headers, $rawBody ?: null);
    }

    public static function create(
        string $method,
        string $path,
        array $params = [],
        array $headers = [],
        ?string $rawBody = null
    ): self {
        return new self($method, $path, $params, $headers, $rawBody);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->params;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isJson(): bool
    {
        $ct = $this->getHeader('content-type', '');
        return str_contains($ct, 'application/json');
    }

    public function getJson(): ?array
    {
        if ($this->rawBody === null) {
            return null;
        }
        $data = json_decode($this->rawBody, true);
        return is_array($data) ? $data : null;
    }
}
