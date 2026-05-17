# 🚀 GUIA DE IMPLEMENTAÇÃO - Backend PHP

## Checklist de Implementação

### Fase 1: Setup Inicial
- [ ] Criar novo projeto Laravel / Symfony
- [ ] Configurar variáveis de ambiente (.env)
- [ ] Setup do banco de dados (PostgreSQL)
- [ ] Instalar dependências (Composer)
- [ ] Configurar JWT authentication (laravel-sanctum ou tymon/jwt-auth)

### Fase 2: Modelos e Migrations
- [ ] Criar Migration: `users`
- [ ] Criar Migration: `blog_posts`
- [ ] Criar Migration: `institutions`
- [ ] Criar Migration: `raffles`
- [ ] Criar Migration: `comments` (opcional)
- [ ] Criar Models: User, BlogPost, Institution, Raffle, Comment
- [ ] Executar `php artisan migrate`

### Fase 3: Seeders (Dados Iniciais)
- [ ] Criar seeder para Institutions
- [ ] Criar seeder para BlogPosts
- [ ] Criar seeder para Raffles
- [ ] Criar seeder para Users (admin + teste)
- [ ] Executar seeders

### Fase 4: Controllers (Endpoints)
- [ ] PublicController - `getHome()`
- [ ] BlogController - `list()`, `show()`, `showBySlug()`
- [ ] RaffleController - `list()`, `show()`, `showBySlug()`
- [ ] AuthController - `login()`, `verify()`, `logout()`

### Fase 5: Rotas
- [ ] Configurar rotas públicas em `routes/api.php`
- [ ] Configurar rotas autenticadas
- [ ] Testar rotas com Postman

### Fase 6: Segurança
- [ ] Implementar CORS corretamente
- [ ] Adicionar rate limiting
- [ ] Validar inputs
- [ ] Sanitizar outputs
- [ ] Configurar headers de segurança

### Fase 7: Testes
- [ ] Testes unitários para controllers
- [ ] Testes de integração para endpoints
- [ ] Testes de autenticação
- [ ] Testar paginação

### Fase 8: Deploy
- [ ] Setup SSL/HTTPS
- [ ] Configurar variáveis de produção
- [ ] Deploy em staging
- [ ] Testes de carga
- [ ] Deploy em produção

---

## 1️⃣ Setup Inicial com Laravel

### 1.1 Criar Projeto
```bash
# Criar novo projeto Laravel
composer create-project laravel/laravel desconectando-api

cd desconectando-api

# Versão específica
composer create-project laravel/laravel:^10.0 desconectando-api
```

### 1.2 Configurar .env
```bash
cp .env.example .env

# Editar .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=desconectando_db
DB_USERNAME=postgres
DB_PASSWORD=postgres

APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:4200

# JWT
JWT_SECRET=seu_jwt_secret_aqui
JWT_ALGORITHM=HS256
JWT_EXPIRES_IN=86400
```

### 1.3 Instalar Autenticação JWT
```bash
# Usar Laravel Sanctum (recomendado para SPAs)
composer require laravel/sanctum

php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

php artisan migrate --seed
```

### 1.4 Testar Server
```bash
php artisan serve

# Deve estar rodando em http://localhost:8000
```

---

## 2️⃣ Criar Modelos e Migrations

### 2.1 User Model (já existe)
```bash
# Apenas verificar/adicionar campos
php artisan make:model User -m
```

**Migration - users table**:
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('full_name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('phone')->nullable();
    $table->string('avatar')->nullable();
    $table->string('address')->nullable();
    $table->enum('role', ['buyer', 'manager', 'publisher'])->default('buyer');
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    
    $table->index('email');
});
```

### 2.2 BlogPost Model
```bash
php artisan make:model BlogPost -m
```

**Migration - blog_posts table**:
```php
Schema::create('blog_posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->longText('content');
    $table->string('featured_image');
    $table->string('image_alt')->nullable();
    $table->text('excerpt')->nullable();
    $table->string('category');
    $table->json('tags')->nullable();
    $table->foreignId('author_id')
        ->constrained('users')
        ->onDelete('cascade');
    $table->string('meta_description')->nullable();
    $table->json('meta_keywords')->nullable();
    $table->integer('views')->default(0);
    $table->timestamp('published_at')->nullable();
    $table->softDeletes();
    $table->timestamps();
    
    $table->index('slug');
    $table->index('category');
    $table->index('published_at');
});
```

**Model - BlogPost.php**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'featured_image', 'image_alt',
        'excerpt', 'category', 'tags', 'author_id', 'meta_description',
        'meta_keywords', 'published_at'
    ];

    protected $dates = ['published_at', 'created_at', 'updated_at'];

    public function author()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }
}
```

### 2.3 Institution Model
```bash
php artisan make:model Institution -m
```

**Migration**:
```php
Schema::create('institutions', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->string('logo')->nullable();
    $table->string('image');
    $table->string('image_position')->default('center center');
    $table->json('contact')->nullable();
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();
});
```

