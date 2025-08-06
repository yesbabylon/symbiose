<?php

use communication\template\TemplateType;

TemplateType::create([
        'name' => 'Email',
        'code' => 'email',
        'description' => "Templates intended for email communications."
    ], 'en')
    ->update([
        'name' => 'E-mail',
        'description' => "Modèles destinés aux communications par e-mail."
    ], 'fr');

TemplateType::create([
        'name' => 'SMS',
        'code' => 'sms',
        'description' => "Templates intended for short text messages (SMS)."
    ], 'en')
    ->update([
        'name' => 'SMS',
        'description' => "Modèles destinés aux messages texte courts (SMS)."
    ], 'fr');

TemplateType::create([
        'name' => 'Notification',
        'code' => 'notification',
        'description' => "Templates for in-app or push notifications."
    ], 'en')
    ->update([
        'name' => 'Notification',
        'description' => "Modèles pour notifications in-app ou push."
    ], 'fr');

TemplateType::create([
        'name' => 'Form',
        'code' => 'form',
        'description' => "Templates for form sections, instructions, or disclaimers."
    ], 'en')
    ->update([
        'name' => 'Formulaire',
        'description' => "Modèles pour sections de formulaires, instructions ou avertissements."
    ], 'fr');

TemplateType::create([
        'name' => 'Document',
        'code' => 'document',
        'description' => "Templates for structured documents or document sections."
    ], 'en')
    ->update([
        'name' => 'Document',
        'description' => "Modèles pour documents structurés ou sections de documents."
    ], 'fr');
