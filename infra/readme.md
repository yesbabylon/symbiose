@customer
    * name (memo identifiant)
    * coordonnées d'entreprise (oranisation_name, organisation_phone, organisation_VAT, address_street, address_country, address_city, address_zip, contact_firstname, contact_lastname, contact_gender, contact_birthdate)
    * description
    * contacts (o2m)
	* nom
	* email
	* fonction
    * références internes
	@provider
	local_account (identifiant CF + email) @account
	registrar_account @account
        cloud_provider_account @account
    * produits (o2m)
        * name (FQDN)
        * description
	* @instances (o2m)
        * @services (mass-mailing, SSL certificate, API providers, ...) [services rattachés à un produit]
    * @services [services globaux pour le client]
	


@instance
    type (prod / dev)
    @software (m2o)
    URL
    @server [emplacement]
    @access (o2m)

@server
    hostname
    IPv4
    IPv6
    description (tech specs, hosting plan, ...)
    @access

@access
    protocol (smtp, pop, ftp, ssh, git, admin)
    host (IP or hostname)
    port
    username
    password



@software
    name
    edition (CE/EE)
    version

@service
    description
    @provider
    @access

@provider
    name
    description
    login_URL

