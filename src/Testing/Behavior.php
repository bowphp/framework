<?php

namespace Bow\Testing;

use Bow\Http\Client\Parser;

class Behavior
{
    /**
     * @var Parser
     */
    private $parser;

    /**
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
     * @param string $message
     * @return $this
     */
    public function mustBeJson($message = '')
    {
        Assert::assertJson(json_encode($this->content), $message);

        return $this;
    }

    /**
     * @param $data
     * @param string $message
     * @return $this
     */
    public function mustBeExactJson($data, $message = '')
    {
        Assert::assertArraySubset($data, json_decode($this->content), $message);

        return $this;
    }

    /**
     * @param $data
     * @param string $message
     * @return $this
     */
    public function mustBeExactText($data, $message = '')
    {
        Assert::assertEquals($this->content, $data, $message);
        
        return $this;
    }

    /**
     * @param $header
     * @param string $message
     * @return $this
     */
    public function headerExists($header, $message = '')
    {
        Assert::assertArrayHasKey($header, $this->parser->getHeaders(), $message);

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function mustBeArray($message = '')
    {
        Assert::assertTrue(is_array($this->parser->toArray()), $message);

        return $this;
    }

    /**
     * @param $code
     * @return $this
     */
    public function statusCodeMustBe($code)
    {
        Assert::assertEquals($code, $this->parser->getCode());

        return $this;
    }

    /**
     * @param $content_type
     * @param string $message
     * @return $this
     */
    public function contentTypeMustBe($content_type, $message = '')
    {
        $type = $this->parser->getContentType();

        Assert::assertEquals($content_type, current(preg_split('/;(\s+)?/', $type)), $message);

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function contentTypeMustBeJson($message = '')
    {
        $this->contentTypeMustBe('application/json', $message);

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function contentTypeMustBeText($message = '')
    {
        $this->contentTypeMustBe('text/plain', $message);

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function contentTypeMustBeHtml($message = '')
    {
        $this->contentTypeMustBe('text/html', $message);

        return $this;
    }

    /**
     * @param $data
     * @param string $message
     * @return Behavior
     */
    public function assertJson($data, $message = '')
    {
        return $this->mustBeExactJson($data, $message);
    }

    /**
     * @param $code
     * @param string $message
     * @return $this
     */
    public function assertStatus($code, $message = '')
    {
        Assert::assertTrue($this->parser->getCode() == $code, $message);

        return $this;
    }

    /**
     * @param $type
     * @param string $message
     * @return Behavior
     */
    public function assertContentType($type, $message = '')
    {
        return $this->contentTypeMustBe($type, $message);
    }

    /**
     * @param $key
     * @param string $message
     * @return $this
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
     * @return $this
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
     * @param $text
     */
    public function containsText($text)
    {
        Assert::assertContains($text, $this->content);
    }
}
