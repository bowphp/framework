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
     * Behovior constructor.
     *
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function mustBeJson($message = '')
    {
        Assert::assertJson($this->parser->toJson(), $message);
        return $this;
    }

    public function mustBeExactJson($data, $message = '')
    {
        $json = $this->parser->toJson();
        Assert::assertArraySubset($data, json_decode($json), $message);
        return $this;
    }

    public function mustBeExactText($data, $message = '')
    {
        $text = $this->parser->raw();
        Assert::assertEquals($text, $data, $message);
        return $this;
    }

    public function headerExists($header, $message = '')
    {
        Assert::assertArrayHasKey($header, $this->parser->getHeaders(), $message);
        return $this;
    }

    public function mustBeArray($message = '')
    {
        Assert::assertTrue(is_array($this->parser->toArray()), $message);
        return $this;
    }

    public function statusCodeMustBe($code)
    {
        Assert::assertEquals($code, $this->parser->getCode());
        return $this;
    }

    public function contentTypeMustBe($content_type, $message = '')
    {
        $type = $this->parser->getContentType();
        Assert::assertEquals($content_type, current(preg_split('/;(\s+)?/', $type)), $message);
        return $this;
    }

    public function contentTypeMustBeJson($message = '')
    {
        $this->contentTypeMustBe('application/json', $message);
        return $this;
    }

    public function contentTypeMustBeText($message = '')
    {
        $this->contentTypeMustBe('text/plain', $message);
        return $this;
    }

    public function contentTypeMustBeHtml($message = '')
    {
        $this->contentTypeMustBe('text/html', $message);
        return $this;
    }

    public function assertJson($data, $message = '')
    {
        return $this->mustBeExactJson($data, $message);
    }

    public function assertStatus($code, $message = '')
    {
        Assert::assertTrue($this->parser->getCode() == $code, $message);
        return $this;
    }

    public function assertContentType($type, $message = '')
    {
        return $this->contentTypeMustBe($type, $message);
    }

    public function assertKeyExists($key, $message = '')
    {
        $data = $this->parser->toArray();
        Assert::assertTrue(isset($data[$key]), $message);
        return $this;
    }

    public function assertKeyMatchValue($key, $value, $message = '')
    {
        $data = $this->parser->toArray();
        Assert::assertTrue(isset($data));
        Assert::assertEquals($data[$key], $value, $message);
        return $this;
    }
}
