/**
 * ================================================
 * API CONTRACTS - ROTAS PÚBLICAS
 * ================================================
 * 
 * Interfaces TypeScript que mapeiam todos os contratos de dados
 * esperados da API PHP para as rotas públicas do sistema.
 * Use estas interfaces como referência para implementar os endpoints no backend.
 * 
 * Versão: 1.0
 * Data: 2025-05-16
 * Stack: Angular 21 + PHP REST API
 */

/**
 * ================================================
 * 1. HOME PAGE - GET /api/public/home
 * ================================================
 */

/** Data do herói principal da home */
export interface HeroData {
  title: string;              // Ex: "Desconectando para Conectar"
  subtitle: string;           // Ex: "Uma iniciativa solidária para o Sertão"
  backgroundImage: string;    // URL da imagem de fundo
  ctaLabel: string;          // Ex: "Participar Agora"
  ctaLink: string;           // Rota interna, ex: "/public/raffles"
}

/** Card de rifa apresentado na home */
export interface FeaturedRaffleCard {
  id: number;
  title: string;             // Ex: "Cesta Regional Nordestina"
  description: string;       // Ex: "Arrecadação de cestas básicas..."
  image: string;             // URL da imagem
  progress: number;          // 0-100 (percentual de venda)
  goal: number;              // Total de pontos/tickets goal
  current: number;           // Quantidade atual vendida
  status: 'active' | 'coming' | 'finished';
  drawDate: string;          // ISO 8601 format
  category?: string;         // Opcional: categoria da rifa
}

/** Instituição apresentada no carrossel */
export interface Institution {
  id: number;
  name: string;              // Ex: "Associação Sertaneja"
  description: string;       // Ex: "Apoio às famílias do sertão..."
  image: string;             // URL
  imagePosition?: string;    // CSS background-position (default: "center center")
}

/** Dados da response da home */
export interface HomePageResponse {
  hero: HeroData;
  featuredRaffles: FeaturedRaffleCard[];
  institutions: Institution[];
  statistics: {
    totalDonated: number;    // Valor total arrecadado (R$)
    livesImpacted: number;   // Número de vidas impactadas
    communitiesReached: number;  // Número de comunidades
  };
  blogPreview: BlogPostPreview[];  // 3 posts em destaque
}

/**
 * ================================================
 * 2. BLOG - LISTAGEM - GET /api/public/blog
 * ================================================
 */

/** Query parameters para filtro/paginação */
export interface BlogListQuery {
  page?: number;             // default: 1
  limit?: number;            // default: 10
  search?: string;           // busca por título/conteúdo
  category?: string;         // filtro por categoria
  sort?: 'newest' | 'oldest' | 'popular'; // default: 'newest'
}

/** Preview de post para listagem */
export interface BlogPostPreview {
  id: number;
  title: string;
  excerpt: string;           // Primeiras 200-300 caracteres
  content?: string;          // HTML truncado ou markdown (opcional)
  image: string;             // URL da imagem destaque
  imageAlt: string;          // Texto alternativo (importante para acessibilidade)
  eyebrow: string;           // Categoria/tag, ex: "Histórias", "Guias", "Natureza"
  description: string;       // Descrição curta (usada nos cards)
  author?: {
    id: number;
    name: string;
    avatar?: string;
  };
  category: string;          // Ex: "Histórias", "Guias", "Natureza"
  publishedAt: string;       // ISO 8601
  readTime?: number;         // Tempo estimado de leitura em minutos
  views?: number;            // Número de visualizações
  slug: string;              // URL-friendly identifier, ex: "como-solidariedade-transformou"
}

/** Response da listagem de blog */
export interface BlogListResponse {
  data: BlogPostPreview[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    pages: number;
  };
  categories?: Array<{ label: string; value: string; count: number }>;
}

/**
 * ================================================
 * 3. BLOG - PUBLICAÇÃO ÚNICA - GET /api/public/blog/:id (ou :slug)
 * ================================================
 */

/** Autor completo */
export interface BlogAuthor {
  id: number;
  name: string;
  avatar?: string;
  bio?: string;
  socialLinks?: {
    instagram?: string;
    facebook?: string;
    twitter?: string;
    email?: string;
  };
}

/** Comentário em um post */
export interface BlogComment {
  id: number;
  author: string;            // Nome do comentarista
  email: string;             // Email (pode ser oculto no frontend)
  content: string;           // Conteúdo do comentário
  createdAt: string;         // ISO 8601
  replies?: BlogComment[];    // Comentários aninhados (thread)
}

