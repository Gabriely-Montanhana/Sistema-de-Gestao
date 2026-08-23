export function formatarMoeda(valor: number | string): string {
    const numero = typeof valor === 'string' ? parseFloat(valor) : valor;

    return numero.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

export function badgeClass(status: string): string {
    const mapa: Record<string, string> = {
        Pendente: 'bg-warning text-dark',
        'Em Andamento': 'bg-primary',
        Concluído: 'bg-success',
        Cancelado: 'bg-danger',
    };

    return mapa[status] ?? 'bg-secondary';
}
