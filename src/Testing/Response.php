<?php

declare(strict_types=1);

namespace Bow\Testing;

use InvalidArgumentException;
use Bow\Http\Client\Response as HttpClientResponse;

class Response
{
    /**
     * The http http_response
     *
     * @var HttpClientResponse
     */
    private HttpClientResponse $http_response;

    /**
     * The http_response content
     *
     * @var string
     */
    private string $content;

    /**
     * Behovior constructor.
     *
     * @param HttpClientResponse $http_response
     */
    public function __construct(HttpClientResponse $http_response)
    {
        $this->http_response = $http_response;

        $this->content = $http_response->getContent();
    }

    /**
     * Check if the content is json format
     *
     * @param string $message
     * @return Response
     */
    public function assertJson(string $message = ''): Response
    {
        Assert::assertJson(json_encode($this->content), $message);

        return $this;
    }

    /**
     * Check if the content is json format and the parsed data is
     * some to the content
     *
     * @param array $data
     * @param string $message
     * @return Response
     */
    public function assertExactJson(array $data, string $message = ''): Response
    {
        $response = $this->toJson(true);

        foreach ($response as $key => $value) {
            Assert::assertArrayHasKey($key, $data, $message);
            Assert::assertEquals($value, $data[$key], $message);
        }

        return $this;
    }

    /**
     * Check if the content is some of parse data
     *
     * @param string $data
     * @param string $message
     *
     * @return Response
     */
    public function assertContainsExactText(string $data, string $message = ''): Response
    {
        Assert::assertEquals($this->content, $data, $message);

        return $this;
    }

    /**
     * Check if the header exists
     *
     * @param string $header
     * @param string $message
     *
     * @return Response
     */
    public function assertHeader(string $header, string $message = ''): Response
    {
        Assert::assertArrayHasKey($header, $this->http_response->getHeaders(), $message);

        return $this;
    }

    /**
     * Check if the content is array format
     *
     * @param string $message
     *
     * @return Response
     */
    public function assertArray(string $message = ''): Response
    {
        Assert::assertTrue(is_array($this->http_response->toArray()), $message);

        return $this;
    }

    /**
     * Check the content type
     *
     * @param string $content_type
     * @param string $message
     *
     * @return Response
     */
    public function assertContentType(string $content_type, string $message = ''): Response
    {
        $type = $this->http_response->getContentType();

        Assert::assertEquals(
            $content_type,
            current(preg_split('/;(\s+)?/', $type)),
            $message
        );

        return $this;
    }

    /**
     * Check if the content type is application/json
     *
     * @param string $message
     *
     * @return Response
     */
    public function assertContentTypeJson(string $message = ''): Response
    {
        $this->assertContentType('application/json', $message);

        return $this;
    }

    /**
     * Check if the content type is text/plain
     *
     * @param string $message
     *
     * @return Response
     */
    public function assertContentTypeText(string $message = ''): Response
    {
        $this->assertContentType('text/plain', $message);

        return $this;
    }

    /**
     * Check if the content type is text/html
     *
     * @param string $message
     *
     * @return Response
     */
    public function assertContentTypeHtml(string $message = ''): Response
    {
        $this->assertContentType('text/html', $message);

        return $this;
    }

    /**
     * Check if the content type is text/xml
     *
     * @param string $message
     *
     * @return Response
     */
    public function assertContentTypeXml(string $message = ''): Response
    {
        $this->assertContentType('text/xml', $message);

        return $this;
    }

    /**
     * Check the status code
     *
     * @param int $code
     * @param string $message
     * @return Response
     */
    public function assertStatus(int $code, string $message = ''): Response
    {
        Assert::assertEquals($this->http_response->getCode(), $code, $message);

        return $this;
    }

    /**
     * @param string $key
     * @param string $message
     * @return Response
     */
    public function assertKeyExists(string $key, string $message = ''): Response
    {
        $data = $this->http_response->toArray();

        Assert::assertTrue(isset($data[$key]), $message);

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $message
     *
     * @return Response
     */
    public function assertKeyMatchValue(string|int $key, mixed $value, string $message = ''): Response
    {
        $data = json_encode($this->content);

        if (isset($data[$key])) {
            Assert::assertFalse(true);
        } else {
            Assert::assertEquals($data[$key], $value, $message);
        }

        return $this;
    }

    /**
     * Check if the content contains the parsed text
     *
     * @param string $text
     * @return Response
     */
    public function assertContains(string $text): Response
    {
        Assert::assertContains($text, $this->content);

        return $this;
    }

    /**
     * Get the response content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get the response content as array
     *
     * @return array|object
     */
    public function toArray(): array|object
    {
        return json_decode($this->content, true, 1024, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * __call
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call(string $method, array $params = [])
    {
        if (method_exists($this->http_response, $method)) {
            return call_user_func([$this->http_response, $method]);
        }

        throw new InvalidArgumentException(
            "The method [$method] is not exists"
        );
    }
}
