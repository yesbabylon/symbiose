<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use document\document\DocumentSubtype;
use document\document\DocumentType;

$document_types = [
    [
        'name'        => 'Document de référence',
        'code'        => 'reference_document',
        'folder_code' => 'reference_documents',
        'subtypes'    => [
            [
                'name'        => 'Règlement d\'ordre intérieur',
                'code'        => 'internal_rules',
                'folder_code' => 'reference_documents'
            ],
            [
                'name'        => 'Plans',
                'code'        => 'plan',
                'folder_code' => 'reference_documents'
            ]
        ]
    ],
    [
        'name'        => 'Document d\'assemblée générale',
        'code'        => 'general_assembly_document',
        'folder_code' => 'general_meetings',
        'description' => 'Document relatif à une assemblée générale.',
        'subtypes'    => [
            [
                'name'        => 'Convocation',
                'code'        => 'invite',
                'folder_code' => 'general_meetings'
            ],
            [
                'name'        => 'Annexe de convocation',
                'code'        => 'appendix',
                'folder_code' => 'general_meetings'
            ],
            [
                'name'        => 'Liste des présences',
                'code'        => 'attendance_register',
                'folder_code' => 'general_meetings'
            ],
            [
                'name'        => 'Procès-verbal d\'assemblée générale',
                'code'        => 'minutes',
                'folder_code' => 'general_meetings'
            ]
        ]
    ],
    [
        'name'        => 'Document bancaire',
        'code'        => 'bank_document',
        'folder_code' => 'bank_documents',
        'subtypes'    => [
            [
                'name'        => 'Ouverture de compte',
                'code'        => 'bank_account_opening',
                'folder_code' => 'bank_documents'
            ],
            [
                'name'        => 'Emprunt bancaire',
                'code'        => 'bank_loan',
                'folder_code' => 'bank_documents'
            ]
        ]
    ],
    [
        'name'        => 'Appel de fonds',
        'code'        => 'fund_request',
        'folder_code' => 'operation_statements',
        'description' => 'Document sollicitant un paiement d\'avance ou une participation.'
    ],
    [
        'name'        => 'Décompte de charges',
        'code'        => 'expense_statement',
        'folder_code' => 'operation_statements',
        'description' => 'Détail ou synthèse des charges engagées.'
    ],
    [
        'name'        => 'Rappel de paiement',
        'code'        => 'payment_reminder',
        'folder_code' => 'operation_statements'
    ],
    [
        'name'        => 'Extrait bancaire (CODA)',
        'code'        => 'bank_statement',
        'folder_code' => 'bank_statements',
        'description' => 'Document reprenant les mouvements d\'un compte bancaire.'
    ],
    [
        'name'        => 'Facture fournisseur',
        'code'        => 'supplier_invoice',
        'folder_code' => 'supplier_invoices',
        'description' => 'Document comptable à comptabiliser et à réconcilier.',
        'subtypes'    => [
            [
                'name'        => 'Facture d\'acompte',
                'code'        => 'advance_invoice',
                'folder_code' => 'supplier_invoices'
            ],
            [
                'name'        => 'Facture de régularisation',
                'code'        => 'adjustment_invoice',
                'folder_code' => 'supplier_invoices'
            ],
            [
                'name'        => 'Facture de prestations hors contrat',
                'code'        => 'off_contract',
                'folder_code' => 'supplier_invoices'
            ]
        ]
    ],
    [
        'name'        => 'Contrat fournisseur',
        'code'        => 'supplier_contract',
        'folder_code' => 'supplier_contracts',
        'description' => 'Engagement contractuel formel avec un fournisseur.'
    ],
    [
        'name'        => 'Document fournisseur',
        'code'        => 'supplier_document',
        'folder_code' => 'supplier_documents',
        'subtypes'    => [
            [
                'name'        => 'Mandat de domiciliation fournisseur',
                'code'        => 'supplier_direct_debit_mandate',
                'folder_code' => 'supplier_documents'
            ],
            [
                'name'        => 'Informations bancaires fournisseur',
                'code'        => 'supplier_bank_details',
                'folder_code' => 'supplier_documents'
            ]
        ]
    ],
    [
        'name'        => 'Attestation technique et rapport de contrôle',
        'code'        => 'technical_report_document',
        'folder_code' => 'technical_reports',
        'subtypes'    => [
            [
                'name'        => 'Rapport de contrôle',
                'code'        => 'inspection_report',
                'folder_code' => 'technical_reports'
            ],
            [
                'name'        => 'Attestation périodique',
                'code'        => 'periodic_certificate',
                'folder_code' => 'technical_reports'
            ]
        ]
    ],
    [
        'name'        => 'Document d\'assurance',
        'code'        => 'insurance_document',
        'folder_code' => 'insurance_documents',
        'subtypes'    => [
            [
                'name'        => 'Assurance responsabilité civile',
                'code'        => 'civil_liability',
                'folder_code' => 'insurance_documents'
            ],
            [
                'name'        => 'Assurance protection juridique',
                'code'        => 'legal_protection',
                'folder_code' => 'insurance_documents'
            ],
            [
                'name'        => 'Assurance accident du travail',
                'code'        => 'work_accident',
                'folder_code' => 'insurance_documents'
            ]
        ]
    ],
    [
        'name'        => 'Document de contentieux',
        'code'        => 'litigation_document',
        'folder_code' => 'litigation_files',
        'subtypes'    => [
            [
                'name'        => 'Dossier de contentieux',
                'code'        => 'case_file',
                'folder_code' => 'litigation_files'
            ]
        ]
    ],
    [
        'name'        => 'Document de sinistre',
        'code'        => 'claim_document',
        'folder_code' => 'claims',
        'subtypes'    => [
            [
                'name'        => 'Rapport d\'expertise sinistre',
                'code'        => 'expert_report',
                'folder_code' => 'claims'
            ],
            [
                'name'        => 'Procès-verbal d\'indemnisation',
                'code'        => 'claim_report',
                'folder_code' => 'claims'
            ]
        ]
    ],
    [
        'name'        => 'Bilan',
        'code'        => 'balance_sheet',
        'folder_code' => 'operation_statements',
        'description' => 'Bilan comptable.'
    ],
    [
        'name'        => 'Récapitulatif des frais',
        'code'        => 'expense_summary',
        'folder_code' => 'operation_statements',
        'description' => 'Synthèse des dépenses courantes.'
    ],
    [
        'name'        => 'Import fournisseurs',
        'code'        => 'suppliers_import',
        'folder_code' => 'imports',
        'description' => 'Fichiers temporaires d\'import de fournisseurs.'
    ],
    [
        'name'        => 'Import banques',
        'code'        => 'banks_import',
        'folder_code' => 'imports',
        'description' => 'Fichiers temporaires d\'import de banques.'
    ],
    [
        'name'        => 'Balance générale des comptes',
        'code'        => 'general_balance',
        'folder_code' => 'operation_statements',
        'description' => 'Document présentant, pour chaque compte, le total des mouvements au débit et au crédit ainsi que le solde sur une période comptable donnée.'
    ],
    [
        'name'        => 'Grand livre',
        'code'        => 'general_ledger',
        'folder_code' => 'operation_statements',
        'description' => 'Document présentant, par compte et par ordre chronologique, l\'ensemble des écritures comptables enregistrées sur une période donnée.'
    ],
    [
        'name'        => 'Opération diverse',
        'code'        => 'misc_operation',
        'folder_code' => 'operation_statements',
        'description' => 'Document justificatif d\'une opération diverse.'
    ]
];

