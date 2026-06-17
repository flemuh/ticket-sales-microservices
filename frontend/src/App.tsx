import { useState } from "react";

type CompraRequest = {
  evento_id: number;
  quantidade: number;
};

type ApiResponse = {
  mensagem?: string;
  erro?: string;
  status?: string;
  venda_id?: number;
};

function App() {
  const [loading, setLoading] = useState<boolean>(false);
  const [mensagem, setMensagem] = useState<string>("");

  const comprar = async (): Promise<void> => {
    setLoading(true);
    setMensagem("");

    const payload: CompraRequest = {
      evento_id: 1,
      quantidade: 1,
    };

    try {
      const response = await fetch("http://localhost:8000/comprar", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const data: ApiResponse = await response.json();

      if (!response.ok) {
        throw new Error(data.erro || "Erro na compra");
      }

      setMensagem(`✅ ${data.mensagem ?? "Compra realizada com sucesso"}`);
    } catch (error: unknown) {
      if (error instanceof Error) {
        setMensagem("❌ Erro: " + error.message);
      } else {
        setMensagem("❌ Erro desconhecido");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ padding: "40px" }}>
      <h1>Compra de Ingressos</h1>

      <button onClick={comprar} disabled={loading}>
        {loading ? "Carregando..." : "Comprar"}
      </button>

      {mensagem && <p>{mensagem}</p>}
    </div>
  );
}

export default App;