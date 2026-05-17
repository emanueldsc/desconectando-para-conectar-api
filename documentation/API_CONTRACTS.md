# 📋 ESPECIFICAÇÃO DE API - ROTAS PÚBLICAS

## Overview
Este documento detalha todos os endpoints necessários para o backend PHP da aplicação **Desconectando para Conectar**.

**Base URL**: `https://api.seu-dominio.com/api` (ajuste conforme necessário)

---

## 1. HOME PAGE

### GET `/api/public/home`

Retorna todos os dados necessários para renderizar a página inicial.

**Parâmetros**: Nenhum

**Headers**:
```
Content-Type: application/json
Accept: application/json
```

**Response (200 OK)**:
```json
{
  "hero": {
    "title": "Desconectando para Conectar",
    "subtitle": "Uma iniciativa solidária para o Sertão Nordestino",
    "backgroundImage": "https://cdn.exemplo.com/hero-bg.jpg",
    "ctaLabel": "Participar Agora",
    "ctaLink": "/public/raffles"
  },
  "featuredRaffles": [
    {
      "id": 1,
      "title": "Cesta Regional Nordestina",
      "description": "Arrecadação de cestas básicas para famílias em vulnerabilidade",
      "image": "https://cdn.exemplo.com/rifa-1.jpg",
      "progress": 65,
      "goal": 5000,
      "current": 3200,
      "status": "active",
      "drawDate": "2025-12-15T00:00:00Z",
      "category": "Alimentação"
    },
    {
      "id": 2,
      "title": "Escola do Campo",
      "description": "Material escolar para crianças da zona rural",
      "image": "https://cdn.exemplo.com/rifa-2.jpg",
      "progress": 60,
      "goal": 3000,
      "current": 1800,
      "status": "active",
      "drawDate": "2025-11-20T00:00:00Z",
      "category": "Educação"
    },
    {
      "id": 3,
      "title": "Poço Artesiano",
      "description": "Construção de poço para comunidade sem acesso à água",
      "image": "https://cdn.exemplo.com/rifa-3.jpg",
      "progress": 100,
      "goal": 12000,
      "current": 12000,
      "status": "finished",
      "drawDate": "2025-09-10T00:00:00Z",
      "category": "Infraestrutura"
    }
  ],
  "institutions": [
    {
      "id": 1,
      "name": "Associação Sertaneja",
      "description": "Apoio às famílias do sertão nordestino com ações de impacto social",
      "image": "https://cdn.exemplo.com/instituicao-1.jpg",
      "imagePosition": "left center"
    },
    {
      "id": 2,
      "name": "Instituto Raízes",
      "description": "Educação e cultura para comunidades rurais",
      "image": "https://cdn.exemplo.com/instituicao-2.jpg",
      "imagePosition": "center center"
    },
    {
      "id": 3,
      "name": "Rede Caatinga",
      "description": "Preservação da Caatinga e tecnologias sustentáveis",
      "image": "https://cdn.exemplo.com/instituicao-3.jpg",
      "imagePosition": "right center"
    },
    {
      "id": 4,
      "name": "Projeto Mandacaru",
      "description": "Fortalecimento da agricultura familiar local",
      "image": "https://cdn.exemplo.com/instituicao-4.jpg",
      "imagePosition": "center top"
    }
  ],
  "statistics": {
    "totalDonated": 245000.50,
    "livesImpacted": 1250,
    "communitiesReached": 48
  },
  "blogPreview": [
    {
      "id": 1,
      "title": "Como a solidariedade transformou o Sertão",
      "excerpt": "Uma história de transformação social e impacto comunitário...",
      "image": "https://cdn.exemplo.com/blog-1.jpg",
      "imageAlt": "Paisagem do sertão com árvores e céu aberto",
      "eyebrow": "Histórias",
      "description": "Como a solidariedade transformou o Sertão",
      "slug": "como-solidariedade-transformou",
      "publishedAt": "2025-05-10T14:30:00Z",
      "readTime": 5
    },
    {
      "id": 2,
      "title": "Rifa do Bem: como funciona e como participar",
      "excerpt": "Entenda como participar das nossas rifas e contribuir...",
      "image": "https://cdn.exemplo.com/blog-2.jpg",
      "imageAlt": "Vista de vegetação da caatinga ao entardecer",
      "eyebrow": "Guias",
      "description": "Rifa do Bem: como funciona e como participar",
      "slug": "rifa-bem-como-participar",
      "publishedAt": "2025-05-08T10:15:00Z",
      "readTime": 4
    },
    {
      "id": 3,
      "title": "Caatinga viva: natureza que inspira resistência",
      "excerpt": "Descubra a beleza única e resilência da Caatinga...",
      "image": "https://cdn.exemplo.com/blog-3.jpg",
      "imageAlt": "Cenário natural da caatinga com vegetação nativa",
      "eyebrow": "Natureza",
      "description": "Caatinga viva: natureza que inspira resistência",
      "slug": "caatinga-viva-resistencia",
      "publishedAt": "2025-05-05T09:20:00Z",
      "readTime": 6
    }
  ]
}
```