### 2.4 Raffle Model
```bash
php artisan make:model Raffle -m
```

**Migration**:
```php
Schema::create('raffles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('description');
    $table->longText('full_description');
    $table->string('image');
    $table->json('gallery')->nullable();
    $table->decimal('goal', 12, 2);
    $table->decimal('current', 12, 2)->default(0);
    $table->enum('status', ['active', 'coming', 'finished'])->default('coming');
    $table->timestamp('draw_date');
    $table->string('category');
    $table->decimal('ticket_price', 10, 2);
    $table->integer('tickets_available');
    $table->integer('tickets_sold')->default(0);
    $table->foreignId('organization_id')
        ->constrained('institutions')
        ->onDelete('cascade');
    $table->text('rules')->nullable();
    $table->json('winner_info')->nullable();
    $table->boolean('featured')->default(false);
    $table->timestamps();
    
    $table->index('slug');
    $table->index('status');
    $table->index('category');
    $table->index('draw_date');
});
```

### 2.5 Executar Migrations
```bash
php artisan migrate
```

---

## 3️⃣ Criar Controllers

### 3.1 PublicController
```bash
php artisan make:controller Api/PublicController
```

**app/Http/Controllers/Api/PublicController.php**:
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Models\BlogPost;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public function getHome(): JsonResponse
    {
        try {
            $data = [
                'hero' => [
                    'title' => 'Desconectando para Conectar',
                    'subtitle' => 'Uma iniciativa solidária para o Sertão Nordestino',
                    'backgroundImage' => 'https://cdn.exemplo.com/hero-bg.jpg',
                    'ctaLabel' => 'Participar Agora',
                    'ctaLink' => '/public/raffles'
                ],
                'featuredRaffles' => $this->getFeaturedRaffles(),
                'institutions' => $this->getInstitutions(),
                'statistics' => $this->getStatistics(),
                'blogPreview' => $this->getBlogPreview()
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar página inicial',
                'code' => 'HOME_ERROR'
            ], 500);
        }
    }

    private function getFeaturedRaffles(): array
    {
        return Raffle::where('status', 'active')
            ->orWhere('featured', true)
            ->limit(3)
            ->get()
            ->map(fn($rifa) => [
                'id' => $rifa->id,
                'title' => $rifa->title,
                'description' => $rifa->description,
                'image' => $rifa->image,
                'progress' => (int)(($rifa->current / $rifa->goal) * 100),
                'goal' => $rifa->goal,
                'current' => $rifa->current,
                'status' => $rifa->status,
                'drawDate' => $rifa->draw_date->toIso8601String(),
                'category' => $rifa->category
            ])
            ->toArray();
    }

    private function getInstitutions(): array
    {
        return Institution::where('status', 'active')
            ->limit(4)
            ->get()
            ->map(fn($inst) => [
                'id' => $inst->id,
                'name' => $inst->name,
                'description' => $inst->description,
                'image' => $inst->image,
                'imagePosition' => $inst->image_position ?? 'center center'
            ])
            ->toArray();
    }

    private function getStatistics(): array
    {
        return [
            'totalDonated' => Raffle::sum('current') * 10,
            'livesImpacted' => \DB::table('raffles')
                ->sum('tickets_sold'),
            'communitiesReached' => Institution::count()
        ];
    }

    private function getBlogPreview(): array
    {
        return BlogPost::published()
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => substr($post->content, 0, 300),
                'image' => $post->featured_image,
                'imageAlt' => $post->image_alt,
                'eyebrow' => $post->category,
                'description' => $post->title,
                'slug' => $post->slug,
                'publishedAt' => $post->published_at->toIso8601String(),
                'readTime' => $this->estimateReadTime($post->content)
            ])
            ->toArray();
    }

    private function estimateReadTime(string $content): int
    {
        $words = str_word_count(strip_tags($content));
        return max(1, ceil($words / 200));
    }
}
```

### 3.2 BlogController
```bash
php artisan make:controller Api/BlogController
```

### 3.3 RaffleController
```bash
php artisan make:controller Api/RaffleController
```

### 3.4 AuthController
```bash
php artisan make:controller Api/AuthController
```

---

## 4️⃣ Configurar Rotas

**routes/api.php**:
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\RaffleController;
use App\Http\Controllers\Api\AuthController;

// Rotas Públicas
Route::prefix('public')->group(function () {
    Route::get('/home', [PublicController::class, 'getHome']);
    
    // Blog
    Route::get('/blog', [BlogController::class, 'list']);
    Route::get('/blog/{id}', [BlogController::class, 'show']);
    Route::get('/blog/slug/{slug}', [BlogController::class, 'showBySlug']);
    
    // Rifas
    Route::get('/raffles', [RaffleController::class, 'list']);
    Route::get('/raffles/{id}', [RaffleController::class, 'show']);
    Route::get('/raffles/slug/{slug}', [RaffleController::class, 'showBySlug']);
});

// Autenticação
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify', [AuthController::class, 'verify'])
        ->middleware('auth:sanctum');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
});

// Rotas Protegidas (Requer autenticação)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('member')->group(function () {
        Route::get('/profile', [MemberController::class, 'profile']);
        Route::put('/profile', [MemberController::class, 'updateProfile']);
        Route::get('/raffles', [MemberController::class, 'raffles']);
        Route::get('/donations', [MemberController::class, 'donations']);
    });
});
```

