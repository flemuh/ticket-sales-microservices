import jwt
import os
import mysql.connector
import pika
import json
from datetime import datetime, timedelta
from flask import Flask, request, jsonify

JWT_SECRET = os.getenv("JWT_SECRET")
RABBITMQ_HOST = os.getenv("RABBITMQ_HOST", "rabbitmq")
DB_HOST = os.getenv("DB_HOST", "mysql")
DB_USER = os.getenv("DB_USER", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "root")
DB_NAME = os.getenv("DB_NAME", "catalogo_db")

app = Flask(__name__)

def conectar_mysql():
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME
    )

def validar_token():
    auth = request.headers.get("Authorization")

    if not auth or not auth.startswith("Bearer "):
        return None

    token = auth.split(" ")[1]

    try:
        decoded = jwt.decode(token, JWT_SECRET, algorithms=["HS256"])

        if decoded.get("service") != "php":
            return None

        return decoded
    except Exception as e:
        print("JWT inválido:", e)
        return None

def publicar_fila(data):
    try:
        connection = pika.BlockingConnection(
            pika.ConnectionParameters(host=RABBITMQ_HOST)
        )
        channel = connection.channel()
        channel.queue_declare(queue='pos_venda', durable=True)

        channel.basic_publish(
            exchange='',
            routing_key='pos_venda',
            body=json.dumps(data),
            properties=pika.BasicProperties(delivery_mode=2)
        )

        connection.close()
    except Exception as e:
        print("⚠️ Falha ao publicar fila:", e)

@app.route('/reservar', methods=['POST'])
def reservar():
    user = validar_token()

    if not user:
        return jsonify({"erro": "não autorizado"}), 401

    data = request.get_json(silent=True) or {}
    evento_id = data.get("evento_id")
    quantidade = data.get("quantidade")

    if not isinstance(evento_id, int) or not isinstance(quantidade, int) or evento_id <= 0 or quantidade <= 0:
        return jsonify({"erro": "dados inválidos"}), 400

    conn = None
    cursor = None

    try:
        conn = conectar_mysql()
        conn.start_transaction()
        cursor = conn.cursor()

        cursor.execute(
            "SELECT quantidade FROM eventos WHERE id = %s FOR UPDATE",
            (evento_id,)
        )
        row = cursor.fetchone()

        if not row:
            conn.rollback()
            return jsonify({"erro": "evento não encontrado"}), 404

        estoque = row[0]

        if estoque < quantidade:
            conn.rollback()
            return jsonify({"erro": "estoque insuficiente"}), 409

        cursor.execute(
            "UPDATE eventos SET quantidade = quantidade - %s WHERE id = %s",
            (quantidade, evento_id)
        )

        expiracao = datetime.now() + timedelta(minutes=5) # -***-
        # Evolução futura, por exemplo em fluxos onde exista timeout de pagamento ou compensação
        # buscar reservas com:
        # status = 'reservado'
        # expiracao < agora
        # devolver a quantidade ao estoque usando python_worker
        # marcar a reserva como expirado

        cursor.execute(
            """
            INSERT INTO reservas (evento_id, quantidade, status, expiracao)
            VALUES (%s, %s, 'reservado', %s)
            """,
            (evento_id, quantidade, expiracao)
        )

        reserva_id = cursor.lastrowid
        conn.commit()

        publicar_fila({
            "tipo": "reserva_criada",
            "reserva_id": reserva_id,
            "evento_id": evento_id,
            "quantidade": quantidade
        })


        # -***- # Simular Resposta inválida do catálogo
        # return "resposta invalida", 200


        return jsonify({
            "mensagem": "reserva realizada com sucesso",
            "reserva_id": reserva_id,
            "status": "reservado"
        }), 200

    except Exception as e:
        if conn:
            conn.rollback()
        print("Erro ao reservar:", e)
        return jsonify({"erro": "erro interno ao reservar ingresso"}), 500

    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)

