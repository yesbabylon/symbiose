import  _ApiService from "./ApiService";
import  _ContextService from "./ContextService";
import  _EnvService from "./EnvService";

/**
 * Singleton instances
 */
export const ApiService = new _ApiService();
export const EnvService = new _EnvService();
export const ContextService = new _ContextService();