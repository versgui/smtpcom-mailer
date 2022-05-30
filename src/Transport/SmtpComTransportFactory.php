<?php

namespace Versgui\SmtpcomMailer\Transport;

use Symfony\Component\Mailer\Exception\InvalidArgumentException;
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
        $password = $this->getPassword($dsn);

        if ('smtpcom+api' === $scheme) {
            $channel = $dsn->getOption('channel');

            if (!$channel) {
                throw new InvalidArgumentException('Channel option is missing');
            }

            $transport = (new SmtpComApiTransport($user, $channel, $this->client, $this->dispatcher, $this->logger))
                ->setHost('default' === $dsn->getHost() ? null : $dsn->getHost())
                ->setPort($dsn->getPort());
        }

        if ('smtpcom+smtp' === $scheme || 'smtpcom' === $scheme) {
            $transport = new SmtpComSmtpTransport($user, $password, $this->dispatcher, $this->logger);
        }

        if (!$transport) {
            throw new UnsupportedSchemeException($dsn, 'smtpcom', $this->getSupportedSchemes());
        }

        return $transport;
    }

    protected function getSupportedSchemes(): array
    {
        return ['smtpcom', 'smtpcom+api', 'smtpcom+smtp'];
    }
}