---

## 2. BLOG - LISTAGEM

### GET `/api/public/blog`

Retorna lista paginada de posts do blog com filtros.

**Query Parameters**:
```
page=1           (opcional, default: 1)
limit=10         (opcional, default: 10)
search=...       (opcional, busca em título/conteúdo)
category=...     (opcional, filtrar por categoria)
sort=newest      (opcional: newest|oldest|popular, default: newest)
```

**Exemplos de URL**:
```
GET /api/public/blog
GET /api/public/blog?page=2&limit=15
GET /api/public/blog?search=solidariedade
GET /api/public/blog?category=Histórias&sort=popular
```

**Response (200 OK)**:
```json
{
  "data": [
    {
      "id": 1,
      "title": "Como a solidariedade transformou o Sertão",
      "excerpt": "Uma história de transformação social e impacto comunitário que mudou a vida de centenas de famílias...",
      "image": "https://cdn.exemplo.com/blog-1.jpg",
      "imageAlt": "Paisagem do sertão com árvores",
      "eyebrow": "Histórias",
      "description": "Como a solidariedade transformou o Sertão",
      "category": "Histórias",
      "slug": "como-solidariedade-transformou",
      "publishedAt": "2025-05-10T14:30:00Z",
      "readTime": 5,
      "views": 156,
      "author": {
        "id": 1,
        "name": "João Silva",
        "avatar": "https://cdn.exemplo.com/avatar-joao.jpg"
      }
    },
    {
      "id": 2,
      "title": "Rifa do Bem: como funciona e como participar",
      "excerpt": "Entenda como participar das nossas rifas e contribuir para transformar vidas...",
      "image": "https://cdn.exemplo.com/blog-2.jpg",
      "imageAlt": "Vista de vegetação da caatinga",
      "eyebrow": "Guias",
      "description": "Rifa do Bem: como funciona e como participar",
      "category": "Guias",
      "slug": "rifa-bem-como-participar",
      "publishedAt": "2025-05-08T10:15:00Z",
      "readTime": 4,
      "views": 89,
      "author": {
        "id": 2,
        "name": "Maria Santos",
        "avatar": "https://cdn.exemplo.com/avatar-maria.jpg"
      }
    }
  ],
  "pagination": {
    "total": 18,
    "page": 1,
    "limit": 10,
    "pages": 2
  },
  "categories": [
    {
      "label": "Histórias",
      "value": "historias",
      "count": 8
    },
    {
      "label": "Guias",
      "value": "guias",
      "count": 5
    },
    {
      "label": "Natureza",
      "value": "natureza",
      "count": 5
    }
  ]
}
```

