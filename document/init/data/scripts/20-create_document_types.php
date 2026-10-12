<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use document\document\DocumentSubtype;
use document\document\DocumentType;

// Keep this catalog reusable across applications: domain-specific document types
// belong to the package that implements the related business workflow.
$createDocumentType = function(array $type, array $subtypes = []) {
    if(empty($type['description'])) {
        $type['description'] = 'Type de document : ' . $type['name'] . '.';
    }

    $type['has_subtype'] = !empty($subtypes);
    $documentType = DocumentType::create($type)->first();

    foreach($subtypes as $subtype) {
        if(empty($subtype['description'])) {
            $subtype['description'] = 'Sous-type de document : ' . $subtype['name'] . '.';
        }

        $subtype['document_type_id'] = $documentType['id'];
        DocumentSubtype::create($subtype);
    }
};

$createDocumentType([
    'id'          => 2,
    'name'        => 'Document de référence',
    'code'        => 'reference_document',
    'folder_code' => 'reference_documents'
], [
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
]);

$createDocumentType([
    'id'          => 18,
    'name'        => 'Document d\'assemblée générale',
    'code'        => 'general_assembly_document',
    'folder_code' => 'general_meetings',
    'description' => 'Document relatif à une assemblée générale.'
], [
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
]);

$createDocumentType([
    'id'          => 22,
    'name'        => 'Document bancaire',
    'code'        => 'bank_document',
    'folder_code' => 'bank_documents'
], [
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
]);

$createDocumentType([
    'id'          => 12,
    'name'        => 'Appel de fonds',
    'code'        => 'fund_request',
    'folder_code' => 'operation_statements',
    'description' => 'Document sollicitant un paiement d\'avance ou une participation.'
]);

$createDocumentType([
    'id'          => 13,
    'name'        => 'Décompte de charges',
    'code'        => 'expense_statement',
    'folder_code' => 'operation_statements',
    'description' => 'Détail ou synthèse des charges engagées.'
]);

$createDocumentType([
    'id'          => 21,
    'name'        => 'Rappel de paiement',
    'code'        => 'payment_reminder',
    'folder_code' => 'operation_statements'
]);

$createDocumentType([
    'id'          => 14,
    'name'        => 'Extrait bancaire (CODA)',
    'code'        => 'bank_statement',
    'folder_code' => 'bank_statements',
    'description' => 'Document reprenant les mouvements d\'un compte bancaire.'
]);

$createDocumentType([
    'id'          => 1,
    'name'        => 'Facture fournisseur',
    'code'        => 'supplier_invoice',
    'folder_code' => 'supplier_invoices',
    'description' => 'Document comptable à comptabiliser et à réconcilier.'
], [
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
]);

$createDocumentType([
    'id'          => 8,
    'name'        => 'Contrat fournisseur',
    'code'        => 'supplier_contract',
    'folder_code' => 'supplier_contracts',
    'description' => 'Engagement contractuel formel avec un fournisseur.'
]);

$createDocumentType([
    'id'          => 23,
    'name'        => 'Document fournisseur',
    'code'        => 'supplier_document',
    'folder_code' => 'supplier_documents'
], [
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
]);

$createDocumentType([
    'id'          => 24,
    'name'        => 'Attestation technique et rapport de contrôle',
    'code'        => 'technical_report_document',
    'folder_code' => 'technical_reports'
], [
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
]);

$createDocumentType([
    'id'          => 25,
    'name'        => 'Document d\'assurance',
    'code'        => 'insurance_document',
    'folder_code' => 'insurance_documents'
], [
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
]);

$createDocumentType([
    'id'          => 33,
    'name'        => 'Document de contentieux',
    'code'        => 'litigation_document',
    'folder_code' => 'litigation_files'
], [
    [
        'name'        => 'Dossier de contentieux',
        'code'        => 'case_file',
        'folder_code' => 'litigation_files'
    ]
]);

$createDocumentType([
    'id'          => 35,
    'name'        => 'Document de sinistre',
    'code'        => 'claim_document',
    'folder_code' => 'claims'
], [
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
]);

$createDocumentType([
    'id'          => 19,
    'name'        => 'Bilan',
    'code'        => 'balance_sheet',
    'folder_code' => 'operation_statements',
    'description' => 'Bilan comptable.'
]);

$createDocumentType([
    'id'          => 20,
    'name'        => 'Récapitulatif des frais',
    'code'        => 'expense_summary',
    'folder_code' => 'operation_statements',
    'description' => 'Synthèse des dépenses courantes.'
]);

$createDocumentType([
    'id'          => 31,
    'name'        => 'Import fournisseurs',
    'code'        => 'suppliers_import',
    'folder_code' => 'imports',
    'description' => 'Fichiers temporaires d\'import de fournisseurs.'
]);

$createDocumentType([
    'id'          => 38,
    'name'        => 'Import banques',
    'code'        => 'banks_import',
    'folder_code' => 'imports',
    'description' => 'Fichiers temporaires d\'import de banques.'
]);

$createDocumentType([
    'id'          => 40,
    'name'        => 'Balance générale des comptes',
    'code'        => 'general_balance',
    'folder_code' => 'operation_statements',
    'description' => 'Document présentant, pour chaque compte, le total des mouvements au débit et au crédit ainsi que le solde sur une période comptable donnée.'
]);

$createDocumentType([
    'id'          => 41,
    'name'        => 'Grand livre',
    'code'        => 'general_ledger',
    'folder_code' => 'operation_statements',
    'description' => 'Document présentant, par compte et par ordre chronologique, l\'ensemble des écritures comptables enregistrées sur une période donnée.'
]);

$createDocumentType([
    'id'          => 42,
    'name'        => 'Opération diverse',
    'code'        => 'misc_operation',
    'folder_code' => 'operation_statements',
    'description' => 'Document justificatif d\'une opération diverse.'
]);
