<?php

use communication\template\TemplateCategory;

TemplateCategory::create([
        'name' => 'Invoice',
        'code' => 'invoice',
        'description' => "Templates related to invoicing, payment reminders, and billing documents."
    ], 'en')
    ->update([
        'name' => 'Facturation',
        'description' => "Modèles liés à la facturation, aux rappels de paiement et aux documents de facturation."
    ], 'fr');

TemplateCategory::create([
        'name' => 'Contract',
        'code' => 'contract',
        'description' => "Templates related to contracts, legal clauses, and agreements."
    ], 'en')
    ->update([
        'name' => 'Contrat',
        'description' => "Modèles liés aux contrats, clauses légales et conventions."
    ], 'fr');

TemplateCategory::create([
        'name' => 'Support',
        'code' => 'support',
        'description' => "Templates used for customer support messages and ticketing."
    ], 'en')
    ->update([
        'name' => 'Support',
        'description' => "Modèles utilisés pour les messages de support client et le suivi des tickets."
    ], 'fr');

TemplateCategory::create([
        'name' => 'Marketing',
        'code' => 'marketing',
        'description' => "Templates used for marketing campaigns, newsletters, and promotions."
    ], 'en')
    ->update([
        'name' => 'Marketing',
        'description' => "Modèles utilisés pour les campagnes marketing, newsletters et promotions."
    ], 'fr');

TemplateCategory::create([
        'name' => 'General',
        'code' => 'general',
        'description' => "Templates that are not tied to a specific business process."
    ], 'en')
    ->update([
        'name' => 'Général',
        'description' => "Modèles qui ne sont pas liés à un processus métier spécifique."
    ], 'fr');
