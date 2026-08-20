# Inventory

This package allows to manage IT inventory products, they are composed of:

* Servers and instances
* Services, subscriptions and pieces of software

Some are for internal use, others are for customers use.
Subscriptions for customers can generate receivables, they can be invoiced.

The `inventory` package depends on the `sale` and `purchase` packages, which are defined in the manifest file.

## Classes

### Product
The inventory comprises services, software, and servers. <br>
Ownership of these items may lie with either the company or the customer

### Access
The access details encompass the access type (e.g., HTTP, SSH), along with associated URL components like the IP address or hostname, and connection port for the server, services, instance, and software.

### Software
The software includes its name, description, edition, and version, along with details regarding the software model, instance, server, service, and customer ownership.

### Software Model 
The software model encompasses its name, description, edition, and version, providing a comprehensive overview of the general software.

### Subscription
The Subscription contains the relationship with a specific service, indicating whether it is for internal use or with an external provider, as well as billing information and the associated customer.

### Subscription Entry
The Subscription entries describe the process of invoicing the client for the service. They are associated with both the customer and the service provider.

### Service
The Service allows comprehensive management of services, facilitating billing, automatic renewal, association with products and clients, and linking with external and internal providers. Additionally, it documents specific details and manages related accesses and software.

### Service  Model 
The Service Model manages and organizes service models, their billing, subscriptions, and associations with providers.

### Service Provider
The Service Provider extends the Supplier class and manages relevant information about the service provider, including login URL, provider category, and associated services.

### Service Provider Category
The Service Provider Category enables the organization and classification of various service providers. Each category can accommodate multiple service providers, simplifying system management and categorization.

### Detail 
The Detail manages specific elements related to services or products, providing properties for identification, description, value, and links to service and category.

### Detail Category 
The DetailCategory organizes and manages categories of details related to services or products, enabling efficient classification and organization of information.

### Server
The Server includes its name, description, type, access, instances, associated products, IP addresses, and installed software.

### IP addresses
 The IP addresses (v4 or v6) associated with servers. It includes properties such as ip_v4, ip_v6, name, description, visibility, and the server it is attached to.
 
### Instance
The Instance manages instances of services or products within the system, describing their characteristics such as type, version, and URL. It also provides information on accessing the instance and the software running on it.
