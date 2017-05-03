<?php
/**
 * Created by IntelliJ IDEA.
 * User: papac
 * Date: 5/1/17
 * Time: 2:42 PM
 */

namespace Bow\Support\Testing;


use Bow\Http\Client\Parser;

class Behovior
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * Behovior constructor.
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function mustBeJson($message = '')
    {
        Assert::assertJson($this->parser->toJson(), $message);
    }

    public function mustBeExactJson($data, $message = '')
    {
        $json = $this->parser->toJson();
        Assert::assertArraySubset($data, json_decode($json), $message);
    }

    public function mustBeExactText($data, $message = '')
    {
        $text = $this->parser->raw();
        Assert::assertEquals($text, $data, $message);
    }

    public function headerExists($header, $message = '')
    {
        Assert::assertArrayHasKey($header, $this->parser->getHeaders(), $message);
    }

    public function mustBeArray($message = '')
    {
        Assert::assertTrue(is_array($this->parser->toArray()), $message);
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

    public function logResponse()
    {
        var_dump($this->parser->raw());
    }

    public function logResponseToArray()
    {
        var_dump($this->parser->toArray());
    }

    public function logError()
    {
        var_dump($this->parser->getErrorMessage());
    }
}