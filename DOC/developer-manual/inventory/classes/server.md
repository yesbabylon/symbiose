
# Server

## Server

### Properties

| Property         | Type        | Description                                  | Value(s)               |
|------------------|-------------|----------------------------------------------|------------------------|
| name             | string      | Internal identification ex. trg.be-master    |                        |
| description      | string      | Short description of the Server              |                        |
| server_type      | string      | Type of the server                           | (front, node, storage) |
| accesses_ids     | one2many    | Access information to the server             |                        |
| instances_ids    | one2many    | Instances running on the server              |                        |
| products_ids     | many2many   | List of products that are using the server   |                        |
| ip_address_ids   | one2many    | IP Addresses of the server                   |                        |
| softwares_ids    | one2many    | List of Software installed on the server     |                        |

## IpAddress

### Properties

| Property               | Type       | Description                                                    | Value(s) |
|------------------------|------------|----------------------------------------------------------------|----------|
| ip_v4                  | string     | IPV4 address of the server (32 bits).                          |          |
| ip_v6                  | string     | IPV6 address of the server (128 bits)                          |          |
| name                   | string     | Name to ip address (computed from  ip_v4 and ip_v6)            |          |
| description            | string     | Short presentation of the IP address element                   |          |
| visibility             | string     | Visibility indicates how an IP address is exposed to the cloud |          |
| server_id              | many2one   | Server attached to the Ip address                              |          |

## Instance

### Properties

| Property       | Type         | Description                                                       | Value(s)                                        |
|----------------|--------------|-------------------------------------------------------------------|-------------------------------------------------|
| name           | string       | Unique identifier of the instance                                 |                                                 |
| description    | string       | Short description of the instance                                 |                                                 |
| instance_type  | string       | Type of instance                                                  | (development, staging, preview, production)     |
| version        | string       | Branch version                                                    |                                                 |
| branch         | string       | Branch used by the instance                                       |                                                 |
| url            | string       | Front-end home URL                                                |                                                 |
| product_id     | many2one     | Product the instance belongs to                                   |                                                 |
| service_id     | many2one     | Server on which the instance runs                                 |                                                 |
| accesses_ids   | one2many     | Information about how to access the instance                      |                                                 |
| softwares_ids  | one2many     | Information about the list of  software running on the instance   |                                                 |

