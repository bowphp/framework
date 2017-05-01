<?php
/**
 * Created by IntelliJ IDEA.
 * User: papac
 * Date: 5/1/17
 * Time: 2:42 PM
 */

namespace Bow\Support\Test;


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

    public function mustBeJson()
    {
        Assert::assertJson($this->parser->toJson());
    }

    public function mustBeArray()
    {
        Assert::assertTrue(is_array($this->parser->toArray()));
    }

    public function statusCodeMustBe($code)
    {
        Assert::assertEquals($code, $this->parser->getCode());
        return $this;
    }

    public function contentTypeMustBe($content_type)
    {
        $type = $this->parser->getContentType();
        Assert::assertEquals($content_type, current(preg_split('/;(\s+)?/', $type)));

        return $this;
    }

    public function contentTypeMustBeJson()
    {
        $this->contentTypeMustBe('application/json');

        return $this;
    }

    public function contentTypeMustBeText()
    {
        $this->contentTypeMustBe('text/plain');

        return $this;
    }

    public function contentTypeMustBeHtml()
    {
        $this->contentTypeMustBe('text/html');

        return $this;
    }

    public function logResponse()
    {
        var_dump($this->parser->raw());
    }

    public function logError()
    {
        var_dump($this->parser->getErrorMessage());
    }
}