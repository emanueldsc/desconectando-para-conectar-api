# 📚 ÍNDICE DE DOCUMENTAÇÃO - API Backend

## 📖 Bem-vindo!

Este projeto de backend foi completamente mapeado e documentado. Todos os dados estruturados esperados do frontend Angular foram catalogados em diferentes formatos para facilitar a implementação.

---

## 🎯 Por Onde Começar?

### 1️⃣ **Quer entender rápido (5 minutos)?**
   👉 Leia: [QUICK_REFERENCE.md](./QUICK_REFERENCE.md)
   - Todos os 10 endpoints em uma página
   - Exemplos JSON prontos
   - Estruturas de dados

### 2️⃣ **Quer ver os diagramas e visuais?**
   👉 Leia: [API_ENDPOINTS_VISUAL_SUMMARY.md](./API_ENDPOINTS_VISUAL_SUMMARY.md)
   - Matrizes de endpoints
   - Fluxos de autenticação
   - Diagramas ASCII
   - Casos de uso

### 3️⃣ **Quer a especificação completa?**
   👉 Leia: [API_CONTRACTS.md](./API_CONTRACTS.md)
   - Descrição detalhada de cada endpoint
   - Query parameters
   - Request/response completo
   - Exemplos de erro
   - Curl commands

### 4️⃣ **Quer ver código PHP de exemplo?**
   👉 Leia: [PHP_IMPLEMENTATION_EXAMPLES.php](./PHP_IMPLEMENTATION_EXAMPLES.php)
   - Controllers Laravel prontos
   - Models e Migrations
   - Middleware e validação
   - Tratamento de erros

### 5️⃣ **Quer um guia passo-a-passo para implementar?**
   👉 Leia: [IMPLEMENTATION_GUIDE_PHP.md](./IMPLEMENTATION_GUIDE_PHP.md)
   - Setup inicial do Laravel
   - Criar modelos e migrations
   - Implementar controllers
   - Deploy em produção

### 6️⃣ **Quer referência TypeScript?**
   👉 Arquivo: [src/app/shared/models/api-contracts.models.ts](./src/app/shared/models/api-contracts.models.ts)
   - Interfaces TypeScript
   - Tipos exportáveis
   - Documentação inline

---

## 📋 Mapa de Documentos

```
desconectando-para-conectar-web/
│
├── 📄 QUICK_REFERENCE.md ⭐ COMECE AQUI
│   ├─ Todos os 10 endpoints em uma página
│   ├─ Estruturas JSON resumidas
│   ├─ Valores enum
│   ├─ Testes com CURL
│   └─ Implementação Angular
│
├── 📄 API_ENDPOINTS_VISUAL_SUMMARY.md
│   ├─ Diagramas visuais
│   ├─ Matriz de endpoints
│   ├─ Fluxo de autenticação
│   ├─ Casos de uso
│   └─ Configurações recomendadas
│
├── 📄 API_CONTRACTS.md
│   ├─ Especificação completa
│   ├─ 1. HOME PAGE
│   ├─ 2. BLOG LISTAGEM
│   ├─ 3. BLOG PUBLICAÇÃO ÚNICA
│   ├─ 4. RIFAS LISTAGEM
│   ├─ 5. RIFA DETALHE
│   ├─ 6. LOGIN
│   ├─ 7. VALIDAR TOKEN
│   ├─ 8. LOGOUT
│   ├─ Padrões gerais
│   └─ Testes CURL
│
├── 📄 PHP_IMPLEMENTATION_EXAMPLES.php
│   ├─ Controllers (PublicController, BlogController, RaffleController, AuthController)
│   ├─ Models (User, BlogPost, Institution, Raffle)
│   ├─ Migrations completas
│   ├─ Middleware e validação
│   └─ Exemplos de cache
│
├── 📄 IMPLEMENTATION_GUIDE_PHP.md
│   ├─ Checklist de implementação
│   ├─ Setup inicial
│   ├─ Modelos e migrations
│   ├─ Controllers (com código completo)
│   ├─ Rotas
│   ├─ Middlewares e CORS
│   ├─ Testes
│   ├─ Deploy em produção
│   └─ Troubleshooting
│
├── 🔵 src/app/shared/models/api-contracts.models.ts
│   ├─ Interfaces TypeScript
│   ├─ HomePageResponse
│   ├─ BlogListResponse, BlogPostFull
│   ├─ RaffleListResponse, RaffleDetailResponse
│   ├─ LoginResponse, VerifyTokenResponse
│   └─ Todas as interfaces
│
└── 📋 API_DOCUMENTATION_INDEX.md (ESTE ARQUIVO)
    └─ Guia de navegação completo
```

