export class CashdeskSession {
    static readonly entity = 'lodging\\sale\\pos\\CashdeskSession';

    constructor(
      public id: number = 0,
      public name: string = '',
      public created: Date = new Date(),
      public amount_opening: number = 0,
      public user_id: number = 0,
      public status: string = '',
      public cashdesk_id: number = 0,
      public orders_ids: [] = [],
      public operations_ids: [] = []
    ) {}
}