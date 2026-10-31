<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/
namespace communication\email;

use equal\orm\Model;
use realestate\management\ManagementProcess;

class Mailbox extends Model {


    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['email'],
                'store'             => true
            ],

            'can_send' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Indicates whether the mailbox can be used to send emails.',
            ],

            'can_receive' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Indicates whether the mailbox can receive and import emails.',
            ],

            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => 'Email address the Mailbox relates to.',
                'required'          => true,
                'unique'            => true,
                'dependents'        => ['name']
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => 'Mailbox signature to append to communications.',
                'multilang'         => true
            ],

            'login' => [
                'type'              => 'string',
                'visible'           => ['auth_type', '=', 'basic']
            ],

            'password' => [
                'type'              => 'string',
                'visible'           => ['auth_type', '=', 'basic']
            ],

            'imap_server' => [
                'type'              => 'string',
                'description'       => 'IMAP server hostname.',
                'visible'           => ['auth_type', '=', 'basic']
            ],

            'imap_port' => [
                'type'              => 'integer',
                'default'           => 993,
                'description'       => 'IMAP server port.',
                'visible'           => ['auth_type', '=', 'basic']
            ],

            'smtp_server' => [
                'type'              => 'string',
                'description'       => 'SMTP server hostname for outgoing emails.',
                'visible'           => ['auth_type', '=', 'basic']
            ],

            'smtp_port' => [
                'type'              => 'integer',
                'default'           => 587,
                'description'       => 'SMTP server port for outgoing emails.',
                'visible'           => ['auth_type', '=', 'basic']
            ],

            'auth_type' => [
                'type'              => 'string',
                'selection'         => ['basic', 'oauth'],
                'default'           => 'basic',
                'description'       => 'Authentication type.'
            ],

            'auth_provider' => [
                'type'              => 'string',
                'selection'         => ['google', 'microsoft'],
                'description'       => 'OAuth provider.',
                'visible'           => ['auth_type', '=', 'oauth']
            ],

            'access_token' => [
                'type'              => 'string',
                'usage'             => 'text/plain:4096',
                'description'       => 'OAuth2 access token (if applicable).',
                'visible'           => ['auth_type', '=', 'oauth']
            ],

            'refresh_token' => [
                'type'              => 'string',
                'usage'             => 'text/plain:4096',
                'description'       => 'OAuth2 refresh token (if applicable).',
                'visible'           => ['auth_type', '=', 'oauth']
            ],

            'access_token_expiry' => [
                'type'              => 'datetime',
                'description'       => 'Token expiration date/time.',
                'visible'           => ['auth_type', '=', 'oauth']
            ],

            'refresh_token_expiry' => [
                'type'              => 'datetime',
                'description'       => 'Token expiration date/time.',
                'visible'           => ['auth_type', '=', 'oauth']
            ],

            'date_last_sync' => [
                'type'              => 'datetime',
                'description'       => 'Time of the last emails fetch.',
                'default'           => fn() => (time() - 86400)
            ],

            'emails_ids' => [
                'type'              => 'one2many',
                'description'       => "All email messaged of the Mailbox.",
                'foreign_object'    => 'communication\email\Email',
                'foreign_field'     => 'mailbox_id'
            ],

            'incoming_emails_ids' => [
                'type'              => 'one2many',
                'description'       => "The email received in the Mailbox.",
                'foreign_object'    => 'communication\email\Email',
                'foreign_field'     => 'mailbox_id',
                'domain'            => [
                    'direction', '=', 'incoming'
                ]
            ],

            'outgoing_emails_ids' => [
                'type'              => 'one2many',
                'description'       => "The email sent with the Mailbox.",
                'foreign_object'    => 'communication\email\Email',
                'foreign_field'     => 'mailbox_id',
                'domain'            => [
                    'direction', '=', 'outgoing'
                ]
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'validated'
                ],
                'default'           => 'pending',
                'description'       => 'Status of the mailbox.',
                'onupdate'          => 'onupdateStatus'
            ]

        ];
    }

    protected static function onupdateStatus($self) {
        $self->read(['status', 'can_send']);
        foreach($self as $id => $mailbox) {
            if($mailbox['status'] === 'validated' && $mailbox['can_send']) {
                $mailboxes = self::search([['status', '=', 'validated'], ['can_send', '=', true], ['id', '<>', $id]]);
                if($mailboxes->count() <= 0) {
                    ManagementProcess::search()->update(['mailbox_id' => $id]);
                }
            }
        }
    }

}
