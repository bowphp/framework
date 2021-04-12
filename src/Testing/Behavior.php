<?php

namespace Bow\Testing;

use Bow\Http\Client\Parser;

class Behavior
{
    /**
     * The http parser
     *
     * @var Parser
     */
    private $parser;

    /**
     * The parser content
     *
     * @var string
     */
    private $content;

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
     *
     * @return Behavior
     */
    public function assertJson($message = '')
    {
        Assert::assertJson(json_encode($this->content), $message);

        return $this;
    }

    /**
     * Check if the content is json format and the parsed data is
     * some to the content
     *
     * @param $data
     * @param string $message
     *
     * @return Behavior
     */
    public function assertExactJson($data, $message = '')
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
     * @return Behavior
     */
    public function assertContainsExactText($data, $message = '')
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
     * @return Behavior
     */
    public function assertHeader($header, $message = '')
    {
        Assert::assertArrayHasKey($header, $this->parser->getHeaders(), $message);

        return $this;
    }

    /**
     * Check if the content is array format
     *
     * @param string $message
     *
     * @return Behavior
     */
    public function assertArray($message = '')
    {
        Assert::assertTrue(is_array($this->parser->toArray()), $message);

        return $this;
    }

    /**
     * Check the content type
     *
     * @param $content_type
     * @param string $message
     *
     * @return Behavior
     */
    public function assertContentType($content_type, $message = '')
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
     * @return Behavior
     */
    public function assertContentTypeJson($message = '')
    {
        $this->assertContentType('application/json', $message);

        return $this;
    }

    /**
     * Check if the content type is text/plain
     *
     * @param string $message
     *
     * @return Behavior
     */
    public function assertContentTypeText($message = '')
    {
        $this->assertContentType('text/plain', $message);

        return $this;
    }

    /**
     * Check if the content type is text/html
     *
     * @param string $message
     *
     * @return Behavior
     */
    public function assertContentTypeHtml($message = '')
    {
        $this->assertContentType('text/html', $message);

        return $this;
    }

    /**
     * Check if the content type is text/xml
     *
     * @param string $message
     *
     * @return Behavior
     */
    public function assertContentTypeXml($message = '')
    {
        $this->assertContentType('text/xml', $message);

        return $this;
    }

    /**
     * Check the status code
     *
     * @param int $code
     *
     * @return Behavior
     */
    public function assertStatus($code, $message = '')
    {
        Assert::assertEquals($this->parser->getCode(), $code, $message);

        return $this;
    }

    /**
     * @param $key
     * @param string $message
     *
     * @return Behavior
     */
    public function assertKeyExists($key, $message = '')
    {
        $data = $this->parser->toArray();

        Assert::assertTrue(isset($data[$key]), $message);

        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @param string $message
     *
     * @return Behavior
     */
    public function assertKeyMatchValue($key, $value, $message = '')
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
     *
     * @return Behavior
     */
    public function assertContains($text)
    {
        Assert::assertContains($text, $this->content);

        return $this;
    }

    /**
     * Get the response content
     * 
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
