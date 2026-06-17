<?php

namespace App\Controller;

use App\Service\VendaService;

class VendaController
{
    private VendaService $vendaService;

    public function __construct(VendaService $vendaService)
    {
        $this->vendaService = $vendaService;
    }

    public function comprar(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(['erro' => 'JSON inválido']);
            return;
        }

        $eventoId = (int) ($input['evento_id'] ?? 0);
        $quantidade = (int) ($input['quantidade'] ?? 0);

        $resultado = $this->vendaService->comprar($eventoId, $quantidade);

        http_response_code($resultado['status_code']);
        echo json_encode($resultado['body']);
    }
}