<?php

namespace Test\Transport;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Versgui\SmtpcomMailer\Transport\SmtpComApiTransport;
use PHPUnit\Framework\TestCase;

/**
 * @author Guillaume Verstraete
 */
class SmtpComApiTransportTest extends TestCase
{
    /**
     * @dataProvider getTransportData
     */
    public function testToString(SmtpComApiTransport $transport, $expected)
    {
        $this->assertSame($expected, (string)$transport);
    }

    public function getTransportData(): \Iterator
    {
        yield [
            new SmtpComApiTransport('ACCESS_KEY', 'channel'),
            'smtpcom+api://api.smtp.com',
        ];

        yield [
            (new SmtpComApiTransport('ACCESS_KEY', 'channel'))->setHost('example.com'),
            'smtpcom+api://example.com',
        ];
    }

    public function testDoSendApi()
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api:API_KEY@api.smtp.com/v4/messages', $url);

            return new MockResponse(json_encode(['status' => 'success', 'data' => ['message' => 'accepted, msg_id: foobar']]), [
                'http_code' => 201,
            ]);
        });

        $transport = new SmtpComApiTransport('API_KEY', 'versgui_smtp_proton_me', $client);

        $dataPart = new DataPart('Lorem Ipsum', 'file.txt', 'text/plain');
        $email = new Email();
        $email->subject('Hello world!')
            ->to(new Address('guillaume@versgui.fr', 'Georges Abitbol'))
            ->from(new Address('peter-steven@example.com', 'Peter and Steven'))
            ->html('<i>Ask for interview in HTML</i>')
            ->text('Ask for interview in plain text')
            ->addCc(new Address('redaction-boss@example.com', 'Patron'))
            ->addBcc(new Address('m.hazanivius@example.com', 'Michel'))
            ->addReplyTo(new Address('redaction@example.com', 'The newspaper'))
            ->attachPart($dataPart);

        $message = $transport->send($email);

        $this->assertSame('foobar', $message->getMessageId());
    }

    public function testMultipleReplyTo()
    {
        $email = new Email();
        $email->subject('Hello world!')
            ->html('Ask for interview')
            ->addReplyTo(new Address('redaction@example.com', 'The newspaper'))
            ->addReplyTo(new Address('company@example.com', 'The company'));


    }
}