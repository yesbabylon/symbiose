<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/
namespace document;


class DocumentSignature extends \core\security\Signature {

    public static function getColumns() {
        return [
            'condo_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'description'       => "The condominium the document belongs to.",
                'foreign_object'    => 'realestate\property\Condominium',
                'relation'          => ['document_id' => 'condo_id'],
                'store'             => true,
                'instant'           => true
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\document\Document',
                'required'          => true,
                'description'       => 'Reference version of the document (immutable).',
                'dependents'        => ['data_digest', 'condo_id']
            ],

            'data_digest' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'text/plain:64',
                'relation'          => ['document_id' => 'hash_sha256'],
                'description'       => 'Original data signed by the signer.',
                'help'              => 'As a convention, it is not the  full document that is signed, but only its digest.
                    This field holds the hexadecimal representation of the value of the document hash of the document, and might require a conversion to binary.
                    Only SHA-256 is supported for now.',
                'store'             => true,
                'readonly'          => true,
                'visible'           => ['sig_method', 'in', ['aes', 'qes']]
            ],

            'signer_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'required'          => true,
                'description'       => 'Person or representative who signed the document.',
                'help'              => 'In case of a AES/QES signature, the identity is used to check the sig_cert against the Signer Identity public key.'
            ]

        ];
    }

    protected static function calcName($self) {
        $result = [];
        $self->read(['signer_identity_id' => ['name'], 'document_id' => ['name'], 'sig_timestamp']);
        foreach($self as $id => $documentSignature) {
            if(!isset($documentSignature['signer_identity_id'], $documentSignature['document_id'])) {
                continue;
            }
            $result[$id] = "{$documentSignature['signer_identity_id']['name']} - {$documentSignature['document_id']['name']} - {$documentSignature['sig_timestamp']}";
        }
        return $result;
    }

}
