import { DateReference } from "./date-reference.class";

/**
 * Class Domain manipulations
 *
 */
export class Domain {

    private clauses: Array<Clause>;

    constructor(domain:Array<any>) {
        this.clauses = new Array<Clause>();
        this.fromArray(domain);
    }

    public fromArray(domain:Array<any>) {
        // reset clauses
        this.clauses.splice(0, this.clauses.length);
        /*
            supported formats :
            1) empty  domain : []
            2) 1 condition only : [ '{operand}', '{operator}', '{value}' ]
            3) 1 clause only (one or more conditions) : [ [ '{operand}', '{operator}', '{value}' ], [ '{operand}', '{operator}', '{value}' ] ]
            4) multiple clauses : [ [ [ '{operand}', '{operator}', '{value}' ], [ '{operand}', '{operator}', '{value}' ] ], [ [ '{operand}', '{operator}', '{value}' ] ] ]
        */
        let normalized = Domain.normalize(domain);

        for(let d_clause of normalized) {
            let clause = new Clause();
            for(let d_condition of d_clause) {
                clause.addCondition(new Condition(d_condition[0], d_condition[1], d_condition[2]))
            }
            this.addClause(clause);
        }
        return this;
    }

    public toArray() {
        let domain = new Array();
        for(let clause of this.clauses) {
            domain.push(clause.toArray());
        }
        return domain;
    }

    public getClauses() {
        return this.clauses;
    }

    public merge(domain:Domain) {
        let res_domain = new Array();
        let domain_a = domain.toArray();
        let domain_b = this.toArray();

        if(domain_a.length <= 0) {
            res_domain = domain_b;
        }
        else if(domain_b.length <= 0) {
            res_domain = domain_a;
        }
        else {
            for(let clause_a of domain_a) {
                for(let clause_b of domain_b) {
                    res_domain.push(clause_a.concat(clause_b));
                }
            }
        }
        return this.fromArray(res_domain);
    }

    private static normalize(domain: Array<any>) {
        if(domain.length <= 0) {
            return [];
        }

        if(!Array.isArray(domain[0])) {
            // single condition
            return [[domain]];
        }
        else {
            if( domain[0].length <= 0)  {
                return [];
            }
            if(!Array.isArray(domain[0][0])) {
                // single clause
                return [domain];
            }
        }
        return domain;
    }

    /**
     * Add a clause at the Domain level : the clause is appened to the Domain
     */
    public addClause(clause: Clause) {
        this.clauses.push(clause);
    }

    /**
     * Add a condition at the Domain level : the condition is added to each clause of the Domain
     */
    public addCondition(condition: Condition) {
        for(let clause of this.clauses) {
            clause.addCondition(condition);
        }
    }

    /**
     * Update domain by parsing conditions and replace any occurence of `object.` and `user.` notations with related attributes of given objects.
     *
     * @param values
     * @returns Domain  Returns current instance with updated values.
     */
    public parse(object: any = {}, user: any = {}) {
        for(let clause of this.clauses) {
            for(let condition of clause.conditions) {
                // adapt value according to its syntax ('user.' or 'object.')
                let value = condition.value;

                // handle object references as `value` part
                if(typeof value === 'string' && value.indexOf('object.') == 0 ) {
                    let target = value.substring('object.'.length);
                    if(!object || !object.hasOwnProperty(target)) {
                        continue;
                    }
                    let tmp = object[target];
                    // target points to an object with subfields
                    if(typeof tmp === 'object' && !Array.isArray(tmp)) {
                        if(tmp.hasOwnProperty('id')) {
                            value = tmp.id;
                        }
                        else if(tmp.hasOwnProperty('name')) {
                            value = tmp.name;
                        }
                        else {
                            continue;
                        }
                    }
                    else {
                        value = object[target];
                    }
                }
                // handle user references as `value` part
                else if(typeof value === 'string' && value.indexOf('user.') == 0) {
                    let target = value.substring('user.'.length);
                    if(!user || !user.hasOwnProperty(target)) {
                        continue;
                    }
                    value = user[target];
                }
                else if(typeof value === 'string' && value.indexOf('date.') == 0) {
                    value = (new DateReference(value)).getDate().toISOString();
                }

                condition.value = value;
            }
        }
        return this;
    }

