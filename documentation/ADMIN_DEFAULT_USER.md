# Usuário Admin Default

## Visão Geral

A partir da versão atual, a API cria automaticamente um **usuário administrador padrão** durante o seed do banco de dados. Este usuário é protegido e não pode ser excluído ou editado.

## Credenciais

| Campo | Valor |
|-------|-------|
| **Email** | `admin@desconectando.local` |
| **Senha** | `adminSenha@0123` |
| **Role** | `manager` |
| **Status** | `active` |

## Características

✅ **Protegido contra exclusão** - Tentativas de deletar retornam erro 403  
✅ **Protegido contra edição** - Tentativas de editar retornam erro 403  
✅ **Sempre criado ao fazer seed** - Executar `php artisan db:seed` garante sua existência  
✅ **Identificável no frontend** - Campo `isDefault: true` nos dados da API  

## Fluxo de Criação

1. Ao executar `php artisan migrate`, a migration adiciona o campo `is_default` (boolean) à tabela `users`
2. Ao executar `php artisan db:seed`, o `AdminDefaultSeeder` cria o usuário admin com `is_default = true`
3. O campo aparece em todas as respostas da API com o valor `isDefault`

## Como Proteger

### Backend (Automático)

O `AdminUserController` já valida e protege:

```php
// Update
if ((bool) $user->is_default) {
    return response()->json([
        'success' => false,
        'message' => 'Não é permitido editar o usuário administrador padrão',
        'code' => 'PROTECTED_DEFAULT_USER',
    ], 403);
}

// Destroy
if ((bool) $user->is_default) {
    return response()->json([
        'success' => false,
        'message' => 'Não é permitido excluir o usuário administrador padrão',
        'code' => 'PROTECTED_DEFAULT_USER',
    ], 403);
}
```

### Frontend (Recomendado)

No frontend, desabilite os botões de edição/exclusão para usuários com `isDefault: true`:

```typescript
// Exemplo em Angular
isDefaultUser(user: User): boolean {
  return user.isDefault === true;
}

// Usar em template
<button 
  (click)="editUser(user)" 
  [disabled]="isDefaultUser(user)"
  title="Usuário administrador padrão não pode ser editado"
>
  Editar
</button>
```

## Endpoints Afetados

### GET /api/admin/users
Retorna todos os usuários com o campo `isDefault`:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "fullName": "Administrador",
      "email": "admin@desconectando.local",
      "role": "manager",
      "status": "active",
      "isDefault": true,
      "createdAt": "2026-06-07T10:00:00.000Z"
    }
  ]
}
```

### PUT /api/admin/users/{id}
Se tentar editar o admin default (id=1), retorna:

```json
{
  "success": false,
  "message": "Não é permitido editar o usuário administrador padrão",
  "code": "PROTECTED_DEFAULT_USER"
}
```

HTTP Status: **403 Forbidden**

### DELETE /api/admin/users/{id}
Se tentar deletar o admin default (id=1), retorna:

```json
{
  "success": false,
  "message": "Não é permitido excluir o usuário administrador padrão",
  "code": "PROTECTED_DEFAULT_USER"
}
```

HTTP Status: **403 Forbidden**

## Mudança de Senha (Admin Padrão)

Para mudar a senha do admin default após o primeiro login, use o endpoint padrão de atualização de perfil (se implementado) ou atualize diretamente no banco:

```bash
php artisan tinker
>>> $admin = \App\Models\User::where('email', 'admin@desconectando.local')->first();
>>> $admin->password = Hash::make('nova_senha_segura');
>>> $admin->save();
```

## Recuperação de Acesso

Se o admin default for bloqueado ou senha esquecida, siga os passos:

1. Acesse o servidor via SSH
2. Redefina a senha via tinker (vide acima)
3. Ou execute uma migration fresca com seed (isso vai resetar o banco!)

## Considerações de Segurança

⚠️ **Em produção:**
- Mude a senha padrão imediatamente após o primeiro login
- Use senhas fortes e únicas
- Implemente 2FA se possível
- Não compartilhe as credenciais
- Monitore logs de acesso da conta admin

## Referências

- [AdminDefaultSeeder.php](../database/seeders/AdminDefaultSeeder.php)
- [AdminUserController.php](../app/Http/Controllers/Api/AdminUserController.php)
- [2026_06_07_000000_add_is_default_to_users_table.php](../database/migrations/2026_06_07_000000_add_is_default_to_users_table.php)

---

**Data**: 2026-06-07  
**Versão**: 1.0  
**Status**: ✅ Implementado
