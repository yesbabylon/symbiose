/**
 * Class Domain manipulations
 *
 */
export class DomainClass {

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
        let normalized = DomainClass.normalize(domain);

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

    public merge(domain:DomainClass) {
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
     * Update domain by parsing conditions and replace any occurence of `$module.`, `$page.`, `$section.` notations with related attributes of given objects.
     *
     * @param values
     * @returns Domain  Returns current instance with updated values.
     */
    public parse(context: any = {}) {
        for(let clause of this.clauses) {
            for(let condition of clause.conditions) {
                // adapt value according to its syntax ('user.' or 'object.')
                let value = condition.value;

                // handle $module as `value` part
                if(typeof value === 'string' && value.indexOf('$module.') == 0 ) {
                    let target = value.substring('$module.'.length);
                    if(!context.hasOwnProperty(target)) {
                        continue;
                    }
                    value = context[target];
                }
                // handle $chapter as `value` part
                else if(typeof value === 'string' && value.indexOf('$chapter.') == 0) {
                    let target = value.substring('$chapter.'.length);
                    if(!context.hasOwnProperty('$chapter') || !context.$chapter.hasOwnProperty(target)) {
                        continue;
                    }
                    value = context.$chapter[target];
                }
                // handle $page as `value` part
                else if(typeof value === 'string' && value.indexOf('$page.') == 0) {
                    let target = value.substring('$page.'.length);
                    if(!context.hasOwnProperty('$page') || !context.$page.hasOwnProperty(target)) {
                        continue;
                    }
                    value = context.$page[target];
                }
                // handle $section as `value` part
                else if(typeof value === 'string' && value.indexOf('$section.') == 0) {
                    let target = value.substring('$section.'.length);
                    if(!context.hasOwnProperty('$section') || !context.$section.hasOwnProperty(target)) {
                        continue;
                    }
                    value = context.$section[target];
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
    public evaluate(context: any) : boolean {
        console.log('Domain::evaluate', context);
        if(!this.clauses.length) return true;
        let res = false;
        // evaluate clauses (OR) and conditions (AND)
        for(let clause of this.clauses) {
            let c_res = true;
            for(let condition of clause.getConditions()) {

                if(!context.hasOwnProperty(condition.operand)) {
                    return false;
                }
                
                let operand = context[condition.operand];
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

                if(operator == 'in') {
                    if(!Array.isArray(value)) {
                        continue;
                    }
                    cc_res = (value.indexOf(operand) > -1);
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
export default DomainClass;