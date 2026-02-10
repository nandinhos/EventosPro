# OPcache Setup - EventosPro

## Overview
OPcache is now configured and optimized for Laravel application performance.

## Configuration

**File**: `docker/php/opcache.ini`

### Key Settings

- **Memory**: 256MB (doubled from 128MB)
- **Interned Strings**: 32MB (doubled from 16MB)
- **Max Files**: 20,000 (doubled from 10,000)
- **JIT**: Enabled with tracing mode (128MB buffer)
- **CLI**: Enabled for Artisan commands

### Expected Performance Improvements

- **20-30% faster** PHP execution
- **Reduced CPU usage** via bytecode caching
- **Faster Artisan commands** with CLI enabled
- **JIT compilation** for hot paths

## How to Apply

### Development (Laravel Sail)

```bash
# Rebuild Docker container
sail build --no-cache

# Restart containers
sail down
sail up -d

# Verify OPcache is enabled
sail php -r "var_dump(opcache_get_status());"
```

### Production

Set in `.env` or server configuration:

```ini
# For maximum performance in production
opcache.validate_timestamps=0
opcache.revalidate_freq=0

# After code deployment, clear OPcache
sail artisan opcache:clear
# or
sail php -r "opcache_reset();"
```

## Monitoring

### Check OPcache Status

```bash
# Via Artisan
sail artisan opcache:status

# Via PHP
sail php -r "print_r(opcache_get_status());"
```

### Key Metrics to Monitor

- `opcache_statistics.num_cached_scripts` - Should grow over time
- `opcache_statistics.hits` vs `opcache_statistics.misses` - High hit ratio is good
- `memory_usage.used_memory` - Should stay below `memory_consumption`

## Troubleshooting

### OPcache Not Working?

1. Check if enabled:
   ```bash
   sail php -i | grep opcache.enable
   ```

2. Rebuild container:
   ```bash
   sail build --no-cache
   ```

3. Verify file is copied:
   ```bash
   sail exec laravel.test cat /etc/php/8.4/cli/conf.d/zz-opcache-override.ini
   ```

### Cache Not Clearing?

```bash
# Clear OPcache
sail artisan opcache:clear

# Or manually
sail php -r "opcache_reset();"

# Restart containers (nuclear option)
sail down && sail up -d
```

## Production Recommendations

1. Set `opcache.validate_timestamps=0` for maximum performance
2. Clear OPcache after each deployment
3. Monitor memory usage and adjust `opcache.memory_consumption` if needed
4. Consider enabling `opcache.file_cache` for persistence

## References

- [PHP OPcache Documentation](https://www.php.net/manual/en/book.opcache.php)
- [Laravel Performance Optimization](https://laravel.com/docs/deployment#optimization)
- [PHP 8 JIT](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.jit)

---

**Last Updated**: 2025-11-14
**Status**: ✅ Configured and Ready
