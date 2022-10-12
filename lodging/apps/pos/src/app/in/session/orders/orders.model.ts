export class CashdeskSession {
    public static entity:string = 'lodging\\sale\\pos\\CashdeskSession';

    constructor(
        public id: number = 0,
        public name: string = '',
        public created: Date = new Date(),
        public amount: number = 0,
        public status: string = '',
        public user_id: number = 0,
        public cashdesk_id: number = 0,
        public center_id: number = 0,
        public orders_ids: number[] = []
    ) {}
}

export class Order {
    public static entity:string = 'lodging\\sale\\pos\\Order';

    constructor(
        public id: number = 0,
        public created: Date = new Date(),
        public name: string = '',
        public status: string = '',
        public total: number = 0,
        public price: number = 0,
        public customer_id: any = 0
    ) {}
}