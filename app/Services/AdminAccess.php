<?php

namespace App\Services;

use App\Models\User;

class AdminAccess
{
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'resources' => [
                    'dashboard' => [
                        'label' => 'Dashboard',
                        'actions' => [
                            'view' => 'View',
                        ],
                    ],
                ],
            ],
            'farmer_data' => [
                'label' => 'Farmer Data',
                'resources' => [
                    'farmer_list' => [
                        'label' => 'Farmer List',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'status' => 'Status',
                        ],
                    ],
                    'animal_list' => [
                        'label' => 'Animal List',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'status' => 'Status',
                            'import' => 'Import',
                        ],
                    ],
                    'pan_list' => [
                        'label' => 'PAN List',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'transfer' => 'Transfer',
                            'delete' => 'Delete',
                        ],
                    ],
                    'milk_production' => [
                        'label' => 'Milk Production',
                        'actions' => [
                            'view' => 'View',
                        ],
                    ],
                    'feeding' => [
                        'label' => 'Feeding',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                        ],
                    ],
                    'pregnancy' => [
                        'label' => 'Pregnancy',
                        'actions' => [
                            'view' => 'View',
                        ],
                    ],
                    'dairy' => [
                        'label' => 'Dairy',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                        ],
                    ],
                    'farmer_settings' => [
                        'label' => 'Farmer Settings',
                        'actions' => [
                            'view' => 'View',
                            'edit' => 'Edit',
                        ],
                    ],
                    'farmer_referred' => [
                        'label' => 'Refer & Earn',
                        'actions' => [
                            'view' => 'View',
                        ],
                    ],
                    'farmer_plan' => [
                        'label' => 'Farmer Plan',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                        ],
                    ],
                    'farmer_subscription' => [
                        'label' => 'Farmer Subscription',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                        ],
                    ],
                ],
            ],
            'animal_lifecycle' => [
                'label' => 'Animal Lifecycle',
                'resources' => [
                    'animal_lifecycle_active' => ['label' => 'Active', 'actions' => ['view' => 'View']],
                    'animal_lifecycle_sold' => ['label' => 'Sold', 'actions' => ['view' => 'View']],
                    'animal_lifecycle_death' => ['label' => 'Death', 'actions' => ['view' => 'View']],
                    'animal_lifecycle_pan_transfer' => ['label' => 'PAN Transfer', 'actions' => ['view' => 'View']],
                ],
            ],
            'health' => [
                'label' => 'Health',
                'resources' => [
                    'health_dmi' => [
                        'label' => 'DMI Calculator',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                        ],
                    ],
                    'health_mastitis' => [
                        'label' => 'Mastitis',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                        ],
                    ],
                ],
            ],
            'doctor' => [
                'label' => 'Doctor',
                'resources' => [
                    'doctor_registration' => [
                        'label' => 'Register Doctor',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                        ],
                    ],
                    'doctor_list' => [
                        'label' => 'Doctor List',
                        'actions' => [
                            'view' => 'View',
                            'status' => 'Approve / Status',
                        ],
                    ],
                    'doctor_appointments' => [
                        'label' => 'Appointment',
                        'actions' => [
                            'view' => 'View',
                            'assign' => 'Assign',
                        ],
                    ],
                    'doctor_visited' => [
                        'label' => 'Visited',
                        'actions' => ['view' => 'View'],
                    ],
                    'doctor_settings' => [
                        'label' => 'Doctor Settings',
                        'actions' => [
                            'view' => 'View',
                            'edit' => 'Edit',
                        ],
                    ],
                    'doctor_ratings' => [
                        'label' => 'Rating',
                        'actions' => ['view' => 'View'],
                    ],
                    'doctor_referred' => [
                        'label' => 'Refer & Earn',
                        'actions' => ['view' => 'View'],
                    ],
                    'doctor_plan' => [
                        'label' => 'Doctor Plan',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                        ],
                    ],
                    'doctor_subscription' => [
                        'label' => 'Doctor Subscription',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                        ],
                    ],
                ],
            ],
            'shop' => [
                'label' => 'Shop',
                'resources' => [
                    'shop_products' => [
                        'label' => 'Product List',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                        ],
                    ],
                    'shop_orders' => [
                        'label' => 'Orders',
                        'actions' => [
                            'view' => 'View',
                            'status' => 'Update Status',
                        ],
                    ],
                    'shop_animal_buy_sell' => [
                        'label' => 'Animal Buy / Sell',
                        'actions' => ['view' => 'View'],
                    ],
                ],
            ],
            'report' => [
                'label' => 'Report',
                'resources' => [
                    'analytics_farmer' => ['label' => 'Farmer Report', 'actions' => ['view' => 'View']],
                    'analytics_dairy' => ['label' => 'Dairy Report', 'actions' => ['view' => 'View']],
                    'analytics_doctor' => ['label' => 'Doctor Report', 'actions' => ['view' => 'View']],
                    'analytics_earnings' => ['label' => 'Earnings Report', 'actions' => ['view' => 'View']],
                ],
            ],
            'settings' => [
                'label' => 'Settings',
                'resources' => [
                    'settings_diseases' => [
                        'label' => 'Add Disease',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'status' => 'Status',
                        ],
                    ],
                    'settings_feed_types' => [
                        'label' => 'Add Feed Type',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'status' => 'Status',
                        ],
                    ],
                    'settings_templates' => [
                        'label' => 'Edit Templates',
                        'actions' => [
                            'view' => 'View',
                            'edit' => 'Edit',
                            'status' => 'Status',
                        ],
                    ],
                    'settings_roles' => [
                        'label' => 'Roles',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'status' => 'Status',
                        ],
                    ],
                    'settings_users' => [
                        'label' => 'Add User',
                        'actions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'status' => 'Status',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function permissionKey(string $resourceKey, string $actionKey): string
    {
        return "{$resourceKey}.{$actionKey}";
    }

    public static function allPermissionKeys(): array
    {
        $keys = [];

        foreach (self::groups() as $group) {
            foreach ($group['resources'] as $resourceKey => $resource) {
                foreach (array_keys($resource['actions']) as $actionKey) {
                    $keys[] = self::permissionKey($resourceKey, $actionKey);
                }
            }
        }

        return $keys;
    }

    public static function normalizePermissions(array $permissions): array
    {
        $allowed = array_flip(self::allPermissionKeys());

        return collect($permissions)
            ->filter(fn ($permission) => is_string($permission) && isset($allowed[$permission]))
            ->map(fn ($permission) => trim($permission))
            ->unique()
            ->values()
            ->all();
    }

    public static function permissionCount(array $permissions): int
    {
        return count(self::normalizePermissions($permissions));
    }

    public static function landingRouteFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $map = [
            'dashboard.view' => 'dashboard',
            'farmer_list.view' => 'farmer.list',
            'animal_list.view' => 'farmer.animals',
            'pan_list.view' => 'farmer.pans',
            'milk_production.view' => 'farmer.milk',
            'feeding.view' => 'farmer.feeding',
            'pregnancy.view' => 'farmer.pregnancy',
            'dairy.view' => 'farmer.dairy',
            'doctor_list.view' => 'doctor.index',
            'doctor_appointments.view' => 'doctor.appointments',
            'shop_products.view' => 'shop.index',
            'analytics_farmer.view' => 'analytics.farmer',
            'settings_diseases.view' => 'settings.diseases.index',
            'settings_roles.view' => 'settings.roles.index',
            'settings_users.view' => 'settings.users.index',
        ];

        foreach ($map as $permission => $routeName) {
            if ($user->hasPermission($permission)) {
                return $routeName;
            }
        }

        return null;
    }
}
