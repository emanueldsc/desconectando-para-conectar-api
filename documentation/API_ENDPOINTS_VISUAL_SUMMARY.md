# 📊 SUMÁRIO VISUAL - CONTRATOS DE API

## Endpoints da API Pública

```
┌─────────────────────────────────────────────────────────────────────┐
│                    🏠 ROTAS PÚBLICAS - SEM AUTENTICAÇÃO            │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 1️⃣  HOME PAGE                                                        │
├──────────────────────────────────────────────────────────────────────┤
│ GET /api/public/home                                                 │
│                                                                      │
│ RETORNA:                                                             │
│  • Hero banner (título, subtítulo, imagem, CTA)                     │
│  • Featured Raffles (3 rifas em destaque)                           │
│  • Institutions carousel (4 instituições)                           │
│  • Statistics (total doado, vidas impactadas, comunidades)          │
│  • Blog Preview (3 posts destaque)                                  │
│                                                                      │
│ ✅ Autenticação: NÃO NECESSÁRIA                                      │
│ ⚙️  Cache: Recomendado 1 hora                                        │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 2️⃣  BLOG - LISTAGEM                                                  │
├──────────────────────────────────────────────────────────────────────┤
│ GET /api/public/blog?page=1&limit=10&search=...&sort=newest         │
│                                                                      │
│ QUERY PARAMETERS:                                                    │
│  • page (default: 1)                                                │
│  • limit (default: 10)                                              │
│  • search (opcional)                                                │
│  • category (opcional)                                              │
│  • sort: newest|oldest|popular (default: newest)                    │
│                                                                      │
│ RETORNA:                                                             │
│  • Array de BlogPostPreview                                         │
│  • Pagination info                                                  │
│  • Categories com contagem                                          │
│                                                                      │
│ ✅ Autenticação: NÃO NECESSÁRIA                                      │
│ ⚙️  Cache: Recomendado 30 minutos                                    │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 3️⃣  BLOG - PUBLICAÇÃO ÚNICA                                          │
├──────────────────────────────────────────────────────────────────────┤
│ GET /api/public/blog/:id                                             │
│ GET /api/public/blog/:slug                                           │
│                                                                      │
│ ROTA PARAMS:                                                         │
│  • id: ID numérico (ex: 1)                                          │
│  • slug: URL-friendly (ex: como-solidariedade-transformou)         │
│                                                                      │
│ RETORNA:                                                             │
│  • Post completo (conteúdo HTML)                                   │
│  • Autor com bio e redes sociais                                   │
│  • Tags, categoria                                                  │
│  • Posts relacionados (3-4)                                         │
│  • Comentários (opcional)                                           │
│  • SEO metadata                                                     │
│                                                                      │
│ 📊 SIDE EFFECT: Incrementa visualizações                             │
│ ✅ Autenticação: NÃO NECESSÁRIA                                      │
│ ⚙️  Cache: Recomendado 24 horas (invalidar ao atualizar)            │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 4️⃣  RIFAS - LISTAGEM                                                 │
├──────────────────────────────────────────────────────────────────────┤
│ GET /api/public/raffles?page=1&limit=12&status=active&sort=newest   │
│                                                                      │
│ QUERY PARAMETERS:                                                    │
│  • page (default: 1)                                                │
│  • limit (default: 12)                                              │
│  • search (opcional)                                                │
│  • status: active|coming|finished (opcional)                        │
│  • sort: newest|popular|progress (default: newest)                  │
│  • includeOld: true|false (default: false) - rifas antigas          │
│                                                                      │
│ RETORNA:                                                             │
│  • Array de RaffleListItem                                          │
│  • Pagination info                                                  │
│  • Filtros disponíveis (statuses, categories com contagem)          │
│                                                                      │
│ ✅ Autenticação: NÃO NECESSÁRIA                                      │
│ ⚙️  Cache: Recomendado 1 hora                                        │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 5️⃣  RIFA - DETALHE                                                   │
├──────────────────────────────────────────────────────────────────────┤
│ GET /api/public/raffles/:id                                          │
│ GET /api/public/raffles/:slug                                        │
│                                                                      │
│ ROTA PARAMS:                                                         │
│  • id: ID numérico                                                  │
│  • slug: URL-friendly (ex: cesta-regional-nordestina)              │
│                                                                      │
│ RETORNA:                                                             │
│  • Rifa completa (descrição longa, galeria)                        │
│  • Status e datas                                                   │
│  • Array de RaffleNumber (números com status)                       │
│  • Organizador (instituição)                                        │
│  • Regras (HTML)                                                    │
│  • Informações do ganhador (se concluída)                          │
│  • SEO metadata                                                     │
│                                                                      │
│ ✅ Autenticação: NÃO NECESSÁRIA                                      │
│ ⚙️  Cache: Recomendado 2 horas                                       │
└──────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────┐
│              🔐 AUTENTICAÇÃO - Sem autenticação prévia               │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 6️⃣  LOGIN                                                             │
├──────────────────────────────────────────────────────────────────────┤
│ POST /api/auth/login                                                 │
│                                                                      │
│ BODY:                                                                │
│ {                                                                    │
│   "email": "usuario@exemplo.com",                                   │
│   "password": "senha_segura",                                       │
│   "rememberMe": false                                               │
│ }                                                                    │
│                                                                      │
│ VALIDAÇÕES:                                                          │
│  • Email: formato válido                                            │
│  • Password: mínimo 6 caracteres                                    │
│                                                                      │
│ RESPOSTA (200 OK):                                                  │
│ {                                                                    │
│   "success": true,                                                  │
│   "token": "eyJhbGciOiJIUzI1NiIs...",                              │
│   "user": { id, fullName, email, phone, avatar, role, ... },      │
│   "expiresIn": 86400,                                               │
│   "refreshToken": "optional"                                        │
│ }                                                                    │
│                                                                      │
│ RESPOSTA (401 Unauthorized):                                         │
│ {                                                                    │
│   "success": false,                                                 │
│   "message": "Email ou senha inválidos",                           │
│   "code": "INVALID_CREDENTIALS"                                     │
│ }                                                                    │
│                                                                      │
│ ✅ Autenticação: NÃO NECESSÁRIA                                      │
│ 🔒 Segurança: Password nunca em plain text, usar bcrypt/argon2     │
│ 🍪 Session: JWT Token (24 horas padrão)                             │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 7️⃣  VERIFICAR TOKEN                                                  │
├──────────────────────────────────────────────────────────────────────┤
│ GET /api/auth/verify                                                 │
│                                                                      │
│ HEADERS:                                                             │
│ Authorization: Bearer eyJhbGciOiJIUzI1NiIs...                       │
│                                                                      │
│ RESPOSTA (200 OK):                                                  │
│ {                                                                    │
│   "valid": true,                                                    │
│   "user": { id, email, role },                                      │
│   "expiresAt": "2025-05-17T14:22:00Z"                              │
│ }                                                                    │
│                                                                      │
│ RESPOSTA (401 Unauthorized):                                         │
│ {                                                                    │
│   "valid": false,                                                   │
│   "message": "Token expirado",                                      │
│   "code": "TOKEN_EXPIRED"                                           │
│ }                                                                    │
│                                                                      │
│ ✅ Autenticação: OBRIGATÓRIA (JWT Token)                             │
│ ⏱️  Verificação: Validar expiração                                    │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ 8️⃣  LOGOUT                                                            │
├──────────────────────────────────────────────────────────────────────┤
│ POST /api/auth/logout                                                │
│                                                                      │
│ HEADERS:                                                             │
│ Authorization: Bearer eyJhbGciOiJIUzI1NiIs...                       │
│ Content-Type: application/json                                      │
│                                                                      │
│ BODY:                                                                │
│ {}                                                                   │
│                                                                      │
│ RESPOSTA (200 OK):                                                  │
│ {                                                                    │
│   "success": true,                                                  │
│   "message": "Logout realizado com sucesso",                       │
│   "timestamp": "2025-05-16T14:25:00Z"                              │
│ }                                                                    │
│                                                                      │
│ ✅ Autenticação: OBRIGATÓRIA (JWT Token)                             │
│ 🔄 Efeito: Invalidar token no servidor (blacklist/revoke)           │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 📊 Matriz de Endpoints

| # | Endpoint | Método | Autenticação | Paginação | Cache | Descrição |
|---|----------|--------|--------------|-----------|-------|-----------|
| 1 | `/api/public/home` | GET | ❌ | ❌ | 1h | Página inicial |
| 2 | `/api/public/blog` | GET | ❌ | ✅ | 30m | Lista de posts |
| 3 | `/api/public/blog/:id` | GET | ❌ | ❌ | 24h | Post único |
| 4 | `/api/public/blog/:slug` | GET | ❌ | ❌ | 24h | Post por slug |
| 5 | `/api/public/raffles` | GET | ❌ | ✅ | 1h | Lista de rifas |
| 6 | `/api/public/raffles/:id` | GET | ❌ | ❌ | 2h | Rifa única |
| 7 | `/api/public/raffles/:slug` | GET | ❌ | ❌ | 2h | Rifa por slug |
| 8 | `/api/auth/login` | POST | ❌ | ❌ | ❌ | Login |
| 9 | `/api/auth/verify` | GET | ✅ | ❌ | ❌ | Validar token |
| 10 | `/api/auth/logout` | POST | ✅ | ❌ | ❌ | Logout |

---

## 🔄 Fluxo de Autenticação

```
┌─────────────────────────────────────────────────────┐
│ 1. Frontend: POST /api/auth/login                   │
│    { email, password, rememberMe }                  │
└─────────────┬───────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────┐
│ 2. Backend: Validar credenciais                     │
│    - Buscar usuário por email                       │
│    - Comparar password com bcrypt/argon2           │
│    - Validar status da conta                        │
└─────────────┬───────────────────────────────────────┘
              │
              ▼
         SUCCESS / FAIL
          /          \
         /            \
        ▼              ▼
