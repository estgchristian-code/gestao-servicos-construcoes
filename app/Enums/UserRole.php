<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Comercial = 'comercial';
    case Tecnico = 'tecnico';

    /**
     * Display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Comercial => 'Comercial',
            self::Tecnico => 'Técnico',
        };
    }

    /**
     * Description for the role.
     */
    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Acesso administrativo completo da própria empresa.',
            self::Comercial => 'Permissões restritas à gestão comercial: clientes e orçamentos.',
            self::Tecnico => 'Permissões restritas à execução de ordens de serviço atribuídas.',
        };
    }
}
