<?php

namespace App\Modules\Billing\Services;

use Exception;
use SoapClient;
use Illuminate\Support\Facades\Log;

class SriSoapClientService
{
    /**
     * Send signed XML to SRI Reception service.
     *
     * @param string $signedXml The signed XML content.
     * @param string $environment 'pruebas' or 'produccion'.
     * @return array Contains 'status' ('RECIBIDA' or 'DEVUELTA') and 'errors' if any.
     */
    public function sendToReception(string $signedXml, string $environment): array
    {
        if (app()->environment('testing')) {
            return $this->getMockReceptionResponse($signedXml);
        }

        $wsdl = $environment === 'produccion'
            ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl'
            : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';

        try {
            // Options to prevent SOAP timeouts and enable trace
            $options = [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 10,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])
            ];

            $client = new SoapClient($wsdl, $options);
            $params = [
                'xml' => $signedXml
            ];

            $response = $client->validarComprobante($params);
            
            $respuesta = $response->RespuestaRecepcionComprobante ?? null;
            if (!$respuesta) {
                throw new Exception("Respuesta del SRI vacía o inválida al intentar recepcionar.");
            }

            $estado = $respuesta->estado ?? 'DEVUELTA';

            $errors = [];
            if ($estado === 'DEVUELTA' && isset($respuesta->comprobantes->comprobante->mensajes)) {
                $mensajes = $respuesta->comprobantes->comprobante->mensajes;
                $errors = $this->parseSoapMessages($mensajes);
            }

            return [
                'status' => $estado,
                'errors' => $errors,
                'raw'    => $respuesta
            ];

        } catch (Exception $e) {
            Log::error("SRI Reception SOAP Error: " . $e->getMessage());
            return [
                'status' => 'DEVUELTA',
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Query authorization status from SRI Authorization service.
     *
     * @param string $accessKey The 49-digit access key.
     * @param string $environment 'pruebas' or 'produccion'.
     * @return array
     */
    public function queryAuthorization(string $accessKey, string $environment): array
    {
        if (app()->environment('testing')) {
            return $this->getMockAuthorizationResponse($accessKey);
        }

        $wsdl = $environment === 'produccion'
            ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl'
            : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';

        try {
            $options = [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 10,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])
            ];

            $client = new SoapClient($wsdl, $options);
            $params = [
                'claveAccesoComprobante' => $accessKey
            ];

            $response = $client->autorizacionComprobante($params);
            
            $respuesta = $response->RespuestaAutorizacionComprobante ?? null;
            if (!$respuesta) {
                throw new Exception("Respuesta del SRI vacía o inválida al consultar la autorización.");
            }

            $numeroComprobantes = (int)($respuesta->numeroComprobantes ?? 0);
            if ($numeroComprobantes === 0) {
                return [
                    'status' => 'NO AUTORIZADO',
                    'errors' => ['El comprobante no ha sido registrado o procesado aún por el SRI.']
                ];
            }

            // Extract the authorization block (SRI may return list or single object)
            $autorizaciones = $respuesta->autorizaciones->autorizacion ?? null;
            if (is_array($autorizaciones)) {
                $auth = $autorizaciones[0];
            } else {
                $auth = $autorizaciones;
            }

            if (!$auth) {
                return [
                    'status' => 'NO AUTORIZADO',
                    'errors' => ['No se encontraron autorizaciones válidas para la clave de acceso.']
                ];
            }

            $estado = $auth->estado ?? 'NO AUTORIZADO';
            $numeroAutorizacion = $auth->numeroAutorizacion ?? null;
            $fechaAutorizacion = $auth->fechaAutorizacion ?? null;
            $errors = [];

            if ($estado !== 'AUTORIZADO' && isset($auth->mensajes)) {
                $errors = $this->parseSoapMessages($auth->mensajes);
            }

            return [
                'status'              => $estado,
                'number'              => $numeroAutorizacion,
                'date'                => $fechaAutorizacion,
                'xml'                 => $auth->comprobante ?? null,
                'errors'              => $errors,
                'raw'                 => $auth
            ];

        } catch (Exception $e) {
            Log::error("SRI Authorization SOAP Error: " . $e->getMessage());
            return [
                'status' => 'NO AUTORIZADO',
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Parse error messages structure from SRI responses.
     */
    private function parseSoapMessages($mensajesNode): array
    {
        $errors = [];
        $mensajes = $mensajesNode->mensaje ?? [];
        
        if (is_array($mensajes)) {
            foreach ($mensajes as $msg) {
                $errors[] = ($msg->mensaje ?? '') . ' (' . ($msg->informacionAdicional ?? '') . ')';
            }
        } else if (is_object($mensajes)) {
            $errors[] = ($mensajes->mensaje ?? '') . ' (' . ($mensajes->informacionAdicional ?? '') . ')';
        } else if (is_string($mensajes)) {
            $errors[] = $mensajes;
        }

        return array_filter($errors);
    }

    /**
     * Mocks for testing environment.
     */
    private function getMockReceptionResponse(string $signedXml): array
    {
        if (str_contains($signedXml, 'FAIL_RECEPTION')) {
            return [
                'status' => 'DEVUELTA',
                'errors' => ['Error en estructura del RUC de prueba (9999999999999)'],
                'raw'    => null
            ];
        }

        return [
            'status' => 'RECIBIDA',
            'errors' => [],
            'raw'    => null
        ];
    }

    private function getMockAuthorizationResponse(string $accessKey): array
    {
        if (str_contains($accessKey, '000000000')) { // Represents default failed test sequence
            return [
                'status' => 'NO AUTORIZADO',
                'errors' => ['Falta de fondos o base imponible errónea.'],
                'raw'    => null
            ];
        }

        return [
            'status' => 'AUTORIZADO',
            'number' => $accessKey, // Access key acts as authorization number in offline model
            'date'   => now()->toIso8601String(),
            'xml'    => '<factura id="comprobante" version="2.1.0"></factura>',
            'errors' => [],
            'raw'    => null
        ];
    }
}