---

## 5️⃣ Middleware e CORS

**config/cors.php**:
```php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => [
    'http://localhost:4200',  // desenvolvimento
    'https://seu-dominio.com'  // produção
],
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => ['X-Total-Count', 'X-RateLimit-*'],
'max_age' => 86400,
'supports_credentials' => true,
```

---

## 6️⃣ Testar Endpoints

### 6.1 Usar Postman
```
1. Criar Collection "Desconectando API"
2. Criar requests para cada endpoint
3. Salvar responses como exemplos
4. Testar em diferentes cenários
```

### 6.2 Comando HTTP (httpie)
```bash
# GET home
http GET http://localhost:8000/api/public/home

# GET blog
http GET http://localhost:8000/api/public/blog page==1 limit==10

# POST login
http POST http://localhost:8000/api/auth/login \
  email=teste@exemplo.com \
  password=senha123
```

### 6.3 cURL
```bash
curl -X GET http://localhost:8000/api/public/home \
  -H "Content-Type: application/json"

curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@exemplo.com",
    "password": "senha123"
  }'
```

---

## 7️⃣ Estrutura de Pastas Recomendada

```
desconectando-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── PublicController.php
│   │   │       ├── BlogController.php
│   │   │       ├── RaffleController.php
│   │   │       └── AuthController.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── BlogPost.php
│   │   ├── Institution.php
│   │   └── Raffle.php
│   └── Services/
│       ├── AuthService.php
│       ├── BlogService.php
│       └── RaffleService.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php
│       ├── InstitutionSeeder.php
│       ├── BlogPostSeeder.php
│       └── RaffleSeeder.php
├── routes/
│   └── api.php
├── config/
│   └── cors.php
└── .env
```

---

## 8️⃣ Exemplo Seeder - Dados Iniciais

**database/seeders/InstitutionSeeder.php**:
```php
namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        Institution::create([
            'name' => 'Associação Sertaneja',
            'description' => 'Apoio às famílias do sertão nordestino',
            'image' => 'https://cdn.exemplo.com/inst-1.jpg',
            'imagePosition' => 'left center',
            'status' => 'active'
        ]);

        Institution::create([
            'name' => 'Instituto Raízes',
            'description' => 'Educação e cultura para comunidades rurais',
            'image' => 'https://cdn.exemplo.com/inst-2.jpg',
            'imagePosition' => 'center center',
            'status' => 'active'
        ]);

        // ... mais instituições
    }
}
```

**Executar seeders**:
```bash
php artisan db:seed --class=InstitutionSeeder
php artisan db:seed  # Executa todos
```

---

## 9️⃣ Validação e Segurança

### Request Validation
```php
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'password' => 'required|string|min:6|max:255',
    'title' => 'required|string|max:255',
    'content' => 'required|string',
]);
```

### Sanitização
```php
// Proteger contra SQL Injection
$posts = BlogPost::where('slug', $request->slug)->first();

// Proteger contra XSS
$sanitized = strip_tags($input);

// Usar hash para passwords
$password = Hash::make($request->password);
```

---

## 🔟 Deploy em Produção

### 10.1 Preparar para Deploy
```bash
# Gerar APP_KEY
php artisan key:generate

# Otimizar autoloader
composer install --optimize-autoloader --no-dev

# Cache de configuração
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Gerar JWT secret
php artisan jwt:secret
```

### 10.2 Configurar Servidor (Nginx)
```nginx
server {
    listen 443 ssl http2;
    server_name api.seu-dominio.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /var/www/desconectando-api/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Segurança
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
}
```

### 10.3 Variáveis de Produção (.env)
```
APP_ENV=production
APP_DEBUG=false
DB_HOST=db.seu-servidor.com
DB_DATABASE=desconectando_prod
DB_USERNAME=db_user
DB_PASSWORD=senha_super_segura
JWT_SECRET=seu_secret_aleatorio
FRONTEND_URL=https://seu-dominio.com
```

---

## 📞 Suporte e Dúvidas

### Documentação Oficial
- Laravel: https://laravel.com/docs
- Sanctum: https://laravel.com/docs/sanctum
- Migration: https://laravel.com/docs/migrations

### Referência Local
- Arquivo completo de contratos: `API_CONTRACTS.md`
- Resumo visual: `API_ENDPOINTS_VISUAL_SUMMARY.md`
- Exemplos PHP: `PHP_IMPLEMENTATION_EXAMPLES.php`

---

**Versão**: 1.0  
**Data**: 2025-05-16  
**Stack**: Laravel 10 + PostgreSQL + Sanctum JWT
