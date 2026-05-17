# 📋 QUICK REFERENCE - Contratos de API

## 🎯 Todos os 10 Endpoints em Uma Página

### Endpoints Públicos (Sem Autenticação)

#### 1. GET `/api/public/home` - Página Inicial
```json
REQUEST:
{}

RESPONSE 200:
{
  "hero": { title, subtitle, backgroundImage, ctaLabel, ctaLink },
  "featuredRaffles": [ { id, title, description, image, progress, goal, current, status, drawDate, category } ],
  "institutions": [ { id, name, description, image, imagePosition } ],
  "statistics": { totalDonated, livesImpacted, communitiesReached },
  "blogPreview": [ { id, title, excerpt, image, imageAlt, eyebrow, description, slug, publishedAt, readTime } ]
}
```

---

#### 2. GET `/api/public/blog` - Lista de Posts
```
QUERY: ?page=1&limit=10&search=...&category=...&sort=newest
RESPONSE 200:
{
  "data": [ { id, title, excerpt, image, imageAlt, eyebrow, description, slug, publishedAt, readTime, views, author } ],
  "pagination": { total, page, limit, pages },
  "categories": [ { label, value, count } ]
}
```

---

#### 3. GET `/api/public/blog/:id` - Post Único
```
RESPONSE 200:
{
  id, title, content (HTML), image, imageAlt, author (com bio),
  category, tags, publishedAt, updatedAt, readTime, views, slug,
  relatedPosts, comments, seo
}
```

---

#### 4. GET `/api/public/blog/:slug` - Post por Slug
```
Mesma response que /blog/:id
```

---

#### 5. GET `/api/public/raffles` - Lista de Rifas
```
QUERY: ?page=1&limit=12&search=...&status=active&sort=newest&includeOld=false
RESPONSE 200:
{
  "data": [ { id, title, description, image, goal, current, progress, status, drawDate, category, ticketPrice, ticketsAvailable, ticketsSold, slug, createdAt } ],
  "pagination": { total, page, limit, pages },
  "filters": { statuses: [ { label, value, count } ], categories: [ { label, value, count } ] }
}
```

---

#### 6. GET `/api/public/raffles/:id` - Rifa Única
```
RESPONSE 200:
{
  id, title, description, fullDescription (HTML), image, gallery,
  goal, current, progress, status, drawDate, category,
  ticketPrice, ticketsAvailable, ticketsSold,
  numbers: [ { number, status: available|selected|occupied } ],
  winnerInfo (se concluída), rules (HTML), slug,
  organization: { id, name, logo, description, contact },
  seo
}
```

---

#### 7. GET `/api/public/raffles/:slug` - Rifa por Slug
```
Mesma response que /raffles/:id
```

---

#### 8. POST `/api/auth/login` - Autenticação
```json
REQUEST:
{
  "email": "usuario@exemplo.com",
  "password": "senha_123",
  "rememberMe": false
}

RESPONSE 200:
{
  "success": true,
  "token": "JWT_TOKEN_AQUI",
  "user": { id, fullName, email, phone, avatar, role, address, createdAt },
  "expiresIn": 86400,
  "refreshToken": "optional"
}

RESPONSE 401:
{
  "success": false,
  "message": "Email ou senha inválidos",
  "code": "INVALID_CREDENTIALS"
}
```

---

### Endpoints Protegidos (Requer JWT Token)

#### 9. GET `/api/auth/verify` - Validar Token
```
HEADER: Authorization: Bearer {token}

RESPONSE 200:
{
  "valid": true,
  "user": { id, email, role },
  "expiresAt": "2025-05-17T14:22:00Z"
}

RESPONSE 401:
{
  "valid": false,
  "message": "Token expirado",
  "code": "TOKEN_EXPIRED"
}
```

---

#### 10. POST `/api/auth/logout` - Logout
```
HEADER: Authorization: Bearer {token}

RESPONSE 200:
{
  "success": true,
  "message": "Logout realizado com sucesso",
  "timestamp": "2025-05-16T14:25:00Z"
}
```

---

## 🗂️ Estrutura de Dados em JSON

### RaffleCard
```json
{
  "id": 1,
  "title": "Cesta Regional Nordestina",
  "description": "Arrecadação de cestas básicas...",
  "image": "https://cdn.exemplo.com/rifa-1.jpg",
  "progress": 65,
  "goal": 5000,
  "current": 3200,
  "status": "active|coming|finished",
  "drawDate": "2025-12-15T00:00:00Z",
  "category": "Alimentação"
}
```

