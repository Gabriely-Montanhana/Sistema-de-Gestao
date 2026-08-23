export interface ApiResponse<T> {
    success: boolean;
    message?: string;
    data: T;
}

export interface DashboardIndicadores {
    receita_total: string;
    total_servicos: string;
    servicos_pendentes: string;
    produtos_estoque_baixo: string;
    produto_mais_usado: string | null;
    empresa_destaque: string | null;
}

export interface ServicoPorStatus {
    status: string;
    quantidade: string;
    receita: string;
}

export interface DashboardTotais {
    quantidade: number;
    receita: number;
}

export interface DashboardData {
    indicadores: DashboardIndicadores | null;
    servicos_por_status: ServicoPorStatus[];
}

export interface Empresa {
    id_empresa: string;
    nome_empresa: string;
    cnpj: string;
    cidade: string | null;
    endereco: string | null;
    telefone: string | null;
    email: string | null;
    status: 'Ativo' | 'Inativo';
}

export interface EmpresaInput {
    nome_empresa: string;
    cnpj: string;
    cidade?: string | null;
    endereco?: string | null;
    telefone?: string | null;
    email?: string | null;
}
