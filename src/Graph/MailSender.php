<?php

declare(strict_types=1);

namespace Shabstagram\Graph;

use Shabstagram\Support\Config;

/**
 * Envoi de courriels via Microsoft Graph, "au nom de" la boîte configurée
 * dans GRAPH_SENDER_UPN (permission applicative Mail.Send).
 */
final class MailSender
{
    public function __construct(private GraphClient $graph)
    {
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $senderUpn = (string) Config::get('graph.sender_upn');

        $this->graph->post("/users/{$senderUpn}/sendMail", [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlBody,
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $toEmail,
                            'name' => $toName,
                        ],
                    ],
                ],
            ],
            'saveToSentItems' => false,
        ]);
    }
}
