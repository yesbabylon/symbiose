
# Inventory
  
## Product

### Properties

| Property         | Type       | Description                                                                                                                              |
|------------------|------------|------------------------------------------------------------------------------------------------------------------------------------------|
| name             | string     | Name of the product                                                                                                                      |
| description      | string     | Short presentation of the product                                                                                                        |
| is_internal      | boolean    | Internal products are used by own organisation. Information relating to external products are kept so that the company can work on those |
| customer_id      | many2one   | The client associated with the product                                                                                                   |
| servers_ids      | many2many  | List of server that are used by the product                                                                                              |
| instances_ids    | one2many   | List of instances utilized by the product                                                                                                |
| services_ids     | one2many   | List of services used by the product                                                                                                     |
| softwares_ids    | one2many   | List of software associated with the product                                                                                             |

## Access

### Properties

| Property    | Type       | Description                                                                      | Value(s)                                               |
|-------------|------------|----------------------------------------------------------------------------------|--------------------------------------------------------|
| name        | string     | Name of the access                                                               |                                                        |
| description | string     | Description of the access                                                        |                                                        |
| access_type | string     | Type of the access                                                               | (http, https, ssh, ftp, sftp, pop, smtp, git, docker)  |
| url         | string     | The URL to access (computed from port, host, access_type, username and password) |                                                        |
| host        | string     | IP address or hostname of the server                                             |                                                        |
| port        | string     | Port to connect to (default based on protocol)                                   |                                                        |
| username    | string     | Username of the account related to this access                                   |                                                        |
| password    | string     | Password of the account related to this access                                   |                                                        |
| server_id   | many2one   | Server to which the access belongs                                               |                                                        |
| software_id | many2one   | Software to which the access belongs                                             |                                                        |
| instance_id | many2one   | Instance to which the access belongs                                             |                                                        |
| service_id  | many2one   | Service to which the access belongs                                              |                                                        |

## Software

### Properties

| Property          | Type      | Description                                                          |
|-------------------|-----------|----------------------------------------------------------------------|
| name              | string    | Name of the software                                                 |
| description       | string    | Short presentation of the software                                   |
| edition           | string    | Type of edition (CE/EE/Pro/...)  (computed from software_model_id)   |
| version           | string    | Installed version of the software (computed from software_model_id)  |
| software_model_id | many2one  | Model of the software used by software                               |
| instance_id       | many2one  | The instance contains the software                                   |
| server_id         | many2one  | Server housing the software                                          |
| service_id        | many2one  | Service used by the software                                         |
| customer_id       | many2one  | Customer owning the software                                         |
| accesses_ids      | one2many  | List of access the server information                                |

## Software Model

### Properties

| Property          | Type      | Description                              |
|-------------------|-----------|------------------------------------------|
| name              | string    | Name of the software model               |
| description       | string    | Short presentation of the software model |
| edition           | string    | Type of edition (CE/EE/Pro/...)          |
| version           | string    | Installed version of the software model  |
