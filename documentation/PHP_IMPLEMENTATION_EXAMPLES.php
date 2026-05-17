<?php

/**
 * ================================================
 * EXEMPLOS DE IMPLEMENTAÇÃO EM PHP
 * ================================================
 * 
 * Este arquivo contém exemplos de como implementar os endpoints
 * da API em PHP. Use como referência para criar seu próprio backend.
 * 
 * Stack recomendado:
 * - PHP 8.1+
 * - Laravel 10+ OU Symfony 6+ OU seu framework favorito
 * - PostgreSQL
 * - JWT para autenticação
 */

// ================================================
// 1. ESTRUTURA BÁSICA EM LARAVEL
// ================================================

// routes/api.php

Route::prefix('public')->group(function () {
    // Home Page
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

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify', [AuthController::class, 'verify'])->middleware('auth:sanctum');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// ================================================
// 2. EXEMPLO: CONTROLLER - HOME PAGE
// ================================================

namespace App\Http\Controllers;

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
        return Institution::active()
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
            'totalDonated' => Raffle::sum('current') * 10, // aproximado
            'livesImpacted' => \DB::table('user_raffles')->distinct('user_id')->count(),
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
        // Média de 200 palavras por minuto
        $words = str_word_count(strip_tags($content));
        return max(1, ceil($words / 200));
    }
}

// ================================================
// 3. EXEMPLO: CONTROLLER - BLOG
// ================================================

class BlogController extends Controller
{
    public function list(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);
            $search = $request->get('search');
            $category = $request->get('category');
            $sort = $request->get('sort', 'newest');

            $query = BlogPost::published();

            // Filtros
            if ($search) {
                $query->where('title', 'like', "%$search%")
                    ->orWhere('content', 'like', "%$search%");
            }

            if ($category) {
                $query->where('category', $category);
            }

            // Ordenação
            switch ($sort) {
                case 'oldest':
                    $query->oldest();
                    break;
                case 'popular':
                    $query->orderBy('views', 'desc');
                    break;
                default:
                    $query->latest();
            }

            $total = $query->count();
            $posts = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'data' => $posts->items(),
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ],
                'categories' => $this->getBlogCategories()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar blog',
                'code' => 'BLOG_ERROR'
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        
        // Incrementar visualizações
        $post->increment('views');

        return response()->json($this->formatBlogPostFull($post));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $post = BlogPost::where('slug', $slug)->firstOrFail();
        
        // Incrementar visualizações
        $post->increment('views');

        return response()->json($this->formatBlogPostFull($post));
    }

    private function formatBlogPostFull(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'image' => $post->featured_image,
            'imageAlt' => $post->image_alt,
            'author' => [
                'id' => $post->author->id,
                'name' => $post->author->name,
                'avatar' => $post->author->avatar,
                'bio' => $post->author->bio,
                'socialLinks' => json_decode($post->author->social_links, true)
            ],
            'category' => $post->category,
            'tags' => json_decode($post->tags, true),
            'publishedAt' => $post->published_at->toIso8601String(),
            'updatedAt' => $post->updated_at->toIso8601String(),
            'readTime' => $this->estimateReadTime($post->content),
            'views' => $post->views,
            'slug' => $post->slug,
            'relatedPosts' => $this->getRelatedPosts($post),
            'comments' => $post->comments,
            'seo' => [
                'metaDescription' => $post->meta_description,
                'keywords' => json_decode($post->meta_keywords, true)
            ]
        ];
    }

    private function getRelatedPosts(BlogPost $post): array
    {
        return BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->latest()
            ->limit(3)
            ->get()
            ->toArray();
    }

    private function getBlogCategories(): array
    {
        return \DB::table('blog_posts')
            ->where('published_at', '<=', now())
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->map(fn($cat) => [
                'label' => ucfirst($cat->category),
                'value' => strtolower($cat->category),
                'count' => $cat->count
            ])
            ->toArray();
    }

    private function estimateReadTime(string $content): int
    {
        $words = str_word_count(strip_tags($content));
        return max(1, ceil($words / 200));
    }
}

