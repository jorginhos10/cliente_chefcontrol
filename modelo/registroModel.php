<?php
// modelo/registroModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class RegistroModel extends BaseModel {

    /**
     * Registra un nuevo restaurante + admin en la misma transacción.
     * @return array ['success', 'message', 'comercio_id']
     */
    public function registrar(
        string $nombreComercio,
        string $nombreAdmin,
        string $email,
        string $username,
        string $password,
        string $telefono = ''
    ): array {
        $slug = $this->generarSlug($nombreComercio);

        if (ComercioModel::emailExiste($email)) {
            return ['success' => false, 'message' => 'Ese email ya tiene una cuenta registrada.'];
        }
        if (ComercioModel::slugExiste($slug)) {
            $slug .= substr(bin2hex(random_bytes(2)), 0, 4);
        }

        try {
            $this->db->beginTransaction();

            // 1. Crear el comercio (pendiente de verificación de documentos)
            $this->query(
                "INSERT INTO comercios (slug, nombre, email, telefono, moneda, doc_estado) VALUES (?, ?, ?, ?, 'COP', 'pendiente')",
                [$slug, $nombreComercio, $email, $telefono ?: null]
            );
            $comercioId = (int)$this->db->lastInsertId();

            // 2. Crear el admin del restaurante (marcado como propietario)
            $this->query(
                "INSERT INTO usuarios (comercio_id, username, password, nombre, email, rol, activo, propietario)
                 VALUES (?, ?, ?, ?, ?, 'admin', 1, 1)",
                [$comercioId, $username, password_hash($password, PASSWORD_DEFAULT), $nombreAdmin, $email]
            );

            $this->db->commit();

            $usuarioId = (int)$this->db->lastInsertId();

            return [
                'success'     => true,
                'message'     => 'Restaurante creado correctamente.',
                'comercio_id' => $comercioId,
                'usuario_id'  => $usuarioId,
                'slug'        => $slug,
            ];

        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("RegistroModel::registrar error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear el restaurante. Intenta de nuevo.'];
        }
    }

    private function generarSlug(string $texto): string {
        $mapa = ['á'=>'a','à'=>'a','ä'=>'a','é'=>'e','è'=>'e','ë'=>'e',
                 'í'=>'i','ì'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ö'=>'o',
                 'ú'=>'u','ù'=>'u','ü'=>'u','ñ'=>'n','Á'=>'a','É'=>'e',
                 'Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n'];
        $texto = strtr($texto, $mapa);
        $texto = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $texto));
        $texto = preg_replace('/\s+/', '-', trim($texto));
        return substr($texto ?: 'restaurante', 0, 40);
    }
}
