export type Menu = {
    name: string;
    access: MenuAccess;
    layout: MenuLayout;
};

export type MenuAccess = {
    groups: string[];
};

export type MenuLayout = {
    items: MenuItem[];
};

export type MenuItem = {
    id: string;
    label: string;
    type: MenuItemType;
    description: string;
    icon: string;
    route?: string;
    children?: MenuItem[];
    context?: MenuItemContext;
};

export type MenuItemType = 'entry' | 'parent';

export type MenuItemContext = {
    entity: string;
    view: string;
};