---

## 🎬 Fluxo de Desenvolvimento Recomendado

### Fase 1: Entender a Arquitetura (30 min)
```
1. Ler QUICK_REFERENCE.md
2. Ver diagramas em API_ENDPOINTS_VISUAL_SUMMARY.md
3. Entender fluxo de autenticação
```

### Fase 2: Setup do Projeto (1 hora)
```
1. Seguir IMPLEMENTATION_GUIDE_PHP.md - Seção 1 (Setup Inicial)
2. Criar novo projeto Laravel
3. Configurar banco de dados
```

### Fase 3: Criar Estrutura (2 horas)
```
1. Seguir IMPLEMENTATION_GUIDE_PHP.md - Seção 2 (Modelos e Migrations)
2. Criar modelos User, BlogPost, Institution, Raffle
3. Executar migrations
4. Criar seeders com dados iniciais
```

### Fase 4: Implementar Controllers (3 horas)
```
1. Copiar código de PHP_IMPLEMENTATION_EXAMPLES.php
2. Seguir IMPLEMENTATION_GUIDE_PHP.md - Seção 4 (Controllers)
3. Implementar PublicController, BlogController, RaffleController, AuthController
```

### Fase 5: Configurar Rotas e Middlewares (1 hora)
```
1. Seguir IMPLEMENTATION_GUIDE_PHP.md - Seção 5 (Rotas)
2. Configurar CORS
3. Adicionar rate limiting
```

### Fase 6: Testes (2 horas)
```
1. Tester cada endpoint com CURL (ver QUICK_REFERENCE.md)
2. Testar em Postman
3. Verificar validações
```

### Fase 7: Deploy (1 hora)
```
1. Seguir IMPLEMENTATION_GUIDE_PHP.md - Seção 10 (Deploy)
2. Configurar variáveis de produção
3. Setup de servidor (Nginx/Apache)
```

**Total Estimado**: ~11 horas para implementação completa

---

## 📊 Resumo dos Endpoints

| # | Endpoint | Método | Autenticação | Descrição |
|---|----------|--------|--------------|-----------|
| 1 | `/api/public/home` | GET | ❌ | Página inicial com dados |
| 2 | `/api/public/blog` | GET | ❌ | Lista paginada de posts |
| 3 | `/api/public/blog/:id` | GET | ❌ | Post único completo |
| 4 | `/api/public/blog/:slug` | GET | ❌ | Post por slug |
| 5 | `/api/public/raffles` | GET | ❌ | Lista paginada de rifas |
| 6 | `/api/public/raffles/:id` | GET | ❌ | Rifa única completa |
| 7 | `/api/public/raffles/:slug` | GET | ❌ | Rifa por slug |
| 8 | `/api/auth/login` | POST | ❌ | Login com email/senha |
| 9 | `/api/auth/verify` | GET | ✅ | Validar token JWT |
| 10 | `/api/auth/logout` | POST | ✅ | Fazer logout |

---

## 🔗 Estruturas Principais

### HomePageResponse
```
Contém:
• Hero banner (título, subtítulo, CTA)
• Featured Raffles (3 rifas em destaque)
• Institutions carousel (4 instituições)
• Statistics (totais e métricas)
• Blog Preview (3 posts)
```

### BlogListResponse
```
Contém:
• Array de BlogPostPreview
• Pagination (total, page, limit, pages)
• Categories com contagem
```

### BlogPostFull
```
Contém:
• Post completo (conteúdo HTML)
• Autor com bio e redes sociais
• Tags, categoria
• Posts relacionados
• Comentários
• SEO metadata
```

### RaffleListResponse
```
Contém:
• Array de RaffleListItem
• Pagination info
• Filtros disponíveis (statuses, categories)
```

### RaffleDetailResponse
```
Contém:
• Rifa completa (descrição longa, galeria)
• Array de números (status: available/selected/occupied)
• Organizador/instituição
• Regras (HTML)
• Info do ganhador (se concluída)
• SEO metadata
```

### LoginResponse
```
Contém:
• JWT Token (24h de expiração)
• User data (id, name, email, phone, avatar, role)
• ExpiresIn (em segundos)
• RefreshToken (opcional)
```

---

## 🛠️ Stack Recomendado

### Backend
- **Framework**: Laravel 10+
- **Database**: PostgreSQL 16+ (recomendado)
- **Authentication**: Laravel Sanctum (JWT)
- **Cache**: Redis (opcional, mas recomendado)
- **Server**: Nginx com PHP-FPM

### Tools
- **API Testing**: Postman ou Insomnia
- **Version Control**: Git
- **Deployment**: Docker (opcional)
- **CI/CD**: GitHub Actions ou GitLab CI

