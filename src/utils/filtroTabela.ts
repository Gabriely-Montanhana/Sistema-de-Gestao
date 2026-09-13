export type FiltroTabelaOpcoes = {
    seletorLinha: string;
    idBusca: string;
    idSelect?: string;
    idVazio: string;
    campoTexto: string;
    campoSelect?: string;
};

export function aplicarFiltroTabela(opcoes: FiltroTabelaOpcoes): void {
    const busca = ((document.getElementById(opcoes.idBusca) as HTMLInputElement | null)?.value ?? '').trim().toLowerCase();
    const selecionado = opcoes.idSelect
        ? ((document.getElementById(opcoes.idSelect) as HTMLSelectElement | null)?.value ?? '')
        : '';
    const linhas = Array.from(document.querySelectorAll<HTMLElement>(opcoes.seletorLinha));

    const visiveis = linhas.filter((linha) => {
        const texto = linha.dataset[opcoes.campoTexto] ?? '';
        const extra = opcoes.campoSelect ? (linha.dataset[opcoes.campoSelect] ?? '') : '';

        return (busca === '' || texto.includes(busca)) && (selecionado === '' || extra === selecionado);
    });

    linhas.forEach((linha) => linha.classList.toggle('d-none', !visiveis.includes(linha)));
    document.getElementById(opcoes.idVazio)?.classList.toggle('d-none', visiveis.length > 0);
}

export function iniciarFiltroTabela(opcoes: FiltroTabelaOpcoes): void {
    const aplicar = (): void => aplicarFiltroTabela(opcoes);

    document.getElementById(opcoes.idBusca)?.addEventListener('input', aplicar);

    if (opcoes.idSelect) {
        document.getElementById(opcoes.idSelect)?.addEventListener('change', aplicar);
    }
    aplicar();
}
