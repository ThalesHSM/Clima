// Endereço do backend (PHP). Troque se rodar em outra porta.
const API_URL = 'http://127.0.0.1:8000/api';

// Faz a requisição e devolve o JSON. Em caso de erro, lança Error com a mensagem da API.
async function api(path, method = 'GET', body = null) {
    const res = await fetch(API_URL + path, {
        method,
        credentials: 'include', // envia o cookie de sessão
        headers: body ? { 'Content-Type': 'application/json' } : {},
        body: body ? JSON.stringify(body) : null,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(data.error || 'Erro inesperado.');
        err.status = res.status;
        throw err;
    }
    return data;
}

// Chamado nas páginas que exigem login: redireciona se não estiver logado.
async function requireLogin() {
    try {
        return await api('/me');
    } catch (e) {
        location.href = 'login.html';
        throw e;
    }
}

async function logout() {
    await api('/logout', 'POST');
    location.href = 'index.html';
}

// Formata "2025-01-31" como "sexta-feira, 31 de janeiro"
function formatDate(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('pt-BR', {
        weekday: 'long', day: 'numeric', month: 'long',
    });
}

// Gramas de CO₂ -> "830 g" ou "1,7 kg"
function formatCo2(grams) {
    if (grams < 1000) return `${grams} g`;
    return `${(grams / 1000).toFixed(1).replace('.', ',')} kg`;
}
