// src/rpcs.js - TR-069 RPC builders

import crypto from 'crypto';
import { buildCwmpEnvelope } from './cwmpUtils.js';

/**
 * Build Reboot RPC
 */
export function buildRebootRpc(commandKey = null) {
    const key = commandKey || crypto.randomUUID();
    const body = {
        'cwmp:Reboot': {
            CommandKey: key,
        },
    };
    return buildCwmpEnvelope(body);
}

/**
 * Build SetParameterValues RPC
 */
export function buildSetParameterValuesRpc(parameters, parameterKey = null) {
    const key = parameterKey || crypto.randomUUID();

    const parameterList = Object.entries(parameters).map(([name, value]) => {
        // Determine XSD type
        let xsdType = 'xsd:string';
        if (typeof value === 'boolean') {
            xsdType = 'xsd:boolean';
            value = value ? '1' : '0';
        } else if (typeof value === 'number') {
            xsdType = Number.isInteger(value) ? 'xsd:int' : 'xsd:double';
            value = String(value);
        }

        return {
            Name: name,
            Value: {
                _: String(value),
                $: { 'xsi:type': xsdType },
            },
        };
    });

    const body = {
        'cwmp:SetParameterValues': {
            ParameterList: {
                $: { 'xsi:type': 'cwmp:ParameterValueList' },
                ParameterValueStruct: parameterList,
            },
            ParameterKey: key,
        },
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build GetParameterValues RPC
 */
export function buildGetParameterValuesRpc(parameterNames = []) {
    const names = Array.isArray(parameterNames) ? parameterNames : [parameterNames];

    const body = {
        'cwmp:GetParameterValues': {
            ParameterNames: {
                $: { 'xsi:type': 'cwmp:ParameterNameList' },
                string: names,
            },
        },
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build GetParameterNames RPC
 */
export function buildGetParameterNamesRpc(parameterPath = 'InternetGatewayDevice.', nextLevel = false) {
    const body = {
        'cwmp:GetParameterNames': {
            ParameterPath: parameterPath,
            NextLevel: nextLevel ? '1' : '0',
        },
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build Download RPC (for firmware/config files)
 */
export function buildDownloadRpc(options) {
    const {
        url,
        fileType = '1 Firmware Upgrade Image',
        fileSize = 0,
        targetFileName = '',
        username = '',
        password = '',
        delaySeconds = 0,
        commandKey = null,
    } = options;

    const key = commandKey || crypto.randomUUID();

    const body = {
        'cwmp:Download': {
            CommandKey: key,
            FileType: fileType,
            URL: url,
            Username: username,
            Password: password,
            FileSize: fileSize,
            TargetFileName: targetFileName,
            DelaySeconds: delaySeconds,
            SuccessURL: '',
            FailureURL: '',
        },
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build Upload RPC
 */
export function buildUploadRpc(options) {
    const {
        url,
        fileType = '1 Vendor Configuration File',
        username = '',
        password = '',
        delaySeconds = 0,
        commandKey = null,
    } = options;

    const key = commandKey || crypto.randomUUID();

    const body = {
        'cwmp:Upload': {
            CommandKey: key,
            FileType: fileType,
            URL: url,
            Username: username,
            Password: password,
            DelaySeconds: delaySeconds,
        },
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build FactoryReset RPC
 */
export function buildFactoryResetRpc() {
    const body = {
        'cwmp:FactoryReset': {},
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build AddObject RPC
 */
export function buildAddObjectRpc(objectName, parameterKey = null) {
    const key = parameterKey || crypto.randomUUID();

    const body = {
        'cwmp:AddObject': {
            ObjectName: objectName,
            ParameterKey: key,
        },
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build DeleteObject RPC
 */
export function buildDeleteObjectRpc(objectName, parameterKey = null) {
    const key = parameterKey || crypto.randomUUID();

    const body = {
        'cwmp:DeleteObject': {
            ObjectName: objectName,
            ParameterKey: key,
        },
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build GetRPCMethods RPC
 */
export function buildGetRPCMethodsRpc() {
    const body = {
        'cwmp:GetRPCMethods': {},
    };

    return buildCwmpEnvelope(body);
}

/**
 * Build RPC from task object
 */
export function buildRpcFromTask(task) {
    const taskType = task.type;
    let payload = {};

    // Parse JSON payload if exists
    if (task.payload && typeof task.payload === 'string') {
        try {
            payload = JSON.parse(task.payload);
        } catch (e) {
            console.error('Failed to parse task payload:', e);
        }
    } else if (task.payload && typeof task.payload === 'object') {
        payload = task.payload;
    }

    switch (taskType) {
        case 'reboot':
            return buildRebootRpc(task.id ? String(task.id) : null);

        case 'setParameterValues':
            return buildSetParameterValuesRpc(
                payload.parameterValues || payload.parameters || {},
                task.id ? String(task.id) : null
            );

        case 'getParameterValues':
            return buildGetParameterValuesRpc(
                payload.parameterNames || ['InternetGatewayDevice.']
            );

        case 'getParameterNames':
            return buildGetParameterNamesRpc(
                payload.parameterPath || 'InternetGatewayDevice.',
                payload.nextLevel || false
            );

        case 'download':
            return buildDownloadRpc({
                ...payload,
                commandKey: task.id ? String(task.id) : null,
            });

        case 'upload':
            return buildUploadRpc({
                ...payload,
                commandKey: task.id ? String(task.id) : null,
            });

        case 'factoryReset':
            return buildFactoryResetRpc();

        case 'addObject':
            return buildAddObjectRpc(
                payload.objectName,
                task.id ? String(task.id) : null
            );

        case 'deleteObject':
            return buildDeleteObjectRpc(
                payload.objectName,
                task.id ? String(task.id) : null
            );

        case 'getRPCMethods':
            return buildGetRPCMethodsRpc();

        default:
            throw new Error(`Unknown task type: ${taskType}`);
    }
}
