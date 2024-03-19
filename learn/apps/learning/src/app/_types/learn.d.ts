import * as Equal from './equal';
import { User } from './equal';

export type Course = {
    id: number;
    name: string;
    title: string;
    subtitle: string;
    description: string;
    creator?: number;
    modules: Module[];
    modified?: string;
    langs_ids?: Record<string, any>[];
    state?: Equal.ModelState;
};

export type Module = {
    id: number;
    identifier: number;
    title: string;
    page_count: number;
    description: string;
    duration: number;
    modified: string;
    state: Equal.ModelState;
    creator?: number;
    chapters: Chapter[];
    order: number;
    chapter_count?: number;
    course_id: number | Course;
    link?: string;
};

export type Chapter = {
    id: number;
    identifier: number;
    modified: string;
    title: string;
    duration: number;
    description: string;
    module_id: number;
    state: Equal.ModelState;
    page_count: number;
    creator?: number;
    order: number;
    pages: Page[];
};

export type Page = {
    id: number;
    identifier: number;
    modified: string;
    next_active: string;
    order: number;
    leaves: Leaf[];
    chapter_id: number;
};

export type Leaf = {
    id: number;
    identifier: number;
    modified: string;
    background_image: null | string;
    background_opacity: number;
    background_stretch: boolean;
    contrast: string;
    oder: number;
    state: Equal.ModelState;
    visible: string;
    groups: Group[];
};

export type Group = {
    id: number;
    identifier: number;
    modified: string;
    order: number;
    state: Equal.ModelState;
    visible: string;
    direction: string;
    fixed: boolean;
    row_span: number;
    widgets: Widget[];
};

export type Widget = {
    id: number;
    identifier: number;
    modified: string;
    order: number;
    content: string;
    type: WidgetType;
    section_id: number | null;
    image_url: string | null;
    video_url: string | null;
    sound_url: string | null;
    has_separator_left: boolean;
    has_separator_right: boolean;
    align: string;
    on_click: string;
    state: Equal.ModelState;
};

export type WidgetType =
    | 'text'
    | 'code'
    | 'chapter_number'
    | 'chapter_title'
    | 'chapter_description'
    | 'page_title'
    | 'headline'
    | 'subtitle'
    | 'head_text'
    | 'tooltip'
    | 'sound'
    | 'video'
    | 'image_popup'
    | 'first_capital'
    | 'submit_button'
    | 'selector'
    | 'selector_wide'
    | 'selector_yes_no'
    | 'selector_choice'
    | 'selector_section'
    | 'selector_section_wide'
    | 'selector_popup';

export type UserStatus = {
    course_id: number;
    module_id: number;
    user_id: number;
    chapter_index: number;
    page_index: number;
    page_count: number;
    is_complete: boolean;
};

export type UserStatement = {
    user: User;
    userAccess: any;
    userInfo: any;
    userStatus: UserStatus[];
};
