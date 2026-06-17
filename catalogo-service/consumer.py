import os
import pika
import json
import time

RABBITMQ_HOST = os.getenv("RABBITMQ_HOST", "rabbitmq")

def processar(ch, method, properties, body):
    try:
        data = json.loads(body)
        print("Evento recebido:", data)

        if data.get("tipo") == "reserva_criada":
            print(f"Pós-processamento da reserva {data.get('reserva_id')}")

        ch.basic_ack(delivery_tag=method.delivery_tag)

    except Exception as e:
        print("Erro no worker:", e)
        ch.basic_ack(delivery_tag=method.delivery_tag)

def consumir():
    while True:
        try:
            connection = pika.BlockingConnection(
                pika.ConnectionParameters(host=RABBITMQ_HOST)
            )
            channel = connection.channel()
            channel.queue_declare(queue='pos_venda', durable=True)
            channel.basic_qos(prefetch_count=1)

            channel.basic_consume(
                queue='pos_venda',
                on_message_callback=processar,
                auto_ack=False
            )

            print("Consumindo fila pos_venda...")
            channel.start_consuming()

        except Exception as e:
            print("Aguardando RabbitMQ...", e)
            time.sleep(5)

consumir()