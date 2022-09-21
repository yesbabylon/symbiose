# Fiche de réservation

La fiche de réservation permet de consulter et d'éditer certaines informations d'une réservation, accessibles en fonction du statut de celle-ci.


La haut de la fiche reprend les informations générales de la réservation.  
* Le **libellé** correspond au code d'identification de la réservation, attribué automatiquement, et donc le premier chiffre correspond à l'équipe de gestion concernée (de 1 à 8).  
* Le champ **type** renseigne le type de réservation. Il est assigné automatiquement en fonction des services réservés et ne peut pas être modifié manuellement.  
* Le champ **prix TTC** correspond à la somme des groupes de services contractés (séjours et autre) et est mis à jour à chaque modification de ceux-ci.  
* Les dates de **début** ("Du") et de **fin** ("Au") ainsi que les heures de **checkin** et **checkout** correspondent respectivement au premier jour avec l'heure d'arrivée et au dernier jour avec heure de départ, tous séjours confondus (la date de début est donc la date du séjour débutant en premier et la date de fin, la date de fin du séjour se terminant en dernier).  

Des indications supplémentaires peuvent également être visibles sous certaines conditions:  
* **à confirmer** (TBC) : dans le cas où les prix renseignés dans le devis doivent encore être confirmés.  
* **pas d'expiration** : dans le cas où la réservation correspond à une option n'expirant jamais.  

### Description
Lors de la création d'une réservation, le champ "description" est automatiquement rempli avec la description du client renseigné. Au cours des échanges, ce champ peut être mis à jour pour communiquer des informations aux autres personnes (en interne) susceptibles de prendre en charge la réservation.
Les informations ajoutées seront uniquement présentes dans la fiche de la réservation, et la fiche du client ne sera pas modifiée.


### Contacts
L'onglet **"Contacts"** reprend la liste des contacts associés à la réservation.
À la création de la réservation, les contacts du client sélectionné sont automatiquement importés, et un contact supplémentaire correspondant au client de la réservation est également ajouté.
Il y a donc toujours au minimum un contact pour une réservation.
Si des contacts sont ajoutés à la fiche du client après la création de la réservation, ils peuvent être importés en utilisant l'action "IMPORTER LES CONTACTS" disponible dans la liste d'actions, en haut de la fiche et à droite du statut.
Chaque contact peut être assigné à un rôle particulier : "réservation" pour les contacts liés au suivi de la réservation; "contrat" pour les contacts qui recevront une copie du contrats; "facturation" pour les contacts qui recevront une copie de la/des facture(s).
Note : la modification des contacts du client n'impacte pas la liste des contacts de la réservation, et inversément.

### Consommations
L'onglet **"Consommations"** est disponible pour les réservations dont le status est au-delà de "Option" (c'est-à dire "Confirmée" et suivants).
Les consommations sont générées automatiquement. Elles correspondent à tous les services planifiables contractés dans la réservation et sont également reprises dans le Planning.
On distingue les unités locatives (logements, salles, mobilier) et les repas. Dans tous les cas, une consommation correspond à une date, à une plage horaire et à un nombre de personnes auquel elle est assignée.
Lorsque l'on repasse en devis, une boite de dialogue permet de libérer les unités locatives pour la période relative à la réservation.
Note: Le fait que l'onglet ne soit pas visible ne signifie pas nécessrairement que la réservation n'a pas de consommations.

### Composition
L'onglet **"Composition"** est disponible pour les réservations dont le statut est au-delà de "Option".
Les lignes de composition détaillent la distribution des personnes associées à la réservation au sein d'unités locatives.
Un écran spécifique est également accessible via le panneau latéral droit ("Composition") et permet d'importer un fichier excel reprenant les informations des hôtes en créant automatiquement les lignes de composition correspondantes.

### Contrats
Lorsque la réservation est confirmée, un contrat est automatiquement généré.
Il n'est pas possible de modifier ni de supprimer un contrat. Par contre, le contrat est automatiquement annulé lorsque la réservation repasse en "Devis".
Le contrat qui est envoyé au client est toujours le dernier contrat émis.
L'onglet **"Contrats"** reprend l'historique complet des contrats générés pour la réservation.

### Services Réservés
Dans le menu de droite, l'onglet services réservés permet de constituer les services de la réservation.

### Financements
Lorsque la réservation est confirmée, l'onglet "Financements" devient accessible.
Les financements représent les montants attendus de la part du client pour le règlement de la réservation et reprennent l'échéance à laquelle ils sont attendus, ainsi que la part que représente le montant par rapport au total de la réservation.
Il peut y avoir plusieurs financements et, en fonction du type de réservation et du délai restant avant le jour du checkin, les financement sont automatiquement créés selon un des plans de financements prédéfinis.
Les financements seront indiqués comme payés soit manuellement, soit de manière automatique lors de la réconcilation des extraits bancaires.
Il est également possible de créer des financements de manière arbitraire.