foreach($document_types as $document_type_data) {
    $document_subtypes = $document_type_data['subtypes'] ?? [];
    unset($document_type_data['subtypes']);

    $document_type = DocumentType::search(['code', '=', $document_type_data['code']]);
    $document_type_ids = $document_type->ids();

    if(count($document_type_ids) > 1) {
        throw new Exception(
            "Several document types use code '{$document_type_data['code']}'.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    if(empty($document_type_ids)) {
        $document_type = DocumentType::create($document_type_data);
        $document_type_ids = $document_type->ids();
    }
    else {
        $document_type->update($document_type_data);
    }

    $document_type_id = reset($document_type_ids);

    foreach($document_subtypes as $document_subtype_data) {
        /*
        Not needed because no description on subtype
        if(empty($document_subtype_data['description'])) {
            $document_subtype_data['description'] = 'Sous-type de document : '.$document_subtype_data['name'].'.';
        }
        */

        $document_subtype_data['document_type_id'] = $document_type_id;

        $document_subtype = DocumentSubtype::search([
            ['document_type_id', '=', $document_type_id],
            ['code', '=', $document_subtype_data['code']]
        ]);
        $document_subtype_ids = $document_subtype->ids();

        if(count($document_subtype_ids) > 1) {
            throw new Exception(
                "Several document subtypes use code '{$document_subtype_data['code']}' for document type '{$document_type_data['code']}'.",
                EQ_ERROR_CONFLICT_OBJECT
            );
        }

        if(empty($document_subtype_ids)) {
            DocumentSubtype::create($document_subtype_data);
        }
        else {
            $document_subtype->update($document_subtype_data);
        }
    }

    $has_subtypes = count(DocumentSubtype::search(['document_type_id', '=', $document_type_id])->ids()) > 0;
    $document_type->update(['has_subtype' => $has_subtypes]);
}
