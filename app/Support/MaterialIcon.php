<?php

namespace App\Support;

use App\Models\Category;

final class MaterialIcon
{
    private const BY_CATEGORY_NAME = [
        'alimentacao' => 'restaurant',
        'mercado' => 'shopping_cart',
        'supermercado' => 'shopping_cart',
        'transporte' => 'directions_car',
        'mobilidade' => 'local_taxi',
        'assinaturas' => 'subscriptions',
        'saude e bem-estar' => 'medical_services',
        'saude' => 'medical_services',
        'educacao' => 'school',
        'pets' => 'pets',
        'moradia' => 'home',
        'lazer' => 'sports_esports',
        'compras' => 'shopping_bag',
        'tarifas e juros' => 'account_balance',
        'outros' => 'inventory_2',
        'salario' => 'payments',
        'outras receitas' => 'trending_up',
        'transferencias' => 'sync_alt',
    ];

    public static function forCategory(Category|string|null $category, string $fallbackKind = 'expense'): string
    {
        if ($category instanceof Category) {
            if ($category->icon && preg_match('/^[a-z0-9_]+$/', $category->icon)) {
                return $category->icon;
            }
            $name = $category->name;
            $kind = $category->kind ?: $fallbackKind;
        } elseif (is_string($category) && $category !== '') {
            $name = $category;
            $kind = $fallbackKind;
        } else {
            return $fallbackKind === 'income' ? 'trending_up' : 'receipt_long';
        }

        $key = Text::normalize($name);

        if (isset(self::BY_CATEGORY_NAME[$key])) {
            return self::BY_CATEGORY_NAME[$key];
        }

        foreach (self::BY_CATEGORY_NAME as $term => $icon) {
            if (str_contains($key, $term)) {
                return $icon;
            }
        }

        return match ($kind) {
            'income' => 'trending_up',
            'transfer' => 'sync_alt',
            default => 'receipt_long',
        };
    }

    public static function badgeClasses(Category|string|null $category, string $kind = 'expense'): string
    {
        if ($kind === 'income') {
            return 'bg-primary/15 text-primary';
        }

        $name = $category instanceof Category ? $category->name : (string) $category;
        $key = Text::normalize($name);

        if (str_contains($key, 'alimentacao') || str_contains($key, 'restaurante')) {
            return 'bg-error/15 text-error';
        }
        if (str_contains($key, 'mercado') || str_contains($key, 'transporte')) {
            return 'bg-tertiary/15 text-tertiary';
        }
        if (str_contains($key, 'assinatura') || str_contains($key, 'lazer')) {
            return 'bg-secondary-container/25 text-secondary-fixed-dim';
        }

        return 'bg-primary/15 text-primary';
    }
}