### BlogPostPreview
```json
{
  "id": 1,
  "title": "Como a solidariedade transformou o Sertão",
  "excerpt": "Uma história de transformação...",
  "image": "https://cdn.exemplo.com/blog-1.jpg",
  "imageAlt": "Paisagem do sertão",
  "eyebrow": "Histórias",
  "description": "Como a solidariedade transformou o Sertão",
  "slug": "como-solidariedade-transformou",
  "publishedAt": "2025-05-10T14:30:00Z",
  "readTime": 5,
  "views": 156,
  "author": {
    "id": 1,
    "name": "João Silva",
    "avatar": "https://cdn.exemplo.com/avatar.jpg"
  }
}
```

### RaffleListItem
```json
{
  "id": 1,
  "title": "Cesta Regional Nordestina",
  "description": "Arrecadação de cestas básicas...",
  "image": "https://cdn.exemplo.com/rifa.jpg",
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
}
```

### UserData
```json
{
  "id": 1,
  "fullName": "João Silva Santos",
  "email": "joao@exemplo.com",
  "phone": "(87) 99999-0000",
  "avatar": "https://cdn.exemplo.com/avatar.jpg",
  "role": "buyer|manager|publisher",
  "address": "Rua das Flores, 123 - Sertânia, PE",
  "createdAt": "2025-01-15T10:30:00Z"
}
```

---

## 🔄 Status HTTP

| Status | Significado | Exemplo |
|--------|-------------|---------|
| 200 | OK | Requisição bem-sucedida |
| 400 | Bad Request | Dados inválidos |
| 401 | Unauthorized | Token ausente/expirado |
| 403 | Forbidden | Sem permissão |
| 404 | Not Found | Recurso não existe |
| 422 | Unprocessable Entity | Erro de validação |
| 500 | Internal Error | Erro no servidor |

---

## 📊 Paginação Padrão

```json
"pagination": {
  "total": 100,        // Total de registros
  "page": 1,          // Página atual
  "limit": 10,        // Registros por página
  "pages": 10         // Total de páginas
}
```

**Exemplo de uso**:
- Primeira página: `?page=1&limit=10`
- Segunda página: `?page=2&limit=10`
- Customizar limite: `?page=1&limit=50`

---

## 🔑 Formato de Datas

```
ISO 8601: 2025-05-16T14:22:00Z
           YYYY-MM-DDTHH:mm:ssZ
```

**Sempre usar UTC (Z suffix)**

---

## 🛡️ Headers Padrão

### Request Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  // Apenas para endpoints protegidos
```

### Response Headers
```
Content-Type: application/json
Cache-Control: public, max-age=3600
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1621198123
```

---

## 🎯 Valores Enum

### Status da Rifa
```
'active'    - Rifa aberta para participação
'coming'    - Rifa em breve
'finished'  - Rifa encerrada/sorteada
```

### Papel do Usuário
```
'buyer'     - Comprador/participante
'manager'   - Gerenciador de rifas/doações
'publisher' - Publicador de conteúdo/blog
```

### Status do Número
```
'available' - Disponível para compra
'selected'  - Selecionado pelo usuário
'occupied'  - Já vendido/sorteado
```

### Status do Usuário
```
'active'    - Conta ativa
'inactive'  - Conta desativada
```

---

## 🔐 Autenticação JWT

### Fluxo Completo

```
1. POST /api/auth/login
   └─> Recebe token JWT

2. Armazenar: localStorage.setItem('token', response.token)

3. Para requisições autenticadas:
   Header: Authorization: Bearer {token}

4. GET /api/auth/verify
   └─> Validar se token ainda é válido

5. POST /api/auth/logout
   └─> Remover token do servidor
```

### Token Expiration
- Default: 24 horas (86400 segundos)
- Renovação: Implementar refresh token (opcional)
- Frontend: Guardar em `localStorage` ou `sessionStorage`

---

## 🚨 Tratamento de Erros

### Erro Genérico
```json
{
  "success": false,
  "message": "Descrição do erro em português",
  "code": "CODIGO_DO_ERRO",
  "timestamp": "2025-05-16T14:22:00Z"
}
```

### Erro de Validação
```json
{
  "success": false,
  "message": "Erro de validação",
  "code": "VALIDATION_ERROR",
  "errors": {
    "email": ["Email é obrigatório"],
    "password": ["Mínimo 6 caracteres"]
  }
}
```

### Erro de Autenticação
```json
{
  "success": false,
  "message": "Email ou senha inválidos",
  "code": "INVALID_CREDENTIALS",
  "timestamp": "2025-05-16T14:22:00Z"
}
```

---

## 📝 Filtros e Buscas

### Blog - Filtros
- `search`: Busca em título/conteúdo
- `category`: Filtro por categoria
- `sort`: Ordenação (newest|oldest|popular)

### Rifas - Filtros
- `search`: Busca por título
- `status`: Filtro por status
- `sort`: Ordenação (newest|popular|progress)
- `includeOld`: Incluir rifas antigas

---

## 🧪 Testes com CURL

```bash
# 1. Home
curl -X GET "http://localhost:8000/api/public/home"

