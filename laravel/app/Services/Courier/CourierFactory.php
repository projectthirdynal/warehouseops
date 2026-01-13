<?php

namespace App\Services\Courier;

use App\Models\CourierProvider;

/**
 * Factory for creating courier service instances.
 * Resolves the appropriate service based on provider code.
 */
class CourierFactory
{
    /**
     * Available courier service mappings.
     */
    protected static array $services = [
        'manual' => ManualCourierService::class,
        'jnt' => JntCourierService::class,
        // Add more couriers here:
        // 'lbc' => LbcCourierService::class,
        // 'ninja_van' => NinjaVanCourierService::class,
    ];

    /**
     * Create a courier service instance by provider code.
     */
    public static function make(string $code): CourierInterface
    {
        $provider = CourierProvider::findByCode($code);
        
        $serviceClass = self::$services[$code] ?? ManualCourierService::class;
        
        return new $serviceClass($provider);
    }

    /**
     * Create a courier service instance from a CourierProvider model.
     */
    public static function fromProvider(CourierProvider $provider): CourierInterface
    {
        return self::make($provider->code);
    }

    /**
     * Get the default courier service (Manual).
     */
    public static function default(): CourierInterface
    {
        return self::make('manual');
    }

    /**
     * Get all available courier codes.
     */
    public static function availableCouriers(): array
    {
        return array_keys(self::$services);
    }

    /**
     * Check if a courier code is supported.
     */
    public static function supports(string $code): bool
    {
        return isset(self::$services[$code]);
    }

    /**
     * Register a new courier service class.
     */
    public static function register(string $code, string $serviceClass): void
    {
        self::$services[$code] = $serviceClass;
    }
}
