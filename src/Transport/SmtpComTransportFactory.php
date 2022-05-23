<?php

namespace Versgui\SmtpcomMailer\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * @author Guillaume Verstraete
 */
class SmtpComTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $transport = null;
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);

        if ('smtpcom+api' === $scheme) {
            $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
            $port = $dsn->getPort();

            $transport = (new SmtpComApiTransport($user, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port);
        }

        if ('smtpcom+smtp' === $scheme || 'smtpcom+smtps' === $scheme || 'smtpcom' === $scheme) {
            $transport = new SmtpComkSmtpTransport($user, $this->dispatcher, $this->logger);
        }

        if (null !== $transport) {
            $messageStream = $dsn->getOption('message_stream');

            if (null !== $messageStream) {
                $transport->setMessageStream($messageStream);
            }

            return $transport;
        }

        throw new UnsupportedSchemeException($dsn, 'smtpcom', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['smtpcom', 'smtpcom+api', 'smtpcom+smtp', 'smtpcom+smtps'];
    }
}