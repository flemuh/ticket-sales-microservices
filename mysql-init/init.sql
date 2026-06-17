CREATE DATABASE IF NOT EXISTS catalogo_db;
CREATE DATABASE IF NOT EXISTS vendas_db;

USE catalogo_db;

CREATE TABLE eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    quantidade INT NOT NULL
);

CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    quantidade INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    expiracao DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evento_id (evento_id),
    INDEX idx_status (status)
);

INSERT INTO eventos (nome, quantidade)
VALUES ('Evento Teste', 10);

USE vendas_db;

CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    quantidade INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evento_id (evento_id),
    INDEX idx_status (status)
);