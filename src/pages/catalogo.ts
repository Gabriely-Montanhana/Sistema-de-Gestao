import { iniciarFiltroTabela } from '../utils/filtroTabela.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('catalogo-filtro');
    if (!root) {
        return;
    }

    iniciarFiltroTabela({
        seletorLinha: '.item-catalogo',
        idBusca: 'filtro-catalogo-busca',
        idSelect: 'filtro-catalogo-select',
        idVazio: 'filtro-catalogo-vazio',
        campoTexto: root.dataset.campoTexto ?? 'busca',
        campoSelect: root.dataset.campoSelect ?? 'status',
    });
});
