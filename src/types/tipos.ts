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

export type DashboardData = {
    indicadores: DashboardIndicadores | null,
    servicos_por_status: ServicoPorStatus[]
}

export type Empresa = {
    id_empresa: string,
    nome_empresa: string,
    cnpj: string,
    cidade: string | null,
    endereco: string | null,
    telefone: string | null,
    email: string | null,
    status: 'Ativo' | 'Inativo'
}

export type EmpresaInput = {
    nome_empresa: string,
    cnpj: string,
    cidade?: string | null,
    endereco?: string | null,
    telefone?: string | null,
    email?: string | null
}

export type CatalogoEmpresa = {
    nome_empresa: string,
    cidade: string | null,
    telefone: string | null,
    email: string | null
}

export type CatalogoProduto = {
    nome_produto: string,
    preco: string,
    quantidade: string
}

export type CatalogoServico = {
    descricao: string | null,
    status: string,
    valor_total: string,
    nome_empresa: string
}

export type CatalogoData = {
    empresas: CatalogoEmpresa[],
    produtos: CatalogoProduto[],
    servicos: CatalogoServico[]
}
