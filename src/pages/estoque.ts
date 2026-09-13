import { confirmar, exibirFlash } from '../utils/alertas.js';
import { iniciarFiltroTabela } from '../utils/filtroTabela.js';

function confirmarExclusao(): void {
    document.querySelectorAll<HTMLFormElement>('.form-excluir-produto').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const nome = form.dataset.nome ?? 'este produto';

            void confirmar({
                title: 'Excluir produto?',
                text: `O produto "${nome}" será removido do estoque.`,
                confirmButtonText: 'Excluir',
            }).then((ok) => {
                if (ok) {
                    form.submit();
                }
            });
        });
    });
}

function validarFormulario(id: string): void {
    const form = document.getElementById(id) as HTMLFormElement | null;
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
        }
    });
}

function confirmarMovimento(): void {
    const form = document.getElementById('form-movimento') as HTMLFormElement | null;
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            return;
        }

        const saida = (form.querySelector('#tipo-saida') as HTMLInputElement | null)?.checked;
        if (!saida) {
            return;
        }

        event.preventDefault();

        const select = form.querySelector('#id_estoque_movimento') as HTMLSelectElement | null;
        const produto = select?.selectedOptions[0]?.text ?? 'este produto';
        const quantidade = (form.querySelector('#quantidade_movimento') as HTMLInputElement | null)?.value ?? '';

        void confirmar({
            title: 'Registrar saída?',
            text: `Vai retirar ${quantidade} unidade(s) de ${produto}.`,
            confirmButtonText: 'Registrar saída',
        }).then((ok) => {
            if (ok) {
                form.submit();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    exibirFlash();
    confirmarExclusao();
    validarFormulario('form-estoque');
    validarFormulario('form-movimento');
    confirmarMovimento();
    iniciarFiltroTabela({
        seletorLinha: '.linha-produto',
        idBusca: 'filtro-estoque-busca',
        idSelect: 'filtro-estoque-nivel',
        idVazio: 'filtro-estoque-vazio',
        campoTexto: 'nome',
        campoSelect: 'nivel',
    });
});
