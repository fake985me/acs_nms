// src/cwmpUtils.js

import xml2js from 'xml2js';

const { Parser, processors } = xml2js;

// Parser XML dengan:
// - explicitArray: false → element tunggal jadi object, bukan array
// - stripPrefix: buang prefix namespace (soapenv:, cwmp:, dll)
const parser = new Parser({
  explicitArray: false,
  ignoreAttrs: false,
  tagNameProcessors: [processors.stripPrefix],
});

/**
 * Parse CWMP SOAP XML → { header, methodName, method }
 * methodName hasilnya: "Inform", "TransferComplete", dll.
 */
export async function parseCwmpXml(xml) {
  const obj = await parser.parseStringPromise(xml);

  // Envelope bisa jadi:
  // - Envelope
  // - soapenv:Envelope (sebelum stripPrefix)
  // Setelah stripPrefix, harusnya jadi "Envelope"
  const envelope =
    obj.Envelope ||
    obj.envelope ||
    obj['soapenv:Envelope'] ||
    obj['SOAP-ENV:Envelope'] ||
    obj;

  const header = envelope.Header || envelope.header || {};
  const body = envelope.Body || envelope.body;

  if (!body || typeof body !== 'object') {
    console.warn('parseCwmpXml: body tidak ditemukan');
    return { header, methodName: null, method: null };
  }

  // Ambil key pertama di body yang bukan Fault
  const keys = Object.keys(body).filter(
    (k) => k !== 'Fault' && k !== 'faultcode' && k !== 'faultstring',
  );

  if (keys.length === 0) {
    console.warn(
      'parseCwmpXml: tidak ada method di Body (mungkin empty POST polling)',
    );
    return { header, methodName: null, method: null };
  }

  const methodName = keys[0]; // Contoh: "Inform", "TransferComplete"
  const method = body[methodName];

  return { header, methodName, method };
}

/**
 * Bangun SOAP Envelope dari body & header object
 * body: object method CWMP (misal { "cwmp:InformResponse": {...} })
 * header: object header (misal { "cwmp:ID": {...} })
 */
export function buildCwmpEnvelope(body, header = {}) {
  const builder = new xml2js.Builder({
    headless: false,
    xmldec: { version: '1.0', encoding: 'UTF-8' },
    renderOpts: { pretty: false },
  });

  const envelopeObj = {
    'soap-env:Envelope': {
      $: {
        'xmlns:soap-env': 'http://schemas.xmlsoap.org/soap/envelope/',
        'xmlns:cwmp': 'urn:dslforum-org:cwmp-1-0',
        'xmlns:xsd': 'http://www.w3.org/2001/XMLSchema',
        'xmlns:xsi': 'http://www.w3.org/2001/XMLSchema-instance',
      },
      'soap-env:Header': header,
      'soap-env:Body': body,
    },
  };

  return builder.buildObject(envelopeObj);
}

/**
 * Bangun InformResponse sederhana
 */
export function buildInformResponse(headerId) {
  const header = {};

  if (headerId) {
    header['cwmp:ID'] = {
      $: { 'soap-env:mustUnderstand': '1' },
      _: headerId,
    };
  }

  const body = {
    'cwmp:InformResponse': {
      MaxEnvelopes: 1,
    },
  };

  return buildCwmpEnvelope(body, header);
}