/** Post completo com conteúdo */
export interface BlogPostFull {
  id: number;
  title: string;
  content: string;           // HTML completo
  image: string;             // URL imagem destaque
  imageAlt?: string;
  author: BlogAuthor;
  category: string;
  tags?: string[];           // Array de tags
  publishedAt: string;       // ISO 8601
  updatedAt?: string;        // ISO 8601
  readTime?: number;         // minutos
  views?: number;
  slug: string;
  relatedPosts?: BlogPostPreview[];  // 3-4 posts relacionados
  comments?: BlogComment[];   // Array de comentários
  seo?: {
    metaDescription: string;
    keywords: string[];
  };
}

/**
 * ================================================
 * 4. RIFAS - LISTAGEM - GET /api/public/raffles
 * ================================================
 */

/** Query parameters para filtro/paginação de rifas */
export interface RaffleListQuery {
  page?: number;             // default: 1
  limit?: number;            // default: 12
  search?: string;           // busca por título
  status?: 'active' | 'coming' | 'finished';  // filtro por status
  sort?: 'newest' | 'popular' | 'progress';   // default: 'newest'
  includeOld?: boolean;      // incluir rifas antigas/arquivadas (default: false)
}

/** Item na listagem de rifas */
export interface RaffleListItem {
  id: number;
  title: string;
  description: string;       // Descrição curta
  image: string;             // URL da imagem
  goal: number;              // Meta total (em pontos ou reais)
  current: number;           // Quantidade atual arrecadada
  progress: number;          // 0-100 (calculado: current/goal * 100)
  status: 'active' | 'coming' | 'finished';
  drawDate: string;          // ISO 8601
  category?: string;
  ticketPrice: number;       // Preço unitário
  ticketsAvailable: number;  // Total de pontos/tickets disponíveis
  ticketsSold: number;       // Quantidade vendida
  slug: string;              // URL-friendly identifier
  createdAt: string;         // ISO 8601
}

/** Response da listagem de rifas */
export interface RaffleListResponse {
  data: RaffleListItem[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    pages: number;
  };
  filters?: {
    statuses: Array<{ label: string; value: string; count: number }>;
    categories?: Array<{ label: string; value: string; count: number }>;
  };
}

/**
 * ================================================
 * 5. RIFA - DETALHE - GET /api/public/raffles/:id (ou :slug)
 * ================================================
 */

/** Número/bilhete de uma rifa */
export interface RaffleNumber {
  number: number;            // Ex: 1, 2, 3...
  status: 'available' | 'selected' | 'occupied';
}

/** Organização/instituição responsável pela rifa */
export interface RaffleOrganization {
  id: number;
  name: string;
  logo?: string;
  description?: string;
  contact?: {
    email?: string;
    phone?: string;
    website?: string;
  };
}

/** Informações do ganhador (se rifa já foi realizada) */
export interface RaffleWinnerInfo {
  id: number;
  name: string;
  winnerNumber: number;      // Número sorteado
  drawDate: string;          // ISO 8601 da data do sorteio
  prize?: string;            // Descrição do prêmio
}

/** Detalhe completo da rifa */
export interface RaffleDetailResponse {
  id: number;
  title: string;
  description: string;       // Descrição curta
  fullDescription: string;   // HTML com descrição longa
  image: string;             // URL imagem principal
  gallery?: string[];        // Outras imagens da rifa
  goal: number;
  current: number;
  progress: number;          // 0-100
  status: 'active' | 'coming' | 'finished';
  drawDate: string;          // ISO 8601
  category?: string;
  ticketPrice: number;
  ticketsAvailable: number;
  ticketsSold: number;
  numbers: RaffleNumber[];   // Array com status de cada número
  winnerInfo?: RaffleWinnerInfo;  // Preenchido se status === 'finished'
  rules?: string;            // HTML com regras completas
  slug: string;
  createdAt: string;
  organization: RaffleOrganization;
  seo?: {
    metaDescription: string;
    keywords: string[];
  };
}

/**
 * ================================================
 * 6. AUTENTICAÇÃO - LOGIN - POST /api/auth/login
 * ================================================
 */

/** Dados que o frontend envia no login */
export interface LoginRequest {
  email: string;             // Ex: "user@example.com"
  password: string;          // Mínimo 6 caracteres
  rememberMe?: boolean;      // Manter conectado (default: false)
}

/** Dados do usuário na response */
export interface UserData {
  id: number;
  fullName: string;
  email: string;
  phone?: string;
  avatar?: string;           // URL da foto de perfil
  role: 'buyer' | 'manager' | 'publisher';
  address?: string;
  createdAt?: string;        // ISO 8601
}

/** Response bem-sucedida do login */
export interface LoginResponse {
  success: true;
  token: string;             // JWT Token para requisições autenticadas
  user: UserData;
  expiresIn: number;         // Segundos até expiração do token
  refreshToken?: string;     // Token para renovar autenticação (opcional)
}

