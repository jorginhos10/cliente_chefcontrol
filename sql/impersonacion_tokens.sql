CREATE TABLE IF NOT EXISTS impersonacion_tokens (
    id          INT(11) NOT NULL AUTO_INCREMENT,
    token       VARCHAR(64) NOT NULL,
    usuario_id  INT(11) NOT NULL,
    comercio_id INT(11) NOT NULL,
    usado       TINYINT(1) NOT NULL DEFAULT 0,
    expira_en   DATETIME NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_token (token),
    KEY idx_usuario (usuario_id),
    KEY idx_comercio (comercio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
