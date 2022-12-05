<?php

declare(strict_types=1);

namespace Bow\Testing;

use InvalidArgumentException;
use Bow\Http\Client\Parser;

class Response
{
    /**
     * The http parser
     *
     * @var Parser
     */
    private Parser $parser;

    /**
     * The parser content
     *
     * @var string
     */
    private string $content;

    /**
     * Behovior constructor.
     *
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;

        $this->content = $parser->getContent();
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
        Assert::assertArraySubset($data, json_decode($this->content), $message);

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
        Assert::assertArrayHasKey($header, $this->parser->getHeaders(), $message);

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
        Assert::assertTrue(is_array($this->parser->toArray()), $message);

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
        $type = $this->parser->getContentType();

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
        Assert::assertEquals($this->parser->getCode(), $code, $message);

        return $this;
    }

    /**
     * @param string $key
     * @param string $message
     * @return Response
     */
    public function assertKeyExists(string $key, string $message = ''): Response
    {
        $data = $this->parser->toArray();

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
     * __call
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call(string $method, array $params = [])
    {
        if (method_exists($this->parser, $method)) {
            return call_user_func([$this->parser, $method]);
        }

        throw new InvalidArgumentException(
            "The method [$method] is not exists"
        );
    }
}