**Response (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Página inválida",
  "code": "INVALID_PAGE"
}
```

---

## 3. BLOG - PUBLICAÇÃO ÚNICA

### GET `/api/public/blog/:id`
### GET `/api/public/blog/:slug`

Retorna o conteúdo completo de um post individual.

**Parâmetros de Rota**:
- `:id` - ID numérico do post (ex: `1`)
- `:slug` - Slug do post (ex: `como-solidariedade-transformou`)

**Exemplos**:
```
GET /api/public/blog/1
GET /api/public/blog/como-solidariedade-transformou
```

**Response (200 OK)**:
```json
{
  "id": 1,
  "title": "Como a solidariedade transformou o Sertão",
  "content": "<h2>Introdução</h2><p>Uma história de transformação social...</p><h2>Capítulo 1</h2><p>...</p>",
  "image": "https://cdn.exemplo.com/blog-1.jpg",
  "imageAlt": "Paisagem do sertão",
  "author": {
    "id": 1,
    "name": "João Silva",
    "avatar": "https://cdn.exemplo.com/avatar-joao.jpg",
    "bio": "Jornalista especializado em impacto social",
    "socialLinks": {
      "instagram": "https://instagram.com/joaosilva",
      "email": "joao@exemplo.com"
    }
  },
  "category": "Histórias",
  "tags": ["Sertão", "Solidariedade", "Transformação"],
  "publishedAt": "2025-05-10T14:30:00Z",
  "updatedAt": "2025-05-12T08:15:00Z",
  "readTime": 5,
  "views": 156,
  "slug": "como-solidariedade-transformou",
  "relatedPosts": [
    {
      "id": 4,
      "title": "Educação transformadora no interior nordestino",
      "excerpt": "...",
      "image": "https://cdn.exemplo.com/blog-4.jpg",
      "imageAlt": "...",
      "eyebrow": "Histórias",
      "description": "Educação transformadora no interior nordestino",
      "slug": "educacao-transformadora",
      "publishedAt": "2025-05-02T11:20:00Z",
      "readTime": 7
    }
  ],
  "comments": [
    {
      "id": 1,
      "author": "Ana Costa",
      "email": "ana@exemplo.com",
      "content": "Excelente matéria! Muito inspirador.",
      "createdAt": "2025-05-11T16:30:00Z",
      "replies": []
    }
  ],
  "seo": {
    "metaDescription": "Como a solidariedade transformou o Sertão - leia a história completa de impacto social",
    "keywords": ["Sertão", "Solidariedade", "Transformação Social"]
  }
}
```

**Response (404 Not Found)**:
```json
{
  "success": false,
  "message": "Post não encontrado",
  "code": "POST_NOT_FOUND"
}
```

---

## 4. RIFAS - LISTAGEM

### GET `/api/public/raffles`

Retorna lista paginada de rifas com filtros.

**Query Parameters**:
```
page=1               (opcional, default: 1)
limit=12             (opcional, default: 12)
search=...           (opcional, busca por título)
status=active        (opcional: active|coming|finished)
sort=newest          (opcional: newest|popular|progress, default: newest)
includeOld=false     (opcional, incluir rifas antigas, default: false)
```

**Exemplos**:
```
GET /api/public/raffles
GET /api/public/raffles?status=active&sort=progress
GET /api/public/raffles?search=cesta&includeOld=true
```

**Response (200 OK)**:
```json
{
  "data": [
    {
      "id": 1,
      "title": "Cesta Regional Nordestina",
      "description": "Arrecadação de cestas básicas para famílias em vulnerabilidade",
      "image": "https://cdn.exemplo.com/rifa-1.jpg",
      "goal": 5000,
      "current": 3200,
      "progress": 64,
      "status": "active",
      "drawDate": "2025-12-15T00:00:00Z",
      "category": "Alimentação",
      "ticketPrice": 10,
      "ticketsAvailable": 5000,
      "ticketsSold": 3200,
      "slug": "cesta-regional-nordestina",
      "createdAt": "2025-04-01T10:00:00Z"
    },
    {
      "id": 2,
      "title": "Escola do Campo",
      "description": "Material escolar para crianças da zona rural",
      "image": "https://cdn.exemplo.com/rifa-2.jpg",
      "goal": 3000,
      "current": 1800,
      "progress": 60,
      "status": "active",
      "drawDate": "2025-11-20T00:00:00Z",
      "category": "Educação",
      "ticketPrice": 5,
      "ticketsAvailable": 3000,
      "ticketsSold": 1800,
      "slug": "escola-campo",
      "createdAt": "2025-03-15T14:30:00Z"
    },
    {
      "id": 3,
      "title": "Poço Artesiano",
      "description": "Construção de poço para comunidade",
      "image": "https://cdn.exemplo.com/rifa-3.jpg",
      "goal": 12000,
      "current": 12000,
      "progress": 100,
      "status": "finished",
      "drawDate": "2025-09-10T00:00:00Z",
      "category": "Infraestrutura",
      "ticketPrice": 20,
      "ticketsAvailable": 12000,
      "ticketsSold": 12000,
      "slug": "poco-artesiano",
      "createdAt": "2025-01-20T09:00:00Z"
    }
  ],
  "pagination": {
    "total": 42,
    "page": 1,
    "limit": 12,
    "pages": 4
  },
  "filters": {
    "statuses": [
      {
        "label": "Em andamento",
        "value": "active",
        "count": 18
      },
      {
        "label": "Em breve",
        "value": "coming",
        "count": 8
      },
      {
        "label": "Concluída",
        "value": "finished",
        "count": 16
      }
    ],
    "categories": [
      {
        "label": "Alimentação",
        "value": "alimentacao",
        "count": 12
      },
      {
        "label": "Educação",
        "value": "educacao",
        "count": 15
      },
      {
        "label": "Infraestrutura",
        "value": "infraestrutura",
        "count": 10
      }
    ]
  }
}
```

---

## 5. RIFA - DETALHE

### GET `/api/public/raffles/:id`
### GET `/api/public/raffles/:slug`

Retorna o detalhe completo de uma rifa.

**Exemplos**:
```
GET /api/public/raffles/1
GET /api/public/raffles/cesta-regional-nordestina
```

**Response (200 OK)**:
```json
{
  "id": 1,
  "title": "Cesta Regional Nordestina",
  "description": "Arrecadação de cestas básicas",
  "fullDescription": "<h3>Objetivo</h3><p>Arrecadar recursos para...</p><h3>Como funciona</h3><p>...</p>",
  "image": "https://cdn.exemplo.com/rifa-1.jpg",
  "gallery": [
    "https://cdn.exemplo.com/rifa-1-gallery-1.jpg",
    "https://cdn.exemplo.com/rifa-1-gallery-2.jpg",
    "https://cdn.exemplo.com/rifa-1-gallery-3.jpg"
  ],
  "goal": 5000,
  "current": 3200,
  "progress": 64,
  "status": "active",
  "drawDate": "2025-12-15T00:00:00Z",
  "category": "Alimentação",
  "ticketPrice": 10,
  "ticketsAvailable": 5000,
  "ticketsSold": 3200,
  "numbers": [
    {
      "number": 1,
      "status": "available"
    },
    {
      "number": 2,
      "status": "selected"
    },
    {
      "number": 3,
      "status": "available"
    },
    {
      "number": 4,
      "status": "occupied"
    },
    {
      "number": 5,
      "status": "occupied"
    }
  ],
  "slug": "cesta-regional-nordestina",
  "createdAt": "2025-04-01T10:00:00Z",
  "organization": {
    "id": 1,
    "name": "Instituto Raízes",
    "logo": "https://cdn.exemplo.com/logo-raizes.png",
    "description": "Educação e cultura para comunidades rurais",
    "contact": {
      "email": "contato@raizes.org.br",
      "phone": "(87) 9999-8888",
      "website": "https://raizes.org.br"
    }
  },
  "rules": "<h4>Regras da Rifa</h4><p>1. Participação aberta ao público em geral...</p><p>2. Cada número custa R$ 10,00...</p>",
  "seo": {
    "metaDescription": "Participe da Cesta Regional Nordestina - rifa solidária",
    "keywords": ["rifa", "solidária", "sertão", "nordeste"]
  }
}
```

**Response (404 Not Found)**:
```json
{
  "success": false,
  "message": "Rifa não encontrada",
  "code": "RAFFLE_NOT_FOUND"
}
```

---

## 6. LOGIN

### POST `/api/auth/login`

Realiza autenticação do usuário via email e senha.

**Request Body**:
```json
{
  "email": "usuario@exemplo.com",
  "password": "senha_segura_123",
  "rememberMe": false
}
```

**Headers**:
```
Content-Type: application/json
Accept: application/json
```

**Response (200 OK)**:
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c",
  "user": {
    "id": 1,
    "fullName": "João Silva Santos",
    "email": "joao@exemplo.com",
    "phone": "(87) 99999-0000",
    "avatar": "https://cdn.exemplo.com/avatar-joao.jpg",
    "role": "buyer",
    "address": "Rua das Flores, 123 - Sertânia, PE 56500-000",
    "createdAt": "2025-01-15T10:30:00Z"
  },
  "expiresIn": 86400,
  "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (401 Unauthorized)**:
```json
{
  "success": false,
  "message": "Email ou senha inválidos",
  "code": "INVALID_CREDENTIALS",
  "timestamp": "2025-05-16T14:22:00Z"
}
```

**Response (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Email ou senha não fornecidos",
  "code": "INVALID_EMAIL",
  "timestamp": "2025-05-16T14:22:00Z"
}
```