┌──────────────┐   ┌─────────────────┐
│ 3a. Gerar    │   │ 3b. Retornar    │
│ JWT Token    │   │ erro 401        │
│ (24h)        │   │ (INVALID_CRED)  │
└──────┬───────┘   └─────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────┐
│ 4. Frontend: Armazenar token (localStorage)         │
│    Redirecionar para /login/member                  │
└─────────────┬───────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────┐
│ 5. Frontend: Usar token em headers                  │
│    Authorization: Bearer {token}                    │
│    Para requisições autenticadas                    │
└─────────────┬───────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────┐
│ 6a. Verificação automática (GET /verify)            │
│    - Validar token (não expirado)                   │
│    - Renovar se próximo a expirar (opcional)        │
│    - Retornar user info                             │
└─────────────────────────────────────────────────────┘

         SE TOKEN EXPIRADO
              │
              ▼
┌─────────────────────────────────────────────────────┐
│ 7. Frontend: POST /api/auth/logout                  │
│    Limpar token do localStorage                     │
│    Redirecionar para /login                         │
└─────────────────────────────────────────────────────┘
```

---

## 🗂️ Estrutura de Dados (TypeScript)

### Interface Principal: HomePageResponse
```typescript
{
  hero: { title, subtitle, backgroundImage, ctaLabel, ctaLink },
  featuredRaffles: RaffleCard[],
  institutions: Institution[],
  statistics: { totalDonated, livesImpacted, communitiesReached },
  blogPreview: BlogPostPreview[]
}
```

### Interface Principal: BlogListResponse
```typescript
{
  data: BlogPostPreview[],
  pagination: { total, page, limit, pages },
  categories: Array<{ label, value, count }>
}
```

### Interface Principal: RaffleListResponse
```typescript
{
  data: RaffleListItem[],
  pagination: { total, page, limit, pages },
  filters: { statuses, categories }
}
```

### Interface Principal: LoginResponse
```typescript
{
  success: true,
  token: string,
  user: { id, fullName, email, phone, avatar, role, address, createdAt },
  expiresIn: number,
  refreshToken?: string
}
```

---

## ⚙️ Configurações Recomendadas

### Headers HTTP
```
Content-Type: application/json
Accept: application/json
Accept-Language: pt-BR
User-Agent: [App Version]
```

### CORS (Cross-Origin)
```
Access-Control-Allow-Origin: https://seu-frontend.com
Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Max-Age: 86400
```

### Rate Limiting
```
X-RateLimit-Limit: 100              (requisições por janela)
X-RateLimit-Remaining: 99           (requisições restantes)
X-RateLimit-Reset: 1621198123       (timestamp de reset)
```

### Cache Headers
```
Cache-Control: public, max-age=3600  (home: 1 hora)
Cache-Control: public, max-age=1800  (blog list: 30 min)
Cache-Control: private, max-age=86400 (blog single: 24h)
ETag: "33a64df551425fcc55e4d42a148795d9f25f89d4"
```

---

## 🛡️ Segurança

- [ ] Validar HTTPS em produção
- [ ] Rate limiting por IP
- [ ] CSRF tokens para POST
- [ ] JWT com expiração
- [ ] Password hashing (bcrypt/argon2)
- [ ] Sanitizar input (SQL injection)
- [ ] Sanitizar HTML (XSS)
- [ ] CORS configurado corretamente
- [ ] Headers de segurança (X-Frame-Options, X-Content-Type-Options)
- [ ] Logging de tentativas de login falhadas

---

## 📱 Casos de Uso no Frontend

### 1. Carregar Home (App Component)
```typescript
ngOnInit() {
  this.api.get('/api/public/home').subscribe(data => {
    this.homeData.set(data);
  });
}
```

### 2. Carregar Blog Paginado (Blog Component)
```typescript
loadBlog(page: number) {
  const params = `?page=${page}&limit=10&sort=newest`;
  this.api.get(`/api/public/blog${params}`).subscribe(data => {
    this.posts.set(data.data);
    this.pagination.set(data.pagination);
  });
}
```

### 3. Carregar Post Único (Blog Detail)
```typescript
loadPost(slug: string) {
  this.api.get(`/api/public/blog/${slug}`).subscribe(post => {
    this.post.set(post);
  });
}
```

### 4. Fazer Login (Login Component)
```typescript
login(email: string, password: string) {
  this.api.post('/api/auth/login', { email, password })
    .subscribe(response => {
      localStorage.setItem('token', response.token);
      this.router.navigate(['/login/member']);
    });
}
```

### 5. Proteger Rotas (Guard)
```typescript
canActivate(): boolean {
  const token = localStorage.getItem('token');
  if (!token) return false;
  
  this.api.get('/api/auth/verify').subscribe(
    () => true,
    () => {
      this.router.navigate(['/login']);
      return false;
    }
  );
}
```

---

## 🚀 Próximos Passos

1. **Implementar em PHP** usando Laravel/Symfony
2. **Configurar banco de dados** (PostgreSQL)
3. **Setup de autenticação JWT**
4. **Implementar migrations** para tabelas
5. **Testar endpoints** com Postman/Insomnia
6. **Documentar com Swagger/OpenAPI**
7. **Integrar no frontend Angular**
8. **Configurar CI/CD pipeline**
9. **Deploy em staging**
10. **Testes de carga e segurança**

---

## 📚 Documentos Relacionados

- `API_CONTRACTS.md` - Especificação completa de todos os endpoints
- `api-contracts.models.ts` - TypeScript interfaces (copiar para seu projeto)
- `PHP_IMPLEMENTATION_EXAMPLES.php` - Exemplos de código PHP/Laravel

---

**Versão**: 1.0  
**Atualizado**: 2025-05-16  
**Stack**: Angular 21 + PHP REST API  
**Autor**: Frontend Team