// ================================================
// 4. EXEMPLO: CONTROLLER - AUTENTICAÇÃO
// ================================================

class AuthController extends Controller
{
    public function login(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:6',
                'rememberMe' => 'boolean'
            ]);

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !\Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou senha inválidos',
                    'code' => 'INVALID_CREDENTIALS',
                    'timestamp' => now()->toIso8601String()
                ], 401);
            }

            if ($user->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'message' => 'Conta desativada',
                    'code' => 'ACCOUNT_DISABLED',
                    'timestamp' => now()->toIso8601String()
                ], 401);
            }

            // Criar token
            $token = $user->createToken('auth_token', ['*'], 
                now()->addHours(24)
            )->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'address' => $user->address,
                    'createdAt' => $user->created_at->toIso8601String()
                ],
                'expiresIn' => 86400, // 24 horas
                'refreshToken' => null // Implementar se necessário
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validação falhou',
                'code' => 'VALIDATION_ERROR',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro no login',
                'code' => 'LOGIN_ERROR'
            ], 500);
        }
    }

    public function verify(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->bearerToken();

        // Verificar expiração do token
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ],
            'expiresAt' => $personalAccessToken->expires_at->toIso8601String()
        ]);
    }

    public function logout(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}

// ================================================
// 5. MIGRATIONS - TABELAS NECESSÁRIAS
// ================================================

// migrations/create_blog_posts_table.php
Schema::create('blog_posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content');
    $table->string('featured_image');
    $table->string('image_alt')->nullable();
    $table->text('excerpt')->nullable();
    $table->string('category');
    $table->json('tags')->nullable();
    $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
    $table->string('meta_description')->nullable();
    $table->json('meta_keywords')->nullable();
    $table->integer('views')->default(0);
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    
    $table->index('slug');
    $table->index('category');
    $table->index('published_at');
});

// migrations/create_raffles_table.php
Schema::create('raffles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('description');
    $table->text('full_description');
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
    $table->foreignId('organization_id')->constrained('institutions')->onDelete('cascade');
    $table->text('rules')->nullable();
    $table->boolean('featured')->default(false);
    $table->timestamps();
    
    $table->index('slug');
    $table->index('status');
    $table->index('category');
    $table->index('draw_date');
});

// migrations/create_institutions_table.php
Schema::create('institutions', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->string('logo')->nullable();
    $table->string('image');
    $table->string('image_position')->default('center center');
    $table->json('contact')->nullable(); // email, phone, website
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();
});

// ================================================
// 6. MODELS - EXEMPLO
// ================================================

// app/Models/BlogPost.php
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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }
}

// ================================================
// 7. MIDDLEWARE - AUTENTICAÇÃO
// ================================================

// app/Http/Middleware/AuthenticateToken.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateToken
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->bearerToken()) {
            return response()->json([
                'valid' => false,
                'message' => 'Token não fornecido',
                'code' => 'TOKEN_MISSING'
            ], 401);
        }

        return $next($request);
    }
}

// ================================================
// 8. TRATAMENTO DE ERROS GLOBAL
// ================================================

// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($request->expectsJson()) {
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso não encontrado',
                'code' => 'NOT_FOUND'
            ], 404);
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'code' => 'VALIDATION_ERROR',
                'errors' => $exception->errors()
            ], 422);
        }
    }

    return parent::render($request, $exception);
}

// ================================================
// 9. CACHE RECOMENDADO
// ================================================

// Cache home page por 1 hora
cache()->remember('home_page_data', 3600, function () {
    return [
        'hero' => [...],
        'featuredRaffles' => [...],
        'statistics' => [...]
    ];
});

// Cache blog lista por 30 minutos
cache()->remember("blog_page_{$page}_{$limit}", 1800, function () {
    return [
        'data' => [...],
        'pagination' => [...]
    ];
});

// Limpar cache ao publicar novo post
\Cache::forget('home_page_data');
\Cache::flush('blog_*');

?>
