<?php

require_once __DIR__ . '/DbConection.php';
require_once __DIR__ . '/ConfigLoader.php';
require_once __DIR__ . '/../../enviar_whatsapp_emergencia.php';

/**
 * Interpreta la clasificación de riesgo que ya devolvió Claude y decide
 * acciones de backend. No reclasifica ni tiene lógica de IA propia.
 *
 * Nivel <= 2: no hace nada especial, el mensaje ya quedó persistido antes de llamar aquí.
 * Nivel 3: solo marca requiere_botones_emergencia (ya viene de Claude, se respeta).
 * Nivel 4: autoregistra una alerta en tbl_alerta_policia (una sola vez por conversación)
 *          y, si el flag está habilitado, notifica por WhatsApp al contacto de emergencia.
 *
 * @author SPIDERSOFTWARE
 */
class ChatRiesgoService
{
    private $pdo;
    private $config;

    public function __construct(PDO $pdo, ConfigLoader $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /**
     * @param int $conversacionId
     * @param int $usuarioId
     * @param int $mensajeId Id del mensaje del asistente que disparó la evaluación.
     * @param int $nivelRiesgo
     * @param string $resumenAuditoria
     * @return array Info de la alerta generada, si aplica.
     */
    public function procesarNivelRiesgo($conversacionId, $usuarioId, $mensajeId, $nivelRiesgo, $resumenAuditoria)
    {
        if ($nivelRiesgo < 4) {
            return ['alerta_generada' => false];
        }

        $stmtConversacion = $this->pdo->prepare(
            'SELECT alerta_nivel4_generada FROM tbl_chat_conversaciones WHERE id = :id LIMIT 1'
        );
        $stmtConversacion->bindValue(':id', $conversacionId, PDO::PARAM_INT);
        $stmtConversacion->execute();
        $conversacion = $stmtConversacion->fetch(PDO::FETCH_ASSOC);

        if (!$conversacion || (int) $conversacion['alerta_nivel4_generada'] === 1) {
            return ['alerta_generada' => false];
        }

        $stmtUsuario = $this->pdo->prepare(
            'SELECT nombre, apellido, celular, contacto_emergencia, nombre_contacto FROM tbl_usuarios WHERE id = :id LIMIT 1'
        );
        $stmtUsuario->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmtUsuario->execute();
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC) ?: [];

        $nombreUsuaria = trim((string) ($usuario['nombre'] ?? '') . ' ' . (string) ($usuario['apellido'] ?? ''));
        if ($nombreUsuaria === '') {
            $nombreUsuaria = 'Usuaria';
        }

        $celularUsuaria = trim((string) ($usuario['celular'] ?? ''));
        $contactoEmergencia = trim((string) ($usuario['contacto_emergencia'] ?? ''));
        $nombreContactoEmergencia = trim((string) ($usuario['nombre_contacto'] ?? ''));

        $zonaColombia = new DateTimeZone('America/Bogota');
        $fechaRegistro = (new DateTimeImmutable('now', $zonaColombia))->format('Y-m-d H:i:s');

        $sql = "INSERT INTO tbl_alerta_policia (
                    usuario_id, nombre_usuaria, celular_usuaria, contacto_emergencia,
                    nombre_contacto_emergencia, tipo_alerta, descripcion, estado,
                    ubicacion_confirmada, fecha_registro, chat_conversacion_id, chat_mensaje_id
                ) VALUES (
                    :usuario_id, :nombre_usuaria, :celular_usuaria, :contacto_emergencia,
                    :nombre_contacto_emergencia, 'riesgo_chat_nivel_4', :descripcion, 'pendiente',
                    'no', :fecha_registro, :conversacion_id, :mensaje_id
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre_usuaria', $nombreUsuaria, PDO::PARAM_STR);
        $stmt->bindValue(':celular_usuaria', $celularUsuaria, PDO::PARAM_STR);
        $stmt->bindValue(':contacto_emergencia', $contactoEmergencia, PDO::PARAM_STR);
        $stmt->bindValue(':nombre_contacto_emergencia', $nombreContactoEmergencia, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $resumenAuditoria, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_registro', $fechaRegistro, PDO::PARAM_STR);
        $stmt->bindValue(':conversacion_id', $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':mensaje_id', $mensajeId, PDO::PARAM_INT);
        $stmt->execute();

        $alertaId = (int) $this->pdo->lastInsertId();

        $stmtMarcar = $this->pdo->prepare(
            'UPDATE tbl_chat_conversaciones SET alerta_nivel4_generada = 1 WHERE id = :id'
        );
        $stmtMarcar->bindValue(':id', $conversacionId, PDO::PARAM_INT);
        $stmtMarcar->execute();

        $resultadoWa = null;

        if ($this->config->isWhatsappRiesgoHabilitado() && $contactoEmergencia !== '') {
            $resultadoWa = enviarWhatsAppAlertaRiesgo($contactoEmergencia, $nombreContactoEmergencia, $nombreUsuaria);

            $stmtWa = $this->pdo->prepare(
                'UPDATE tbl_chat_conversaciones SET whatsapp_notificado = :notificado, whatsapp_notificado_fecha = :fecha WHERE id = :id'
            );
            $stmtWa->bindValue(':notificado', !empty($resultadoWa['ok']) ? 1 : 0, PDO::PARAM_INT);
            $stmtWa->bindValue(':fecha', $fechaRegistro, PDO::PARAM_STR);
            $stmtWa->bindValue(':id', $conversacionId, PDO::PARAM_INT);
            $stmtWa->execute();
        }

        return [
            'alerta_generada' => true,
            'alerta_id' => $alertaId,
            'whatsapp' => $resultadoWa
        ];
    }
}
