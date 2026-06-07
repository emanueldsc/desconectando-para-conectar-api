# Solução: Problema de Imagens Quebradas em Produção

## Problema Identificado

As postagens antigas em produção estão com referências de imagens quebradas após deployments. Isso ocorria porque as URLs das imagens eram construídas usando `$request->getSchemeAndHttpHost()`, que é **não confiável em produção** quando há proxies, load balancers ou configurações de HTTPS.

## Causa Raiz

Foram encontrados **4 pontos** na API onde as URLs eram construídas incorretamente:

1. **AdminBlogController::uploadFeaturedImage()** - Imagens de blogs
2. **AdminRaffleController::uploadImage()** - Imagens de rifas  
3. **AdminCmsController::uploadBannerImage()** - Banners CMS
4. **RaffleController::uploadReservationReceipt()** - Recibos de pagamento

Exemplo do código problemático:
```php
// ❌ ANTES (Problemático)
$url = rtrim($request->getSchemeAndHttpHost(), '/').Storage::url($path);

// ✅ DEPOIS (Correto)
$url = rtrim(config('app.url'), '/').Storage::url($path);
```

## Por que `config('app.url')` é melhor?

- **Confiável em produção**: Vem diretamente da variável `APP_URL` no `.env`
- **Independente de proxies**: Não depende dos headers do request
- **Funciona com HTTPS**: Respeita a configuração de HTTPS/HTTP correta
- **Consistente**: Garante URLs uniformes em todos os ambientes

## Correções Implementadas

### 1. Código da API Corrigido

Todos os 4 controladores foram atualizados para usar `config('app.url')` em vez de `$request->getSchemeAndHttpHost()`.

### 2. Comando Artisan para Corrigir URLs Antigas

Um novo comando foi criado: `php artisan images:fix-urls`

**Uso:**

```bash
# Ver quais URLs serão alteradas (sem fazer mudanças)
php artisan images:fix-urls --dry-run

# Aplicar as correções
php artisan images:fix-urls
```

**O que o comando faz:**

- Encontra todas as imagens no banco de dados com URLs incorretas
- Reconstrói as URLs usando a `APP_URL` correta configurada
- Atualiza BlogPosts, Raffles e CMS Banners
- Preserva URLs externas (placeholders, etc)

## Passos para Resolver em Produção

### 1. Deploy da API Corrigida

```bash
# Fazer git pull com as alterações
git pull origin main

# Instalar dependências (se necessário)
composer install

# Executar migrations (se houver)
php artisan migrate --force

# Executar o comando para corrigir URLs
php artisan images:fix-urls
```

### 2. Verificar `APP_URL` no `.env`

Certifique-se de que a variável de ambiente está correta:

```env
APP_URL=https://seu-dominio-producao.com
```

❌ Incorreto em produção:
```env
APP_URL=http://localhost
APP_URL=127.0.0.1
```

### 3. Verificar Symlink de Storage

Verifique se o symlink `public/storage` → `storage/app/public` existe:

```bash
# Listar symlinks
ls -la public/ | grep storage

# Se não existir, criar:
php artisan storage:link
```

### 4. Testar

Após o deploy:

1. Verifique se as postagens antigas agora carregam as imagens corretamente
2. Crie uma nova postagem/rifa e verifique se a imagem funciona
3. Verifique os logs em `storage/logs/laravel.log` para erros

## Preventivo para Futuros Deployments

Para evitar esse problema novamente:

1. ✅ Sempre use `config('app.url')` para construir URLs internas
2. ✅ Configure `APP_URL` corretamente no `.env` de cada ambiente
3. ✅ Verifique se o symlink de storage existe após deploy
4. ✅ Execute `php artisan storage:link` se necessário
5. ✅ Teste imagens antes de considerar o deploy completo

## Referências

- [Laravel Storage Documentation](https://laravel.com/docs/11.x/filesystem)
- [Laravel Configuration](https://laravel.com/docs/11.x/configuration)
- `config/filesystems.php` - Configuração de disco 'public'
- `app/Console/Commands/FixImageUrls.php` - Comando para corrigir URLs

---

**Data**: 2026-06-07  
**Status**: ✅ Corrigido e testado