---

## 7. VERIFICAR TOKEN

### GET `/api/auth/verify`

Valida se um token JWT ainda é válido.

**Headers**:
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Accept: application/json
```

**Response (200 OK)**:
```json
{
  "valid": true,
  "user": {
    "id": 1,
    "email": "joao@exemplo.com",
    "role": "buyer"
  },
  "expiresAt": "2025-05-17T14:22:00Z"
}
```

**Response (401 Unauthorized)**:
```json
{
  "valid": false,
  "message": "Token expirado",
  "code": "TOKEN_EXPIRED"
}
```

---

## 8. LOGOUT

### POST `/api/auth/logout`

Realiza logout do usuário (opcional, pode ser client-side apenas).

**Headers**:
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

**Request Body**:
```json
{}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Logout realizado com sucesso",
  "timestamp": "2025-05-16T14:25:00Z"
}
```

---

## PADRÕES GERAIS

### Status HTTP

| Status | Uso |
|--------|-----|
| 200 | Requisição bem-sucedida |
| 400 | Bad Request - dados inválidos |
| 401 | Não autenticado |
| 403 | Não autorizado |
| 404 | Recurso não encontrado |
| 500 | Erro interno do servidor |

### Formato de Datas

Todas as datas devem estar em formato **ISO 8601**:
```
2025-05-16T14:22:00Z
```

### Paginação

Todas as listagens devem seguir o padrão:
```json
{
  "data": [],
  "pagination": {
    "total": 100,
    "page": 1,
    "limit": 10,
    "pages": 10
  }
}
```

### Erro Padrão

```json
{
  "success": false,
  "message": "Descrição do erro em português",
  "code": "ERROR_CODE_EM_MAIUSCULA",
  "timestamp": "2025-05-16T14:22:00Z"
}
```

### Autenticação JWT

- Token deve ser enviado no header: `Authorization: Bearer {token}`
- Token expira após o tempo especificado em `expiresIn` (em segundos)
- Opcional: Implementar refresh token para renovação sem fazer login novamente

### Slugs

- URL-friendly identifiers
- Lowercase
- Sem espaços (use hífens)
- Sem acentos
- Exemplos:
  - `como-solidariedade-transformou`
  - `cesta-regional-nordestina`
  - `rifa-bem-como-participar`

### CORS (Para frontend em outro domínio)

Configurar headers:
```
Access-Control-Allow-Origin: https://seu-frontend.com
Access-Control-Allow-Methods: GET, POST, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

