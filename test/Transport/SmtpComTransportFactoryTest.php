<?php

namespace Test\Transport;

use Symfony\Component\Mailer\Test\TransportFactoryTestCase;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Versgui\SmtpcomMailer\Transport\SmtpComApiTransport;
use Versgui\SmtpcomMailer\Transport\SmtpComSmtpTransport;
use Versgui\SmtpcomMailer\Transport\SmtpComTransportFactory;

/**
 * @author Guillaume Verstraete
 */
class SmtpComTransportFactoryTest extends TransportFactoryTestCase
{
    public function getFactory(): TransportFactoryInterface
    {
        return new SmtpComTransportFactory($this->getDispatcher(), $this->getClient(), $this->getLogger());
    }

    public function supportsProvider(): iterable
    {
        yield [
            new Dsn('smtpcom+api', 'default'),
            true,
        ];

        yield [
            new Dsn('smtpcom', 'default'),
            true,
        ];

        yield [
            new Dsn('smtpcom+smtp', 'default'),
            true,
        ];

        yield [
            new Dsn('smtpcom+smtp', 'example.com'),
            true,
        ];
    }

    public function createProvider(): iterable
    {
        $dispatcher = $this->getDispatcher();
        $logger = $this->getLogger();

        yield [
            new Dsn('smtpcom+api', 'default', self::USER, '', 8080, ['channel' => 'a_channel']),
            new SmtpComApiTransport(self::USER, 'a_channel', $this->getClient(), $dispatcher, $logger),
        ];

        yield [
            new Dsn('smtpcom+api', 'example.com', self::USER, '', 8080, ['channel' => 'a_channel']),
            (new SmtpComApiTransport(self::USER, 'a_channel', $this->getClient(), $dispatcher, $logger))
                ->setHost('example.com')
                ->setPort(8080),
        ];

        yield [
            new Dsn('smtpcom', 'default', self::USER, self::PASSWORD),
            new SmtpComSmtpTransport(self::USER, 'a_channel', $dispatcher, $logger),
        ];

        yield [
            new Dsn('smtpcom+smtp', 'default', self::USER, self::PASSWORD),
            new SmtpComSmtpTransport(self::USER, 'a_channel', $dispatcher, $logger),
        ];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield [
            new Dsn('smtpcom+foo', 'default', self::USER),
            'The "smtpcom+foo" scheme is not supported; supported schemes for mailer "smtpcom" are: "smtpcom", "smtpcom+api", "smtpcom+smtp".',
        ];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield [new Dsn('smtpcom+api', 'default')];
    }
}
