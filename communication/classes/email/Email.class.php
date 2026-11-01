<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/
namespace communication\email;

use equal\orm\Model;

class Email extends Model {

    /** @deprecated */
    public function getTable() {
        return self::getModelTable();
    }

    public static function getModelTable() {
        return 'core_mail';
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['subject']
            ],

            'message_id' => [
                'type'              => 'string',
                'description'       => "Unique string identifier of the message.",
                'help'              => "Internet message identifier per RFC 5322.",
                // 'unique'            => true
            ],

            'mailbox_id' => [
                'type'              => 'many2one',
                'description'       => "The mailbox the email relates to, if any.",
                'foreign_object'    => 'communication\email\Mailbox'
            ],

            'outgoing_attachment_documents_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'document\document\Document',
                'foreign_field'     => 'attached_emails_ids',
                'rel_table'         => 'communication_email_rel_documents',
                'rel_foreign_key'   => 'document_id',
                'rel_local_key'     => 'email_id',
                'description'       => 'Documents attached to the email.',
                'help'              => 'Documents linked to the email message to be sent as attachments.',
                'visible'           => ['direction', '=', 'outgoing']
            ],

            'incoming_attachment_documents_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\document\Document',
                'foreign_field'     => 'source_object_id',
                'domain'            => [
                    ['source_object_class', '=', 'communication\email\Email'],
                    ['source', '=', 'email']
                ],
                'description'       => 'Documents imported from incoming email attachments.',
                'visible'           => ['direction', '=', 'incoming']
            ],

            'incoming_attachment_import_status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'complete',
                    'unsupported',
                    'missing'
                ],
                'description'       => 'Result of importing attachments from an incoming email.',
                'help'              => 'Indicates whether attachments were imported, unsupported, missing, or are still pending.',
                'visible'           => ['direction', '=', 'incoming']
            ],

            'incoming_attachment_import_log' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Names of incoming email attachments ignored during import.',
                'help'              => 'Contains one ignored attachment name per line.',
                'visible'           => ['direction', '=', 'incoming']
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'Date and time of the email message.',
                'default'           => fn() => time()
            ],

            'direction' => [
                'type'              => 'string',
                'selection'         => ['outgoing', 'incoming'],
                'default'           => 'outgoing',
                'description'       => 'Direction of the email message.',
            ],

            'thread_hash' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Hash of the cleaned subject to group conversations.',
                'help'              => 'The hash is base on the email subject, discarding any `Re` and `Fwd`',
                'function'          => 'calcThreadHash',
                'store'             => true,
                'instant'           => true
            ],

            'to' => [
                'type'              => 'string',
                'usage'             => 'email',
                'required'          => true
            ],

            'from' => [
                'type'              => 'string',
                'usage'             => 'email'
            ],

            'reply_to' => [
                'type'              => 'string',
                'usage'             => 'email'
            ],

            'cc' => [
                'type'              => 'string',
                'description'       => 'Comma separated list of carbon-copy recipients.'
            ],

            'bcc' => [
                'type'              => 'string',
                'description'       => 'Comma separated list of blind carbon-copy recipients.'
            ],

            'subject' => [
                'type'              => 'string',
                'required'          => true,
                'dependents'        => ['name', 'thread_hash']
            ],

            'body' => [
                'type'              => 'string',
                'usage'             => 'text/html.medium'
            ],

            'object_class' => [
                'type'              => 'string',
                'description'       => 'Class of the object object_id points to.'
            ],

            'object_id' => [
                'type'              => 'integer',
                'description'       => 'Identifier of the object the email originates from.'
            ],

            'has_error' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'SMTP response status code.',
                'function'          => 'calcHasError',
                'store'             => true,
                'visible'           => [['direction', '=', 'outgoing'], ['status', '<>', 'pending']]
            ],

            'response_status' => [
                'type'              => 'integer',
                'description'       => 'SMTP response status code.',
                'visible'           => [['direction', '=', 'outgoing'], ['status', '<>', 'pending']],
                'dependents'        => ['has_error']
            ],

            'response' => [
                'type'              => 'string',
                'description'       => 'SMTP response returned at sending.',
                'default'           => '',
                'visible'           => [['direction', '=', 'outgoing'], ['status', '<>', 'pending']]
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'processed'
                ],
                'default'           => 'pending',
                'description'       => 'Processing status of the email.'
            ]

        ];
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'can_force_attachment_import_complete' => [
                'description' => 'Checks whether an attachment import can be manually marked as complete.',
                'function'    => 'policyCanForceAttachmentImportComplete'
            ]
        ]);
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'force_attachment_import_complete' => [
                'description' => 'Mark the attachment import as complete after a manual review.',
                'policies'    => ['can_force_attachment_import_complete'],
                'function'    => 'doForceAttachmentImportComplete'
            ]
        ]);
    }

    protected static function policyCanForceAttachmentImportComplete($self): array {
        $result = [];

        $self->read(['direction', 'attachment_import_status']);
        foreach($self as $id => $email) {
            if($email['direction'] !== 'incoming') {
                $result[$id] = [
                    'invalid_direction' => 'Only incoming emails can have their attachment import manually completed.'
                ];
                continue;
            }

            if($email['attachment_import_status'] !== 'unsupported') {
                $result[$id] = [
                    'invalid_attachment_import_status' => 'Only an unsupported attachment import can be manually completed.'
                ];
            }
        }

        return $result;
    }

    protected static function doForceAttachmentImportComplete($self) {
        $self->update(['attachment_import_status' => 'complete']);
    }

    protected static function calcHasError($self) {
        $result = [];
        $self->read(['response_status']);
        foreach($self as $id => $email) {
            if($email['response_status']) {
                $result[$id] = ($email['response_status'] !== 250);
            }
        }
        return $result;
    }

    protected static function calcThreadHash($self) {
        $result = [];
        $self->read(['subject']);
        foreach($self as $id => $email) {
            if(!$email['subject']) {
                continue;
            }
            $cleaned_subject = preg_replace('/^((re|fwd)\s*:\s*)+/i', '', $email['subject']);
            if(strlen($cleaned_subject) > 0) {
                $result[$id] = hash('sha1', strtolower(trim($cleaned_subject)));
            }
        }
        return $result;
    }

}
