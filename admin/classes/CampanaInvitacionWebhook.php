<?php
declare(strict_types=1);

require_once __DIR__ . '/DbCampanaConection.php';
require_once dirname(__DIR__, 2) . '/config_campana_whatsapp.php';

/**
 * Procesa respuestas de botones de la plantilla pregunta_usuario
 * y actualiza estado_invitacion en la campaña.
 */
final class CampanaInvitacionWebhook
{
    private const PAYLOAD_ACEPTA = 'SI, ACEPTO LA INVITACION';
    private const PAYLOAD_RECHAZA = 'NO, GRACIAS';

    public static function procesarPayload(array $data): void
    {
        if (($data['object'] ?? '') !== 'whatsapp_business_account') {
            return;
        }

        foreach ($data['entry'] ?? [] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            foreach ($entry['changes'] ?? [] as $change) {
                if (!is_array($change) || ($change['field'] ?? '') !== 'messages') {
                    continue;
                }
                $value = $change['value'] ?? null;
                if (!is_array($value)) {
                    continue;
                }
                self::procesarValue($value);
            }
        }
    }

    private static function procesarValue(array $value): void
    {
        $phoneNumberId = (string)($value['metadata']['phone_number_id'] ?? '');
        if ($phoneNumberId !== CAMPANA_WHATSAPP_PHONE_NUMBER_ID) {
            return;
        }

        foreach ($value['messages'] ?? [] as $message) {
            if (!is_array($message) || ($message['type'] ?? '') !== 'button') {
                continue;
            }
            $payload = (string)($message['button']['payload'] ?? $message['button']['text'] ?? '');
            $from = (string)($message['from'] ?? '');
            if ($payload === '' || $from === '') {
                continue;
            }
            self::resolverRespuesta($from, $payload);
        }
    }

    private static function resolverRespuesta(string $waFrom, string $payload): void
    {
        $clave = self::normalizarPayload($payload);
        if ($clave === self::PAYLOAD_ACEPTA) {
            self::actualizarEstado($waFrom, 'aceptado', true);
            return;
        }
        if ($clave === self::PAYLOAD_RECHAZA) {
            self::actualizarEstado($waFrom, 'rechazado', false);
        }
    }

    private static function actualizarEstado(string $waFrom, string $nuevoEstado, bool $enviarUsuarioAgregado): void
    {
        $db = new DbCampanaConection();
        try {
            $pdo = $db->openConect();
            $colaborador = self::buscarPorWhatsApp($pdo, $waFrom);
            if ($colaborador === null) {
                return;
            }

            $actual = (string)($colaborador['estado_invitacion'] ?? '');
            if ($actual !== 'por_validar') {
                return;
            }

            $st = $pdo->prepare("
                UPDATE tbl_colaboradores_campana
                   SET estado_invitacion = :estado
                 WHERE id = :id
                   AND estado_invitacion = 'por_validar'
                 LIMIT 1
            ");
            $st->execute([
                ':estado' => $nuevoEstado,
                ':id' => (int)$colaborador['id'],
            ]);

            if ($st->rowCount() !== 1) {
                return;
            }

            if ($enviarUsuarioAgregado) {
                self::enviarUsuarioAgregado($pdo, $colaborador);
                self::avisarCampanaNiveles(
                    (int)($colaborador['invitado_por_id'] ?? 0),
                    (int)$colaborador['id']
                );
            }
        } catch (Throwable $e) {
            error_log('CampanaInvitacionWebhook: ' . $e->getMessage());
        } finally {
            $db->closeConect();
        }
    }

    private static function buscarPorWhatsApp(PDO $pdo, string $waFrom): ?array
    {
        $digitos = preg_replace('/\D+/', '', $waFrom) ?? '';
        if ($digitos === '') {
            return null;
        }
        $tel10 = strlen($digitos) >= 10 ? substr($digitos, -10) : $digitos;

        $st = $pdo->prepare("
            SELECT c.id, c.nombres, c.apellidos, c.telefono, c.invitado_por_id, c.estado_invitacion,
                   p.nombres AS invitador_nombres, p.apellidos AS invitador_apellidos
              FROM tbl_colaboradores_campana c
              LEFT JOIN tbl_colaboradores_campana p ON p.id = c.invitado_por_id
             WHERE REPLACE(REPLACE(REPLACE(c.telefono, '+', ''), ' ', ''), '-', '') = :tel
                OR REPLACE(REPLACE(REPLACE(c.telefono, '+', ''), ' ', ''), '-', '') = :tel10
                OR RIGHT(REPLACE(REPLACE(REPLACE(c.telefono, '+', ''), ' ', ''), '-', ''), 10) = :tel10b
             ORDER BY c.id DESC
             LIMIT 1
        ");
        $st->execute([
            ':tel' => $digitos,
            ':tel10' => $tel10,
            ':tel10b' => $tel10,
        ]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private static function enviarUsuarioAgregado(PDO $pdo, array $colaborador): void
    {
        // Plantilla usuario_agregado: body solo {{1}} = nombre de quien aceptó.
        $nombre = self::primerNombre((string)($colaborador['nombres'] ?? ''));
        $telefono = (string)($colaborador['telefono'] ?? '');
        if ($nombre === '' || $telefono === '') {
            return;
        }

        $resultado = self::enviarPlantilla($telefono, 'usuario_agregado', [$nombre]);
        self::registrarNotif(
            $pdo,
            (int)$colaborador['id'],
            'usuario_agregado',
            'usuario_agregado',
            $telefono,
            $resultado
        );
    }

    private static function enviarPlantilla(string $telefono, string $plantilla, array $bodyParams): array
    {
        $destino = self::destino($telefono);
        if ($destino === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'Teléfono inválido'];
        }

        $parameters = [];
        foreach ($bodyParams as $texto) {
            $texto = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n", "\t"], ' ', (string)$texto)) ?? '');
            if ($texto === '') {
                return ['ok' => false, 'message_id' => null, 'error' => 'Parámetro vacío'];
            }
            $parameters[] = ['type' => 'text', 'text' => $texto];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $destino,
            'type' => 'template',
            'template' => [
                'name' => $plantilla,
                'language' => [
                    'code' => CAMPANA_WHATSAPP_TEMPLATE_LANGUAGE,
                    'policy' => 'deterministic',
                ],
                'components' => [[
                    'type' => 'body',
                    'parameters' => $parameters,
                ]],
            ],
        ];

        $url = 'https://graph.facebook.com/' . CAMPANA_WHATSAPP_API_VERSION
            . '/' . CAMPANA_WHATSAPP_PHONE_NUMBER_ID . '/messages';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . CAMPANA_WHATSAPP_TOKEN,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'Error de conexión'];
        }

        $json = json_decode((string)$response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'ok' => true,
                'message_id' => is_array($json) ? ($json['messages'][0]['id'] ?? null) : null,
                'error' => null,
            ];
        }

        $error = is_array($json) ? ($json['error']['message'] ?? 'Error HTTP ' . $httpCode) : 'Error HTTP ' . $httpCode;

        return ['ok' => false, 'message_id' => null, 'error' => $error];
    }

