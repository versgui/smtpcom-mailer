<?php

namespace Versgui\SmtpcomMailer\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\RawMessage;

/**
 * @author Guillaume Verstraete
 */
class SmtpComSmtpTransport extends EsmtpTransport
{
    public function __construct(string $username, string $password, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        parent::__construct('send.smtp.com', 2525, true, $dispatcher, $logger);

        $this->setUsername($username);
        $this->setPassword($password);
    }
}
