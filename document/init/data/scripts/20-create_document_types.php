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
    $typeNames = $type['name'];
    $type['name'] = $typeNames['fr'];

    if(empty($type['description'])) {
        $type['description'] = 'Type de document : ' . $type['name'] . '.';
    }

    $type['has_subtype'] = !empty($subtypes);
    $documentType = DocumentType::create($type, 'fr')->first();

    foreach($typeNames as $lang => $name) {
        if($lang !== 'fr') {
            DocumentType::id($documentType['id'])->update(['name' => $name], $lang);
        }
    }

    foreach($subtypes as $subtype) {
        $subtypeNames = $subtype['name'];
        $subtype['name'] = $subtypeNames['fr'];

        if(empty($subtype['description'])) {
            $subtype['description'] = 'Sous-type de document : ' . $subtype['name'] . '.';
        }

        $subtype['document_type_id'] = $documentType['id'];
        $documentSubtype = DocumentSubtype::create($subtype, 'fr')->first();

        foreach($subtypeNames as $lang => $name) {
            if($lang !== 'fr') {
                DocumentSubtype::id($documentSubtype['id'])->update(['name' => $name], $lang);
            }
        }
    }
};

$createDocumentType([
        'name'        => [
            'fr' => 'Document de référence',
            'en' => 'Reference document',
            'nl' => 'Referentiedocument'
        ],
        'code'        => 'reference_document',
        'folder_code' => 'reference_documents'
    ],
    [
        [
            'name'        => [
                'fr' => 'Règlement d\'ordre intérieur',
                'en' => 'Internal rules',
                'nl' => 'Reglement van interne orde'
            ],
            'code'        => 'internal_rules',
            'folder_code' => 'reference_documents'
        ],
        [
            'name'        => [
                'fr' => 'Plans',
                'en' => 'Plans',
                'nl' => 'Plannen'
            ],
            'code'        => 'plan',
            'folder_code' => 'reference_documents'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Document d\'assemblée générale',
            'en' => 'General assembly document',
            'nl' => 'Document algemene vergadering'
        ],
        'code'        => 'general_assembly_document',
        'folder_code' => 'general_meetings',
        'description' => 'Document relatif à une assemblée générale.'
    ],
    [
        [
            'name'        => [
                'fr' => 'Convocation',
                'en' => 'Invitation',
                'nl' => 'Uitnodiging'
            ],
            'code'        => 'invite',
            'folder_code' => 'general_meetings'
        ],
        [
            'name'        => [
                'fr' => 'Annexe de convocation',
                'en' => 'Invitation appendix',
                'nl' => 'Bijlage bij de uitnodiging'
            ],
            'code'        => 'appendix',
            'folder_code' => 'general_meetings'
        ],
        [
            'name'        => [
                'fr' => 'Liste des présences',
                'en' => 'Attendance register',
                'nl' => 'Aanwezigheidslijst'
            ],
            'code'        => 'attendance_register',
            'folder_code' => 'general_meetings'
        ],
        [
            'name'        => [
                'fr' => 'Procès-verbal d\'assemblée générale',
                'en' => 'General assembly minutes',
                'nl' => 'Notulen van de algemene vergadering'
            ],
            'code'        => 'minutes',
            'folder_code' => 'general_meetings'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Document bancaire',
            'en' => 'Banking document',
            'nl' => 'Bankdocument'
        ],
        'code'        => 'bank_document',
        'folder_code' => 'bank_documents'
    ],
    [
        [
            'name'        => [
                'fr' => 'Ouverture de compte',
                'en' => 'Account opening',
                'nl' => 'Rekeningopening'
            ],
            'code'        => 'bank_account_opening',
            'folder_code' => 'bank_documents'
        ],
        [
            'name'        => [
                'fr' => 'Emprunt bancaire',
                'en' => 'Bank loan',
                'nl' => 'Banklening'
            ],
            'code'        => 'bank_loan',
            'folder_code' => 'bank_documents'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Appel de fonds',
            'en' => 'Funding request',
            'nl' => 'Opvraging van fondsen'
        ],
        'code'        => 'fund_request',
        'folder_code' => 'operation_statements',
        'description' => 'Document sollicitant un paiement d\'avance ou une participation.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Décompte de charges',
            'en' => 'Expense statement',
            'nl' => 'Kostenafrekening'
        ],
        'code'        => 'expense_statement',
        'folder_code' => 'operation_statements',
        'description' => 'Détail ou synthèse des charges engagées.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Rappel de paiement',
            'en' => 'Payment reminder',
            'nl' => 'Betalingsherinnering'
        ],
        'code'        => 'payment_reminder',
        'folder_code' => 'operation_statements'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Extrait bancaire (CODA)',
            'en' => 'Bank statement (CODA)',
            'nl' => 'Bankafschrift (CODA)'
        ],
        'code'        => 'bank_statement',
        'folder_code' => 'bank_statements',
        'description' => 'Document reprenant les mouvements d\'un compte bancaire.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Facture fournisseur',
            'en' => 'Supplier invoice',
            'nl' => 'Leveranciersfactuur'
        ],
        'code'        => 'supplier_invoice',
        'folder_code' => 'supplier_invoices',
        'description' => 'Document comptable à comptabiliser et à réconcilier.'
    ],
    [
        [
            'name'        => [
                'fr' => 'Facture d\'acompte',
                'en' => 'Advance invoice',
                'nl' => 'Voorschotfactuur'
            ],
            'code'        => 'advance_invoice',
            'folder_code' => 'supplier_invoices'
        ],
        [
            'name'        => [
                'fr' => 'Facture de régularisation',
                'en' => 'Adjustment invoice',
                'nl' => 'Regularisatiefactuur'
            ],
            'code'        => 'adjustment_invoice',
            'folder_code' => 'supplier_invoices'
        ],
        [
            'name'        => [
                'fr' => 'Facture de prestations hors contrat',
                'en' => 'Invoice for services outside contract',
                'nl' => 'Factuur voor prestaties buiten contract'
            ],
            'code'        => 'off_contract',
            'folder_code' => 'supplier_invoices'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Contrat fournisseur',
            'en' => 'Supplier contract',
            'nl' => 'Leverancierscontract'
        ],
        'code'        => 'supplier_contract',
        'folder_code' => 'supplier_contracts',
        'description' => 'Engagement contractuel formel avec un fournisseur.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Document fournisseur',
            'en' => 'Supplier document',
            'nl' => 'Leveranciersdocument'
        ],
        'code'        => 'supplier_document',
        'folder_code' => 'supplier_documents'
    ],
    [
        [
            'name'        => [
                'fr' => 'Mandat de domiciliation fournisseur',
                'en' => 'Supplier direct debit mandate',
                'nl' => 'Domiciliëringsmandaat leverancier'
            ],
            'code'        => 'supplier_direct_debit_mandate',
            'folder_code' => 'supplier_documents'
        ],
        [
            'name'        => [
                'fr' => 'Informations bancaires fournisseur',
                'en' => 'Supplier bank details',
                'nl' => 'Bankgegevens leverancier'
            ],
            'code'        => 'supplier_bank_details',
            'folder_code' => 'supplier_documents'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Attestation technique et rapport de contrôle',
            'en' => 'Technical certificate and inspection report',
            'nl' => 'Technisch attest en keuringsverslag'
        ],
        'code'        => 'technical_report_document',
        'folder_code' => 'technical_reports'
    ],
    [
        [
            'name'        => [
                'fr' => 'Rapport de contrôle',
                'en' => 'Inspection report',
                'nl' => 'Keuringsverslag'
            ],
            'code'        => 'inspection_report',
            'folder_code' => 'technical_reports'
        ],
        [
            'name'        => [
                'fr' => 'Attestation périodique',
                'en' => 'Periodic certificate',
                'nl' => 'Periodiek attest'
            ],
            'code'        => 'periodic_certificate',
            'folder_code' => 'technical_reports'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Document d\'assurance',
            'en' => 'Insurance document',
            'nl' => 'Verzekeringsdocument'
        ],
        'code'        => 'insurance_document',
        'folder_code' => 'insurance_documents'
    ],
    [
        [
            'name'        => [
                'fr' => 'Assurance responsabilité civile',
                'en' => 'Civil liability insurance',
                'nl' => 'Burgerlijke aansprakelijkheidsverzekering'
            ],
            'code'        => 'civil_liability',
            'folder_code' => 'insurance_documents'
        ],
        [
            'name'        => [
                'fr' => 'Assurance protection juridique',
                'en' => 'Legal protection insurance',
                'nl' => 'Rechtsbijstandsverzekering'
            ],
            'code'        => 'legal_protection',
            'folder_code' => 'insurance_documents'
        ],
        [
            'name'        => [
                'fr' => 'Assurance accident du travail',
                'en' => 'Workplace accident insurance',
                'nl' => 'Arbeidsongevallenverzekering'
            ],
            'code'        => 'work_accident',
            'folder_code' => 'insurance_documents'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Document de contentieux',
            'en' => 'Litigation document',
            'nl' => 'Geschillendocument'
        ],
        'code'        => 'litigation_document',
        'folder_code' => 'litigation_files'
    ],
    [
        [
            'name'        => [
                'fr' => 'Dossier de contentieux',
                'en' => 'Litigation case file',
                'nl' => 'Geschillendossier'
            ],
            'code'        => 'case_file',
            'folder_code' => 'litigation_files'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Document de sinistre',
            'en' => 'Claim document',
            'nl' => 'Schadedocument'
        ],
        'code'        => 'claim_document',
        'folder_code' => 'claims'
    ],
    [
        [
            'name'        => [
                'fr' => 'Rapport d\'expertise sinistre',
                'en' => 'Claim assessment report',
                'nl' => 'Schade-expertiserapport'
            ],
            'code'        => 'expert_report',
            'folder_code' => 'claims'
        ],
        [
            'name'        => [
                'fr' => 'Procès-verbal d\'indemnisation',
                'en' => 'Compensation report',
                'nl' => 'Proces-verbaal van schadevergoeding'
            ],
            'code'        => 'claim_report',
            'folder_code' => 'claims'
        ]
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Bilan',
            'en' => 'Balance sheet',
            'nl' => 'Balans'
        ],
        'code'        => 'balance_sheet',
        'folder_code' => 'operation_statements',
        'description' => 'Bilan comptable.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Récapitulatif des frais',
            'en' => 'Expense summary',
            'nl' => 'Kostenoverzicht'
        ],
        'code'        => 'expense_summary',
        'folder_code' => 'operation_statements',
        'description' => 'Synthèse des dépenses courantes.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Import fournisseurs',
            'en' => 'Supplier import',
            'nl' => 'Leveranciersimport'
        ],
        'code'        => 'suppliers_import',
        'folder_code' => 'imports',
        'description' => 'Fichiers d\'import Fournisseurs  (temporaire).'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Import banques',
            'en' => 'Bank import',
            'nl' => 'Bankenimport'
        ],
        'code'        => 'banks_import',
        'folder_code' => 'imports',
        'description' => 'Fichiers d\'import Banques (temporaire).'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Import Contacts',
            'en' => 'Contact import',
            'nl' => 'Contactimport'
        ],
        'code'        => 'contacts_import',
        'folder_code' => 'imports',
        'description' => 'Fichiers d\'imports Contacts (temporaire).'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Balance générale des comptes',
            'en' => 'Trial balance',
            'nl' => 'Proef- en saldibalans'
        ],
        'code'        => 'general_balance',
        'folder_code' => 'operation_statements',
        'description' => 'Document présentant, pour chaque compte, le total des mouvements au débit et au crédit ainsi que le solde sur une période comptable donnée.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Grand livre',
            'en' => 'General ledger',
            'nl' => 'Grootboek'
        ],
        'code'        => 'general_ledger',
        'folder_code' => 'operation_statements',
        'description' => 'Document présentant, par compte et par ordre chronologique, l\'ensemble des écritures comptables enregistrées sur une période donnée.'
    ]);

$createDocumentType([
        'name'        => [
            'fr' => 'Opération diverse',
            'en' => 'Miscellaneous transaction',
            'nl' => 'Diverse verrichting'
        ],
        'code'        => 'misc_operation',
        'folder_code' => 'operation_statements',
        'description' => 'Document justificatif d\'une opération diverse.'
    ]);
