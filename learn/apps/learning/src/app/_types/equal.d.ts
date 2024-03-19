export type ModelState = string;

export interface User {
    id: number;
    identifier: number;
    firstname: string;
    lastname: string;
    email: string;
    state: ModelState;
}
