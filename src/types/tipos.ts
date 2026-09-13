export type ApiResponse<T> = {
    success: boolean,
    message?: string,
    data: T
}

export type DashboardIndicadores = {
    receita_total: string,
    total_servicos: string,
    servicos_pendentes: string,
    produtos_estoque_baixo: string,
    produto_mais_usado: string | null,
    empresa_destaque: string | null
}

export type ServicoPorStatus = {
    status: string,
    quantidade: string,
    receita: string
}

export type DashboardTotais = {
    quantidade: number,
    receita: number
}

export type RankingItem = {
    nome: string,
    total: string | number
}

export type DashboardData = {
    indicadores: DashboardIndicadores | null,
    servicos_por_status: ServicoPorStatus[],
    ranking_produtos?: RankingItem[],
    ranking_empresas?: RankingItem[]
}