    private static function registrarNotif(
        PDO $pdo,
        int $colaboradorId,
        string $evento,
        string $plantilla,
        string $telefono,
        array $resultado
    ): void {
        try {
            $st = $pdo->prepare("
                INSERT INTO tbl_notif_whatsapp_nivel
                    (colaborador_id, nivel_id, evento, plantilla, telefono, estado, intentos, message_id, detalle)
                VALUES
                    (:colaborador_id, 0, :evento, :plantilla, :telefono, :estado, 1, :message_id, :detalle)
            ");
            $st->execute([
                ':colaborador_id' => $colaboradorId,
                ':evento' => $evento,
                ':plantilla' => $plantilla,
                ':telefono' => mb_substr($telefono, 0, 20),
                ':estado' => !empty($resultado['ok']) ? 'enviado' : 'error',
                ':message_id' => $resultado['message_id'] ?? null,
                ':detalle' => isset($resultado['error']) ? mb_substr((string)$resultado['error'], 0, 500) : null,
            ]);
        } catch (Throwable $e) {
            // Tabla ausente o duplicado: no bloquear el flujo de aceptación.
        }
    }

    /**
     * Llama al endpoint de omarcampana para recalcular niveles y regalos
     * del invitador (aviso / cierre) con el conteo solo de aceptados.
     */
    private static function avisarCampanaNiveles(int $padreId, int $colaboradorId): void
    {
        if ($padreId <= 0 || $colaboradorId <= 0) {
            return;
        }
        if (!defined('CAMPANA_EVALUAR_NIVELES_URL') || CAMPANA_EVALUAR_NIVELES_URL === '') {
            return;
        }
        if (!defined('CAMPANA_WEBHOOK_SECRET') || CAMPANA_WEBHOOK_SECRET === '') {
            return;
        }

        $payload = json_encode([
            'padre_id' => $padreId,
            'colaborador_id' => $colaboradorId,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(CAMPANA_EVALUAR_NIVELES_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Campana-Webhook-Secret: ' . CAMPANA_WEBHOOK_SECRET,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            error_log('CampanaInvitacionWebhook niveles curl: ' . $curlErr);
            return;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('CampanaInvitacionWebhook niveles HTTP ' . $httpCode . ': ' . substr((string)$response, 0, 300));
        }
    }

    private static function destino(string $telefono): string
    {
        $digitos = preg_replace('/\D+/', '', $telefono) ?? '';
        $prefijo = CAMPANA_WHATSAPP_COUNTRY_PREFIX;
        if ($digitos === '' || $prefijo === '') {
            return '';
        }
        if (strlen($digitos) === 10) {
            return $prefijo . $digitos;
        }
        if (str_starts_with($digitos, $prefijo) && strlen($digitos) === strlen($prefijo) + 10) {
            return $digitos;
        }

        return '';
    }

    private static function normalizarPayload(string $payload): string
    {
        $payload = mb_strtoupper(trim($payload), 'UTF-8');
        $map = [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U',
            'Ñ' => 'N', '¡' => '', '!' => '',
        ];
        $payload = strtr($payload, $map);

        return preg_replace('/\s+/', ' ', $payload) ?? $payload;
    }

    private static function primerNombre(string $nombres): string
    {
        $nombres = trim(preg_replace('/\s+/', ' ', $nombres) ?? '');
        if ($nombres === '') {
            return '';
        }
        $partes = preg_split('/\s+/u', $nombres) ?: [];

        return trim((string)($partes[0] ?? ''));
    }
}