    /**
     * Evaluate domain for a given object.
     * Object structure has to comply with the operands mentionned in the conditions of the domain. If no, related conditions are ignored (skipped).
     *
     * @param object
     * @returns boolean Return true if the object matches the domain, false otherwise.
     */
    public evaluate(object: any) : boolean {
        let res = false;
        // parse any reference to object in conditions
        this.parse(object);
        // evaluate clauses (OR) and conditions (AND)
        for(let clause of this.clauses) {
            let c_res = true;
            for(let condition of clause.getConditions()) {

                if(!object.hasOwnProperty(condition.operand)) {
                    continue;
                }

                let operand = object[condition.operand];
                let operator = condition.operator;
                let value = condition.value;

                let cc_res: boolean;

                // handle special cases
                if(operator == '=') {
                    operator = '==';
                }
                else if(operator == '<>') {
                    operator = '!=';
                }

                if(operator == 'is' && typeof value == 'number') {
                    operator = '==';
                }

                if(operator == 'is') {
                    if( value === true ) {
                        cc_res = operand;
                    }
                    else if( [false, null, 'null', 'empty'].includes(value) ) {
                        cc_res = (['', false, undefined, null].includes(operand) || (Array.isArray(operand) && !operand.length) );
                    }
                    else {
                        continue;
                    }
                }
                else if(operator == 'in') {
                    if(!Array.isArray(value)) {
                        continue;
                    }
                    cc_res = (value.indexOf(operand) > -1);
                }
                else if(operator == 'not in') {
                    if(!Array.isArray(value)) {
                        continue;
                    }
                    cc_res = (value.indexOf(operand) == -1);
                }
                else {
                    let c_condition = "( '" + operand + "' "+operator+" '" + value + "')";

                    cc_res = <boolean>eval(c_condition);
                }
                c_res = c_res && cc_res;
            }
            res = res || c_res;
        }
        return res;
    }

}

export class Clause {
    public conditions: Array<Condition>;

    constructor(conditions:Array<Condition> = []) {
        if(conditions.length == 0) {
            this.conditions = new Array<Condition>();
        }
        else {
            this.conditions = conditions;
        }
    }

    public addCondition(condition: Condition) {
        this.conditions.push(condition);
    }

    public getConditions() {
        return this.conditions;
    }

    public toArray() {
        let clause = new Array();
        for(let condition of this.conditions) {
            clause.push(condition.toArray());
        }
        return clause;
    }
}

export class Condition {
    public operand:any;
    public operator:any;
    public value:any;

    constructor(operand: any, operator: any, value: any) {
        this.operand = operand;
        this.operator = operator;
        this.value = value;
    }

    public toArray() {
        let condition = new Array();
        condition.push(this.operand);
        condition.push(this.operator);
        condition.push(this.value);
        return condition;
    }

    public getOperand() {
        return this.operand;
    }

    public getOperator() {
        return this.operator;
    }

    public getValue() {
        return this.value;
    }

}

export class Reference {

    private value: string;

    constructor(value:string) {
        this.value = value;
    }

    /**
     * Update value by replacing any occurence of `object.` and `user.` notations with related attributes of given objects.
     *
     * @param object        An entity object to serve as reference.
     * @param user          A user object to serve as reference.
     * @returns string      The result of the parsing.
     */
    public parse(object: any, user: any = {}): string {
        let result = this.value;
        if(this.value.indexOf('object.') == 0 ) {
            let target = this.value.substring('object.'.length);
            if(object && object.hasOwnProperty(target)) {
                let tmp = object[target];
                // target points to an object with subfields
                if(typeof tmp === 'object' && !Array.isArray(tmp)) {
                    if(tmp.hasOwnProperty('id')) {
                        result = tmp.id;
                    }
                    else if(tmp.hasOwnProperty('name')) {
                        result = tmp.name;
                    }
                }
                else {
                    result  = object[target];
                }
            }
        }
        // handle user references as `value` part
        else if(this.value.indexOf('user.') == 0) {
            let target = this.value.substring('user.'.length);
            if(user && user.hasOwnProperty(target)) {
                result = user[target];
            }
        }
        return result;
    }
}

export default Domain;