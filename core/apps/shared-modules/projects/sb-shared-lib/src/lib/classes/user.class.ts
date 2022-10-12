class Identity {
    constructor(
        public id: number = 0,
        public name: string = '',
        public firstname: string = '',
        public lastname: string = ''
    ) {}
}

export class UserClass {
    constructor(
        public id: number = 0,
        public name: string = '',
        public login: string = '',
        public identity_id : Identity = new Identity(),
        public language: string = 'fr',
        public organisation_id: number = 1,
        public avatar: string = '',
        public apps: string[] = ['booking', 'catalog', 'pos', 'config', 'documents'],
        public centers_ids: number[] = [],
        public center_offices_ids: number[] = []
    ) {}
}