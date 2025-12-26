<?php

declare(strict_types=1);

namespace Bow\Mail\Adapters;

use Bow\Mail\Contracts\MailAdapterInterface;
use Bow\Mail\Envelop;
use Bow\Support\Str;

class LogAdapter implements MailAdapterInterface
{
    /**
     * The log path
     *
     * @var string
     */
    private string $path;

    /**
     * LogAdapter Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/_bow/mails';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Implement send email
     *
     * @param  Envelop $envelop
     * @return bool
     */
    public function send(Envelop $envelop): bool
    {
        $filename = date('Y-m-d_H-i-s') . '_' . Str::random(6) . '.eml';
        $filepath = $this->path . '/' . $filename;

        $content = "Date: " . date('r') . "\n";
        $content .= $envelop->compileHeaders();

        $recipients = array_map(fn($to) => $to[0] ? "{$to[0]} <{$to[1]}>" : $to[1], $envelop->getTo());

        $content .= "To: " . implode(', ', $recipients) . "\n";

        $content .= "Subject: " . $envelop->getSubject() . "\n";

        $content .= $envelop->getMessage();

        return (bool) file_put_contents($filepath, $content);
    }
}
