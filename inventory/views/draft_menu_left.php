<!-- {
  "name": "Inventory menu",
  "layout": {
    "items": [
      {
        "id": "item.product",
        "label": "Products",
        "description": "",
        "icon": "attachment",
        "type": "parent",
        "children": [
          {
            "id": "item.all_Access",
            "type": "entry",
            "label": "All Access",
            "description": "",
            "context": {
              "entity": "inventory\\product\\Access",
              "view": "list.default",
              "order": "id",
              "sort": "asc"
            }
          },
          {
            "id": "item.all_Product",
            "type": "entry",
            "label": "All Products",
            "description": "",
            "context": {
              "entity": "inventory\\product\\Product",
              "view": "list.default",
              "order": "id",
              "sort": "asc"
            }
          },
          {
            "id": "item.service",
            "label": "Services",
            "description": "",
            "icon": "attachment",
            "type": "parent",
            "children": [
              {
                "id": "item.all_Service",
                "type": "entry",
                "label": "All Services",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\service\\Service",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              },
              {
                "id": "item.all_ServiceProvider",
                "type": "entry",
                "label": "All ServiceProviders",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\service\\ServiceProvider",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              },
              {
                "id": "item.all_ServiceProviderCategory",
                "type": "entry",
                "label": "All ServiceProviderCategories",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\service\\ServiceProviderCategory",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              },
              {
                "id": "item.all_ServiceProviderDetailCategory",
                "type": "entry",
                "label": "All ServiceProviderDetailCategories",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\service\\ServiceProviderDetailCategory",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              },
              {
                "id": "item.all_Details",
                "type": "entry",
                "label": "All Details",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\service\\Detail",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              }
            ]
          },
          {
            "id": "item.server",
            "label": "Servers",
            "description": "",
            "icon": "attachment",
            "type": "parent",
            "children": [
              {
                "id": "item.all_Servers",
                "type": "entry",
                "label": "All Servers",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\server\\Server",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              },
              {
                "id": "item.all_Instance",
                "type": "entry",
                "label": "All Instances",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\server\\Instance",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              },
              {
                "id": "item.all_IpAdress",
                "type": "entry",
                "label": "All IpAdress",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\server\\IpAdress",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              },
              {
                "id": "item.all_Softwares",
                "type": "entry",
                "label": "All Softwares",
                "description": "",
                "context": {
                  "entity": "inventory\\product\\server\\Software",
                  "view": "list.default",
                  "order": "id",
                  "sort": "asc"
                }
              }
            ]
          }
        ]
      }
    ]
  }
} -->