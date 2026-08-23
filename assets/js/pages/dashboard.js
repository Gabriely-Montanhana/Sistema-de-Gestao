import { apiGet } from '../api/client.js';
import { badgeClass, formatarMoeda } from '../utils/formatters.js';
function setText(id, text) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = text;
    }
}
function normalizarServicosPorStatus(raw) {
    if (Array.isArray(raw)) {
        return raw.filter((row) => row != null && typeof row === 'object');
    }
    if (raw != null && typeof raw === 'object' && 'quantidade' in raw) {
        return [raw];
    }
    return [];
}
function calcularTotaisServicos(rows) {
    return rows.reduce((acc, row) => ({
        quantidade: acc.quantidade + Number(row?.quantidade ?? 0),
        receita: acc.receita + Number(row?.receita ?? 0),
    }), { quantidade: 0, receita: 0 });
}
function calcularReceitaTotal(rows) {
    return rows.reduce((total, row) => total + Number(row?.receita ?? 0), 0);
}
function renderTabelaStatus(rows, totais) {
    const tbody = document.getElementById('tabela-status');
    const tfoot = document.getElementById('tabela-status-total');
    if (!tbody || !tfoot) {
        return;
    }
    if (rows.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="3" class="text-muted">Nenhum serviço cadastrado.</td></tr>';
        tfoot.innerHTML = '';
        return;
    }
    tbody.innerHTML = rows
        .map((row) => `
            <tr>
                <td><span class="badge rounded-pill ${badgeClass(row.status)}">${row.status}</span></td>
                <td>${row.quantidade}</td>
                <td>${formatarMoeda(row.receita)}</td>
            </tr>
        `)
        .join('');
    tfoot.innerHTML = `
        <tr class="table-light fw-bold">
            <td>Total</td>
            <td>${totais.quantidade}</td>
            <td>${formatarMoeda(String(totais.receita))}</td>
        </tr>
    `;
}
function renderIndicadores(data, receitaTotal) {
    const ind = data.indicadores;
    if (!ind) {
        setText('kpi-receita', formatarMoeda(String(receitaTotal)));
        setText('kpi-total-servicos', '0');
        setText('kpi-pendentes', '0');
        setText('kpi-estoque-baixo', '0');
        setText('destaque-produto', 'Nenhum produto registrado');
        setText('destaque-empresa', 'Nenhuma empresa registrada');
        return;
    }
    setText('kpi-receita', formatarMoeda(String(receitaTotal)));
    setText('kpi-total-servicos', ind.total_servicos || '0');
    setText('kpi-pendentes', ind.servicos_pendentes || '0');
    setText('kpi-estoque-baixo', ind.produtos_estoque_baixo || '0');
    setText('destaque-produto', ind.produto_mais_usado?.trim() || 'Nenhum produto registrado');
    setText('destaque-empresa', ind.empresa_destaque?.trim() || 'Nenhuma empresa registrada');
}
function renderErroDashboard(msg) {
    setText('kpi-receita', '—');
    setText('kpi-total-servicos', '—');
    setText('kpi-pendentes', '—');
    setText('kpi-estoque-baixo', '—');
    setText('destaque-produto', '—');
    setText('destaque-empresa', '—');
    const tbody = document.getElementById('tabela-status');
    const tfoot = document.getElementById('tabela-status-total');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-danger">Falha ao carregar: ${msg}. Verifique o XAMPP e importe o schema.sql no phpMyAdmin.</td></tr>`;
    }
    if (tfoot) {
        tfoot.innerHTML = '';
    }
}
async function carregarDashboard() {
    try {
        const data = await apiGet('api/dashboard.php');
        const rows = normalizarServicosPorStatus(data?.servicos_por_status);
        const totais = calcularTotaisServicos(rows);
        const receitaTotal = calcularReceitaTotal(rows);
        renderIndicadores(data, receitaTotal);
        renderTabelaStatus(rows, totais);
    }
    catch (error) {
        const msg = error instanceof Error ? error.message : 'Erro desconhecido';
        renderErroDashboard(msg);
    }
}
document.addEventListener('DOMContentLoaded', () => {
    void carregarDashboard();
});
