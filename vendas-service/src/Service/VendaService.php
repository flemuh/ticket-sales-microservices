<?php

namespace App\Service;

use Firebase\JWT\JWT;
use PDO;
use Throwable;

class VendaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function comprar(int $eventoId, int $quantidade): array
    {
        if ($eventoId <= 0 || $quantidade <= 0) {
            return [
                'success' => false,
                'status_code' => 400,
                'body' => ['erro' => 'evento_id e quantidade devem ser maiores que zero']
            ];
        }

        $secret = getenv('JWT_SECRET');
        if (!$secret) {
            return [
                'success' => false,
                'status_code' => 500,
                'body' => ['erro' => 'JWT_SECRET não configurado']
            ];
        }

        $payload = [
            'service' => 'php',
            'iat' => time(),
            'exp' => time() + 60
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        $data = json_encode([
            'evento_id' => $eventoId,
            'quantidade' => $quantidade
        ]);

        $headers = implode("\r\n", [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);

        $options = [
            'http' => [
                'header' => $headers,
                'method' => 'POST',
                'content' => $data,
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);

        // -***- Serviço de catálogo indisponível
        //$result = @file_get_contents('http://python_api:9999/reservar', false, $context);
        $result = @file_get_contents('http://python_api:5000/reservar', false, $context);

        if ($result === false) {
            return [
                'success' => false,
                'status_code' => 503,
                'body' => ['erro' => 'Serviço de catálogo indisponível']
            ];
        }

        $response = json_decode($result, true);

        if (!is_array($response)) {
            return [
                'success' => false,
                'status_code' => 502,
                'body' => ['erro' => 'Resposta inválida do catálogo']
            ];
        }

        $statusCode = 500;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $statusCode = (int) $matches[1];
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return [
                'success' => false,
                'status_code' => $statusCode,
                'body' => ['erro' => $response['erro'] ?? 'Falha ao reservar ingresso']
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "INSERT INTO vendas (evento_id, quantidade, status) VALUES (?, ?, ?)"
            );
            $stmt->execute([$eventoId, $quantidade, 'confirmada']);

            $vendaId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return [
                'success' => true,
                'status_code' => 201,
                'body' => [
                    'mensagem' => 'Compra confirmada com sucesso',
                    'status' => 'confirmada',
                    'venda_id' => $vendaId
                ]
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
                error_log('Erro ao salvar venda: ' . $e->getMessage());
            }

            return [
                'success' => false,
                'status_code' => 500,
                'body' => ['erro' => 'Erro ao salvar venda']
            ];
        }
    }
}