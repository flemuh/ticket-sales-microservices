# 🎫 Ticket sales platform with PHP, Python, RabbitMQ, MySQL, Docker and asynchronous processing.

## 📝 Descrição

Este projeto implementa um sistema de venda de ingressos baseado em microserviços, com foco em consistência de dados, separação de responsabilidades e escalabilidade.

### Componentes

- **Frontend (React)** → Interface do usuário  
- **Serviço de Vendas (PHP + PDO)** → Processamento da compra  
- **Serviço de Catálogo (Python + Flask)** → Controle de estoque  
- **Worker (Python + RabbitMQ)** → Processamento assíncrono  
- **MySQL** → Persistência de dados  

---

## 🏗️ Arquitetura e Fluxo

O sistema prioriza a integridade do estoque por meio de uma comunicação síncrona no momento crítico da compra.

### Fluxo da compra

1. Usuário realiza a compra no frontend  
2. O serviço PHP recebe a requisição  
3. O PHP chama o serviço Python (`/reservar`)  
4. O Python valida e reserva o estoque  
5. Se sucesso:
   - Reserva é registrada no catálogo  
   - Venda é salva no banco de vendas  
   - Evento é enviado para a fila  
6. Worker processa tarefas assíncronas  

---

## 🚀 Como executar

### 1. Limpar o ambiente

```bash
docker compose down -v
```

2. Subir os serviços

```bash
docker compose up -d --build3. Acessar o sistema
```

3. Acessar o sistema

Frontend
http://localhost:5173

3. Ver logs do worker
```bash
docker compose logs -f python_worker
```

🧪 Teste da compra

   Clique no botão Comprar
   
   O sistema irá:
   
      Validar estoque no Python
      
      Registrar venda no PHP
      
      Retornar sucesso ou erro


| Cenário           | Comportamento         | Status HTTP |
|-------------------|-----------------------|-------------|
| Serviço offline   | Catálogo indisponível | 503         |
| Resposta inválida | Erro de comunicação   | 502         |
| Sem estoque       | Bloqueio da compra    | 409         |
| Payload inválido  | Erro de validação     | 400         |
| compra válida     | Sucesso               | 201         |
|-------------------|-----------------------|-------------|


💡 Decisões técnicas
Consistência síncrona

   A validação de estoque é feita via HTTP entre PHP e Python para garantir que a compra só seja confirmada após validação, evitando venda acima do disponível.

Mensageria
   
   RabbitMQ é utilizado apenas para tarefas secundárias, como:
   
   logs, auditoria, notificações
   
   Sem impactar o fluxo principal.


Isolamento

   Cada serviço possui seu próprio banco lógico, reduzindo acoplamento e facilitando manutenção.
   

🔐 Segurança

   Comunicação entre serviços via JWT
   
   Endpoint /reservar não é público
   
   Apenas o serviço PHP pode reservar estoque
   
   Frontend não acessa diretamente o catálogo

📈 Escalabilidade

   A arquitetura permite escalar serviços de forma independente.
   
   Componentes mais impactados em alta carga:
   
      Serviço de catálogo (controle de estoque)
      
      Banco de dados do catálogo
      
      Frontend (acesso simultâneo)
   
   O uso de fila reduz carga no fluxo principal.

🧠 Observações

   O fluxo principal é síncrono para garantir consistência
   
   RabbitMQ é usado como melhoria arquitetural
   
   Reserva é feita com controle transacional
   
   Projeto focado em simplicidade e escalabilidade
   
   Tag para buscar de implementações ou testes -***-
