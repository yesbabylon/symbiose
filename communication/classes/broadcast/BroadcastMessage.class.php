<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

namespace communication\broadcast;

use communication\email\Email;
use equal\orm\Model;

class BroadcastMessage extends Model {

    public static function getDescription(): string {
        return 'Prepares an email message and queues one outgoing email for each recipient contact.';
    }

    public static function getColumns(): array {
        return [
            'name' => [
                'type'        => 'string',
                'description' => 'Internal name used to identify the broadcast.',
                'required'    => true
            ],

            'status' => [
                'type'        => 'string',
                'selection'   => ['draft', 'queued'],
                'default'     => 'draft',
                'description' => 'Current processing status of the broadcast.'
            ],

            'mailbox_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'communication\email\Mailbox',
                'description'    => 'Validated mailbox used to send the generated emails.',
                'required'       => true,
                'dependents'     => ['reply_to'],
                'domain'         => [
                    ['can_send', '=', true],
                    ['status', '=', 'validated']
                ]
            ],

            'contact_group_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\ContactGroup',
                'description'    => 'Contact group used as the mailing list.',
                'required'       => true
            ],

            'documents_ids' => [
                'type'            => 'many2many',
                'foreign_object'  => 'document\document\Document',
                'foreign_field'   => 'broadcasts_ids',
                'rel_table'       => 'communication_broadcast_rel_document',
                'rel_foreign_key' => 'document_id',
                'rel_local_key'   => 'broadcast_id',
                'description'     => 'Documents attached to every email generated for the broadcast.'
            ],

            'subject' => [
                'type'        => 'string',
                'description' => 'Subject shared by every generated email.',
                'required'    => true
            ],

            'body' => [
                'type'        => 'string',
                'usage'       => 'text/html.medium',
                'description' => 'HTML content shared by every generated email.',
                'required'    => true
            ],

            'reply_to' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'usage'       => 'email',
                'relation'    => ['mailbox_id' => 'email'],
                'description' => 'Mailbox address to which replies must be sent.',
                'readonly'    => true
            ],

            'recipient_count' => [
                'type'        => 'integer',
                'description' => 'Number of distinct recipient emails queued by the broadcast.',
                'default'     => 0,
                'readonly'    => true
            ],

            'queued_at' => [
                'type'        => 'datetime',
                'description' => 'Date and time at which the outgoing emails were queued.',
                'readonly'    => true
            ],

            'mails_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'communication\email\Email',
                'foreign_field'  => 'object_id',
                'domain'         => [
                    ['object_class', '=', 'communication\broadcast\BroadcastMessage'],
                    ['object_id', '=', 'object.id']
                ],
                'description'    => 'Outgoing emails generated for the broadcast.'
            ]
        ];
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'is_queueable' => [
                'description' => 'Checks that the broadcast can generate its outgoing emails.',
                'function'    => 'policyIsQueueable'
            ]
        ]);
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'queue' => [
                'description' => 'Generate one pending outgoing email for each distinct recipient.',
                'policies'    => ['is_queueable'],
                'function'    => 'doQueue'
            ]
        ]);
    }

    protected static function policyIsQueueable($self): array {
        $result = [];

        $self->read([
            'status',
            'mailbox_id' => ['status', 'can_send'],
            'contact_group_id' => [
                'contacts_ids' => [
                    'email'
                ]
            ],
            'subject',
            'body'
        ]);
        foreach($self as $id => $broadcast) {
            if($broadcast['status'] !== 'draft') {
                $result[$id]['status']['not_draft'] = 'Only draft broadcasts can be queued.';
            }

            $mailbox = $broadcast['mailbox_id'] ?? null;
            if(!$mailbox) {
                $result[$id]['mailbox_id']['missing_mailbox'] = 'A sending mailbox is required.';
            }
            elseif($mailbox['status'] !== 'validated' || !$mailbox['can_send']) {
                $result[$id]['mailbox_id']['invalid_mailbox'] = 'The selected mailbox must be validated and allowed to send emails.';
            }

            if(empty(trim((string) $broadcast['subject']))) {
                $result[$id]['subject']['missing_subject'] = 'A subject is required.';
            }

            if(empty(trim((string) $broadcast['body']))) {
                $result[$id]['body']['missing_body'] = 'A body is required.';
            }

            $recipients = self::computeRecipients($broadcast);
            if(empty($recipients)) {
                $result[$id]['contact_group_id']['missing_recipients'] = 'At least one recipient is required.';
            }
        }

        return $result;
    }

    protected static function doQueue($self) {
        $self->read([
            'mailbox_id' => ['id'],
            'contact_group_id' => [
                'contacts_ids' => [
                    'email'
                ]
            ],
            'documents_ids',
            'subject',
            'body',
            'reply_to'
        ]);

        foreach($self as $id => $broadcast) {
            $recipients = self::computeRecipients($broadcast);

            foreach($recipients as $recipient) {
                Email::create([
                    'mailbox_id'   => $broadcast['mailbox_id']['id'],
                    'direction'    => 'outgoing',
                    'to'           => $recipient,
                    'reply_to'     => $broadcast['reply_to'],
                    'subject'      => $broadcast['subject'],
                    'body'         => $broadcast['body'],
                    'status'       => 'pending',
                    'object_class' => 'communication\broadcast\BroadcastMessage',
                    'object_id'    => $id,
                    'outgoing_attachment_documents_ids' => $broadcast['documents_ids']
                ]);
            }

            self::id($id)->update([
                'status'          => 'queued',
                'recipient_count' => count($recipients),
                'queued_at'       => time()
            ]);
        }
    }

    private static function computeRecipients($broadcast): array {
        $recipients = [];

        $contact_group = $broadcast['contact_group_id'] ?? null;
        if($contact_group) {
            foreach($contact_group['contacts_ids'] as $contact) {
                $email = strtolower(trim((string) ($contact['email'] ?? '')));

                if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $recipients[$email] = $email;
            }
        }

        return array_values($recipients);
    }
}
