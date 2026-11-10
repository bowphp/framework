<?php

namespace Bow\Tests\Mail;

use Bow\Mail\Envelop;
use PHPUnit\Framework\TestCase;

class EnvelopHeaderInjectionTest extends TestCase
{
    public function test_subject_strips_crlf()
    {
        $envelop = new Envelop(false);
        $envelop->subject("Hi\r\nBcc: victim@evil.com");

        $subject = $envelop->getSubject();

        $this->assertStringNotContainsString("\r", $subject);
        $this->assertStringNotContainsString("\n", $subject);
        $this->assertSame("HiBcc: victim@evil.com", $subject);
    }

    public function test_with_header_strips_crlf_from_key_and_value()
    {
        $envelop = new Envelop(false);
        $envelop->withHeader("X-Test\r\nBcc: x@evil", "v\r\nCc: y@evil");

        $compiled = $envelop->compileHeaders();

        $this->assertStringNotContainsString("\nBcc:", $compiled);
        $this->assertStringNotContainsString("\nCc:", $compiled);

        foreach ($envelop->getHeaders() as $header) {
            $this->assertStringNotContainsString("\r", $header);
            $this->assertStringNotContainsString("\n", $header);
        }
    }

    public function test_from_strips_crlf()
    {
        $envelop = new Envelop(false);
        $envelop->from("bob@example.com\r\nBcc: x@evil", "Bob\r\nName");

        $from = $envelop->getFrom();

        $this->assertStringNotContainsString("\r", $from);
        $this->assertStringNotContainsString("\n", $from);
    }

    public function test_copy_and_reply_headers_strip_crlf()
    {
        $envelop = new Envelop(false);
        $envelop->addBcc("bcc@example.com\r\nSubject: hacked");
        $envelop->addCc("cc@example.com\r\nSubject: hacked");
        $envelop->addReplyTo("reply@example.com\r\nSubject: hacked");
        $envelop->addReturnPath("return@example.com\r\nSubject: hacked");

        foreach ($envelop->getHeaders() as $header) {
            $this->assertStringNotContainsString("\r", $header);
            $this->assertStringNotContainsString("\n", $header);
        }
    }
}
