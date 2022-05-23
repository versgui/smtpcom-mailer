<?php

namespace Versgui\SmtpcomMailer\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Guillaume Verstraete
 */
class SmtpComApiTransport extends AbstractApiTransport
{
    private $key;
    private $channel;

    public function __construct(string $key, string $channel, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->key = $key;
        $this->channel = $channel;

        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        return sprintf('smtpcom+api://%s', $this->getEndpoint());
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $response = $this->client->request('POST', 'https://api:' . $this->key . '@' . $this->getEndpoint() . '/v4/messages', [
            'json' => $this->getPayload($email, $envelope),
        ]);

        try {
            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw new HttpTransportException('Unable to send an email: ' . $response->getContent(false) . sprintf(' (code %d).', $statusCode), $response);
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the remote SMTP.com server.', $response, 0, $e);
        }

        if (201 !== $statusCode) {
            throw new HttpTransportException('Unable to send an email: API returned a status "' . $result['status'] . '"', $response);
        }

        // Expected message in the response: "accepted, msg_id: UUID".
        // We pick the UUID as the message ID.
        preg_match('/(.*)(, msg_id: )(.*)/', $result['data']['message'], $message);
        $sentMessage->setMessageId($message[3]);

        return $response;
    }

    private function getPayload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'channel' => $this->channel,
            'originator' => [
                'from' => $this->formatAddress($envelope->getSender()),
            ],
            'recipients' => [
                'to' => $this->formatAddresses($this->getRecipients($email, $envelope)),
                'cc' => $this->formatAddresses($email->getCc()),
                'bcc' => $this->formatAddresses($email->getBcc()),
            ],
            'subject' => $email->getSubject(),
            'body' => [
                'parts' => $this->formatBody($email),
                'attachments' => $this->formatAttachments($email->getAttachments()),
            ],
        ];

        $replyTo = $email->getReplyTo();

        if (count($replyTo) > 1) {
            throw new \LogicException('SMTP.com accepts only one "reply_to" address.');
        } elseif ($replyTo) {
            $payload['originator']['reply_to'] = $this->formatAddress($replyTo[0]);
        }

        return $payload;
    }

    private function formatAddress(Address $address): array
    {
        $formattedAddress = ['address' => $address->getAddress()];

        if ($address->getName()) {
            $formattedAddress['name'] = $address->getName();
        }

        return $formattedAddress;
    }

    /**
     * @param Address[] $addresses
     */
    private function formatAddresses(array $addresses): array
    {
        $formattedAddresses = [];
        foreach ($addresses as $address) {
            $formattedAddresses[] = $this->formatAddress($address);
        }

        return $formattedAddresses;
    }

    private function formatBody(Email $email): array
    {
        $formattedBody = [];

        if ($htmlBody = $email->getHtmlBody()) {
            $formattedBody[] = [
                'type' => 'text/html',
                'content' => $htmlBody,
            ];
        }

        if ($textBody = $email->getTextBody()) {
            $formattedBody[] = [
                'type' => 'text/plain',
                'content' => $textBody,
            ];
        }

        return $formattedBody;
    }

    /**
     * @param array|DataPart[] $attachments
     */
    private function formatAttachments(array $attachments): array
    {
        $processedAttachments = [];

        foreach ($attachments as $attachment) {
            $processedAttachments[] = [
                'type' => $attachment->getContentType(),
                'filename' => $attachment->getFilename(),
                'cid' => $attachment->getContentId(),
                'content' => str_replace("\r\n", '', $attachment->bodyToString()),
            ];
        }

        return $processedAttachments;
    }

    private function getEndpoint(): string
    {
        return $this->host ?: 'api.smtp.com';
    }
}