/** Response de erro do login */
export interface LoginErrorResponse {
  success: false;
  message: string;           // Ex: "Email ou senha inválidos"
  code: 'INVALID_CREDENTIALS' | 'USER_NOT_FOUND' | 'ACCOUNT_DISABLED' | 'INVALID_EMAIL';
  timestamp?: string;        // ISO 8601
}

/** Type union para response */
export type LoginResponseType = LoginResponse | LoginErrorResponse;

/**
 * ================================================
 * 7. AUTENTICAÇÃO - VALIDAR TOKEN - GET /api/auth/verify
 * ================================================
 * 
 * Headers necessários:
 * - Authorization: Bearer {token}
 */

/** Response de validação de token */
export interface VerifyTokenResponse {
  valid: true;
  user: {
    id: number;
    email: string;
    role: string;
  };
  expiresAt: string;         // ISO 8601
}

/** Response de erro na validação */
export interface VerifyTokenErrorResponse {
  valid: false;
  message: string;           // Ex: "Token expirado" ou "Token inválido"
  code: 'TOKEN_EXPIRED' | 'TOKEN_INVALID' | 'TOKEN_MALFORMED';
}

/**
 * ================================================
 * 8. AUTENTICAÇÃO - LOGOUT - POST /api/auth/logout
 * ================================================
 * 
 * Headers necessários:
 * - Authorization: Bearer {token}
 */

export interface LogoutResponse {
  success: true;
  message: string;           // Ex: "Logout realizado com sucesso"
  timestamp?: string;        // ISO 8601
}

/**
 * ================================================
 * RESUMO DOS ENDPOINTS - ROTAS PÚBLICAS
 * ================================================
 * 
 * | Método | Path                          | Autenticação | Response Type           |
 * |--------|-------------------------------|--------------|------------------------|
 * | GET    | /api/public/home              | ❌ Não       | HomePageResponse       |
 * | GET    | /api/public/blog              | ❌ Não       | BlogListResponse       |
 * | GET    | /api/public/blog/:id          | ❌ Não       | BlogPostFull           |
 * | GET    | /api/public/blog/:slug        | ❌ Não       | BlogPostFull           |
 * | GET    | /api/public/raffles           | ❌ Não       | RaffleListResponse     |
 * | GET    | /api/public/raffles/:id       | ❌ Não       | RaffleDetailResponse   |
 * | GET    | /api/public/raffles/:slug     | ❌ Não       | RaffleDetailResponse   |
 * | POST   | /api/auth/login               | ❌ Não       | LoginResponseType      |
 * | GET    | /api/auth/verify              | ✅ JWT Token | VerifyTokenResponse    |
 * | POST   | /api/auth/logout              | ✅ JWT Token | LogoutResponse         |
 * 
 * ================================================
 * NOTAS IMPORTANTES PARA IMPLEMENTAÇÃO PHP
 * ================================================
 * 
 * 1. HEADERS PADRÃO
 *    - Content-Type: application/json
 *    - Accept: application/json
 * 
 * 2. AUTENTICAÇÃO (JWT)
 *    - Token enviado no header: Authorization: Bearer {token}
 *    - Implementar validação de expiração
 *    - Considerar refresh token para renovação
 * 
 * 3. PAGINAÇÃO
 *    - Query params: ?page=1&limit=10
 *    - Response sempre com objeto pagination
 *    - Total, page, limit, pages
 * 
 * 4. DATAS
 *    - Sempre usar ISO 8601 format (YYYY-MM-DDTHH:mm:ssZ)
 *    - Considerar timezone (recomendado UTC)
 * 
 * 5. SLUGS
 *    - URL-friendly identifiers para blog e rifas
 *    - Permitir acesso por ID numérico OU slug
 *    - Exemplos: "como-solidariedade-transformou", "cesta-regional-nordestina"
 * 
 * 6. SEO (Opcional mas recomendado)
 *    - Incluir metaDescription e keywords
 *    - Importante para indexação em buscadores
 * 
 * 7. TRATAMENTO DE ERROS
 *    - Sempre retornar response type apropriado
 *    - Mensagens em português (pt-BR)
 *    - Codes padronizados para tratamento no frontend
 * 
 * 8. VALIDAÇÕES
 *    - Email: formato válido
 *    - Senha: mínimo 6 caracteres
 *    - Campos obrigatórios conforme interface
 * 
 * 9. FILTROS E BUSCAS
 *    - Busca case-insensitive
 *    - Suportar múltiplos tipos de sort
 *    - Retornar contagens filtradas
 * 
 * 10. PERFORMANCE
 *     - Considerar cache para dados públicos (home, blog, rifas)
 *     - Cache-Control headers adequados
 *     - Lazy load de imagens e paginação
 */