# 2. Blog List
curl -X GET "http://localhost:8000/api/public/blog?page=1&limit=10"

# 3. Blog Single
curl -X GET "http://localhost:8000/api/public/blog/1"

# 4. Rifas List
curl -X GET "http://localhost:8000/api/public/raffles?status=active"

# 5. Rifa Detail
curl -X GET "http://localhost:8000/api/public/raffles/1"

# 6. Login
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@exemplo.com",
    "password": "senha123"
  }'

# 7. Verify Token
curl -X GET "http://localhost:8000/api/auth/verify" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

# 8. Logout
curl -X POST "http://localhost:8000/api/auth/logout" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

---

## 📱 Implementação no Angular

### Criar Serviço de API

```typescript
// src/app/shared/services/api.service.ts
import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private baseUrl = 'http://localhost:8000/api';

  getHome(): Observable<HomePageResponse> {
    return this.http.get<HomePageResponse>(`${this.baseUrl}/public/home`);
  }

  getBlog(page = 1, limit = 10): Observable<BlogListResponse> {
    return this.http.get<BlogListResponse>(
      `${this.baseUrl}/public/blog?page=${page}&limit=${limit}`
    );
  }

  getBlogPost(id: number): Observable<BlogPostFull> {
    return this.http.get<BlogPostFull>(`${this.baseUrl}/public/blog/${id}`);
  }

  getRaffles(page = 1, limit = 12): Observable<RaffleListResponse> {
    return this.http.get<RaffleListResponse>(
      `${this.baseUrl}/public/raffles?page=${page}&limit=${limit}`
    );
  }

  getRaffle(id: number): Observable<RaffleDetailResponse> {
    return this.http.get<RaffleDetailResponse>(`${this.baseUrl}/public/raffles/${id}`);
  }

  login(email: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.baseUrl}/auth/login`, {
      email,
      password
    });
  }

  verifyToken(): Observable<VerifyTokenResponse> {
    return this.http.get<VerifyTokenResponse>(`${this.baseUrl}/auth/verify`);
  }

  logout(): Observable<LogoutResponse> {
    return this.http.post<LogoutResponse>(`${this.baseUrl}/auth/logout`, {});
  }
}
```

### Usar em Component

```typescript
import { inject } from '@angular/core';
import { ApiService } from '@shared/services/api.service';

export class HomeComponent {
  private api = inject(ApiService);

  ngOnInit() {
    this.api.getHome().subscribe(data => {
      // Usar data
    });
  }
}
```

---

## ✅ Checklist Antes de Deploy

- [ ] Todos os 10 endpoints implementados
- [ ] Testes unitários passando
- [ ] Testes de integração passando
- [ ] CORS configurado corretamente
- [ ] Rate limiting ativo
- [ ] JWT tokens funcionando
- [ ] Database migrations executadas
- [ ] Seeders populando dados
- [ ] Logs configurados
- [ ] Variáveis de ambiente definidas
- [ ] HTTPS/SSL habilitado
- [ ] Headers de segurança adicionados
- [ ] Documentação atualizada
- [ ] Backup do banco realizado

---

## 📚 Arquivos de Referência

| Arquivo | Conteúdo |
|---------|----------|
| `API_CONTRACTS.md` | Especificação completa com exemplos JSON |
| `API_ENDPOINTS_VISUAL_SUMMARY.md` | Diagramas visuais e matrizes |
| `PHP_IMPLEMENTATION_EXAMPLES.php` | Código Laravel pronto para usar |
| `IMPLEMENTATION_GUIDE_PHP.md` | Guia passo-a-passo de implementação |
| `QUICK_REFERENCE.md` | Este arquivo - referência rápida |

---

**Versão**: 1.0  
**Data**: 2025-05-16  
**Stack**: Angular 21 + PHP/Laravel REST API  
**Status**: 🟢 Pronto para implementação