---

## 🔐 Segurança Checklist

- [ ] HTTPS/SSL em produção
- [ ] CORS configurado corretamente
- [ ] Rate limiting ativo
- [ ] Password hashing (bcrypt/argon2)
- [ ] Input validation
- [ ] Output sanitization
- [ ] SQL injection prevention (usar Eloquent)
- [ ] XSS prevention (sanitizar HTML)
- [ ] CSRF tokens (se necessário)
- [ ] JWT com expiração
- [ ] Logging de erros
- [ ] Headers de segurança (X-Frame-Options, etc)

---

## 💡 Dicas Importantes

### 1. Começar Simples
Implemente primeiro os 7 endpoints públicos, depois os 3 de autenticação.

### 2. Testar Incrementalmente
Após cada endpoint, teste com CURL antes de passar para o próximo.

### 3. Usar Seeders
Populate seu banco com dados iniciais usando seeders para facilitar testes.

### 4. Cache Estratégico
- Home: 1 hora
- Blog list: 30 minutos
- Blog single: 24 horas
- Raffles list: 1 hora
- Raffle detail: 2 horas

### 5. Monitorar Performance
Use ferramentas como Laravel Telescope para debug e otimização.

### 6. Documentar com Swagger
Considere usar Swagger/OpenAPI para documentar a API e gerar clientes automaticamente.

---

## ❓ Perguntas Frequentes

### P: Por onde início?
**R**: Leia QUICK_REFERENCE.md (5 min), depois IMPLEMENTATION_GUIDE_PHP.md (setup).

### P: Preciso implementar tudo de uma vez?
**R**: Não! Implemente os endpoints públicos primeiro, depois autenticação, depois dashboard.

### P: Como conecto isso ao Angular?
**R**: Veja seção "Implementação no Angular" em QUICK_REFERENCE.md

### P: E se eu usar outra linguagem (Node.js, Python)?
**R**: Os contratos são agnósticos de linguagem. Use API_CONTRACTS.md como referência.

### P: Como faço testes?
**R**: Use CURL commands em QUICK_REFERENCE.md ou Postman para testar cada endpoint.

### P: Preciso de refresh token?
**R**: Opcional. JWT de 24h é suficiente para a maioria dos casos.

### P: E o deploy em produção?
**R**: Veja IMPLEMENTATION_GUIDE_PHP.md - Seção 10 (Deploy em Produção)

---

## 📞 Recursos Úteis

### Documentação Oficial
- **Laravel**: https://laravel.com/docs
- **Sanctum**: https://laravel.com/docs/sanctum
- **Eloquent**: https://laravel.com/docs/eloquent
- **Testing**: https://laravel.com/docs/testing

### Ferramentas Recomendadas
- **Postman**: https://www.postman.com
- **Insomnia**: https://insomnia.rest
- **Laravel Telescope**: https://laravel.com/docs/telescope
- **Swagger**: https://swagger.io

### Segurança
- **OWASP**: https://owasp.org/Top10/
- **JWT.io**: https://jwt.io
- **bcrypt**: https://en.wikipedia.org/wiki/Bcrypt

---

## 📝 Changelog

### v1.0 (2025-05-16)
- ✅ Documentação completa de 10 endpoints
- ✅ Exemplos PHP com Laravel
- ✅ Guia de implementação passo-a-passo
- ✅ Interfaces TypeScript
- ✅ Referência rápida CURL

---

## 👨‍💻 Autor

**Frontend Team**: Desconectando para Conectar  
**Status**: 🟢 Pronto para implementação  
**Versão**: 1.0  
**Data**: 2025-05-16

---

## 🎓 Próximas Fases (Não mapeadas ainda)

Após implementar os endpoints públicos, você precisará mapear:

- **Área do Membro** (endpoints protegidos de profile e histórico)
- **Dashboard Admin** (CRUD completo para rifas, doações, usuários, cms)
- **Webhooks** (para notificações em tempo real)
- **Relatórios** (analytics e estatísticas avançadas)

---

## ✨ Conclusão

Toda a arquitetura de dados para o frontend foi mapeada e documentada em múltiplos formatos. Você tem:

✅ **Contratos completos** em 6 diferentes formatos  
✅ **Exemplos de código** prontos para usar  
✅ **Guias passo-a-passo** de implementação  
✅ **Referências rápidas** para consulta  
✅ **Diagramas visuais** explicativos  
✅ **Commands CURL** para testar  

**Próximo passo**: Começar a implementação seguindo IMPLEMENTATION_GUIDE_PHP.md!

---

**Boa sorte! 🚀**