### Rate Limiting (Recomendado)

Implementar limite de requisições por IP/usuário:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1621198123
```

---

## TESTE RÁPIDO COM CURL

```bash
# Home Page
curl -X GET "https://api.seu-dominio.com/api/public/home" \
  -H "Content-Type: application/json"

# Blog List
curl -X GET "https://api.seu-dominio.com/api/public/blog?page=1&limit=10" \
  -H "Content-Type: application/json"

# Blog Single
curl -X GET "https://api.seu-dominio.com/api/public/blog/1" \
  -H "Content-Type: application/json"

# Rifas List
curl -X GET "https://api.seu-dominio.com/api/public/raffles?status=active" \
  -H "Content-Type: application/json"

# Rifa Detail
curl -X GET "https://api.seu-dominio.com/api/public/raffles/1" \
  -H "Content-Type: application/json"

# Login
curl -X POST "https://api.seu-dominio.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@exemplo.com","password":"senha123"}'

# Verify Token
curl -X GET "https://api.seu-dominio.com/api/auth/verify" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

# Logout
curl -X POST "https://api.seu-dominio.com/api/auth/logout" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Content-Type: application/json"
```

---

## PRÓXIMOS ENDPOINTS A DOCUMENTAR

Após implementar os endpoints públicos, documentaremos:

- **Área do Membro Protegida**:
  - GET `/api/member/profile` - Dados do usuário logado
  - GET `/api/member/raffles` - Rifas que o usuário participou
  - GET `/api/member/donations` - Histórico de doações
  - PUT `/api/member/profile` - Atualizar dados do usuário

- **Dashboard Admin** (requer role: manager/publisher):
  - CRUD de Rifas
  - CRUD de Doações
  - CRUD de Usuários
  - GET/PUT endpoints para CMS (banners, phrases, contact info)
  - CRUD de Blog/Conteúdo

---

## DÚVIDAS?

Consulte o arquivo `src/app/shared/models/api-contracts.models.ts` para as interfaces TypeScript completas de cada endpoint.
