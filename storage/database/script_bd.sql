CREATE TABLE personal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userid VARCHAR(9) NOT NULL,           -- ID que usa el reloj ZKTeco
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    dni VARCHAR(15),
    password VARCHAR(50) DEFAULT '',
    role TINYINT DEFAULT 0,                -- 0: normal, 14: admin (según modelo)
    cardno VARCHAR(50) DEFAULT '',
    estado_sync TINYINT DEFAULT 0,         -- 0: crear, 1: sincronizado, 2: modificar, 3: eliminar
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Para evitar duplicados por UserID o DNI
CREATE UNIQUE INDEX idx_userid ON personal(userid);
CREATE UNIQUE INDEX idx_dni ON personal(dni);

CREATE TABLE asistencias (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    ip VARCHAR(50),
    sincronizado TINYINT DEFAULT 1, -- 1 porque ya estará sincronizado en la nube
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asistencia_user_fecha (user_id, fecha)
);
